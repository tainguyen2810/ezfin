<?php

namespace App\Modules\Personal\Actions;

use App\Models\User;
use App\Modules\Personal\Mail\DueReminderMail;
use App\Modules\Personal\Models\RecurringTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Email nhắc các khoản định kỳ sắp đến hạn (theo số ngày nhắc trước người dùng chọn). Mỗi người một email,
 * mỗi kỳ chỉ nhắc một lần (reminded_for).
 */
class SendDueReminders
{
    /** @return int số email đã đưa vào hàng đợi */
    public function handle(Carbon $today): int
    {
        $due = RecurringTransaction::withoutGlobalScope('owner')
            ->whereNotNull('remind_days_before')
            ->whereNotNull('next_due_on')
            ->whereDate('next_due_on', '>=', $today->toDateString())
            ->with(['wallet' => fn ($q) => $q->withoutGlobalScope('owner'), 'user'])
            ->get()
            ->filter(fn (RecurringTransaction $item) => $item->next_due_on->copy()->subDays($item->remind_days_before)->lte($today)
                && ! $item->reminded_for?->isSameDay($item->next_due_on));

        $sent = 0;
        $due->groupBy('user_id')->each(function (Collection $items) use ($today, &$sent) {
            /** @var User|null $user */
            $user = $items->first()->user;
            if (! $user) {
                return;
            }

            Mail::to($user)->queue(new DueReminderMail($user, $items->sortBy('next_due_on')->values(), $today->copy()));
            $sent++;

            RecurringTransaction::withoutGlobalScope('owner')->whereKey($items->modelKeys())->get()
                ->each(fn (RecurringTransaction $item) => $item->forceFill(['reminded_for' => $item->next_due_on])->save());
        });

        return $sent;
    }
}
