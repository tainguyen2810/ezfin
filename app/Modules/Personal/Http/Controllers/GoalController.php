<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Http\Requests\GoalRequest;
use App\Modules\Personal\Models\Goal;
use App\Modules\Personal\Models\GoalContribution;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GoalController
{
    public function index(Request $request): View
    {
        $completedTab = $request->query('trang_thai') === 'hoan-thanh';
        [$completed, $active] = Goal::withSaved()->orderByRaw('deadline is null')->orderBy('deadline')->orderBy('name')->get()
            ->partition(fn (Goal $goal) => $goal->isCompleted());

        return view('personal::goals.index', [
            'completedTab' => $completedTab,
            'goals' => $completedTab ? $completed : $active,
            'activeCount' => $active->count(),
            'completedCount' => $completed->count(),
            'target' => $active->sum(fn (Goal $goal) => $goal->target()),
            'saved' => $active->sum(fn (Goal $goal) => $goal->savedAmount()),
            'monthly' => $active->sum(fn (Goal $goal) => $goal->monthlyNeeded() ?? 0),
        ]);
    }

    public function create(): View
    {
        return view('personal::goals.form', ['goal' => new Goal(['icon' => 'flag', 'color' => 'blue'])]);
    }

    public function store(GoalRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $goal = DB::transaction(function () use ($data) {
            $goal = Goal::create($data);
            if ($data['initial_amount'] > 0) {
                $goal->contributions()->create(['amount' => $data['initial_amount'], 'contributed_on' => today(), 'note' => 'Số đã có khi tạo mục tiêu']);
            }

            return $goal;
        });

        return redirect()->route('personal.goals.show', $goal)->with('toast', ['message' => 'Đã tạo mục tiêu.', 'type' => 'success']);
    }

    public function show(Goal $goal): View
    {
        Gate::authorize('view', $goal);

        return view('personal::goals.show', [
            'goal' => $goal,
            'contributions' => $goal->contributions()->orderByDesc('contributed_on')->orderByDesc('id')->get(),
        ]);
    }

    public function edit(Goal $goal): View
    {
        Gate::authorize('update', $goal);

        return view('personal::goals.form', ['goal' => $goal]);
    }

    public function update(GoalRequest $request, Goal $goal): RedirectResponse
    {
        Gate::authorize('update', $goal);
        $goal->update(collect($request->validated())->except('initial_amount')->all());

        return redirect()->route('personal.goals.show', $goal)->with('toast', ['message' => 'Đã lưu thay đổi.', 'type' => 'success']);
    }

    /** Xóa mục tiêu và lịch sử góp. Tiền trong ví không bị ảnh hưởng. */
    public function destroy(Goal $goal): RedirectResponse
    {
        Gate::authorize('delete', $goal);
        $goal->delete();

        return redirect()->route('personal.goals.index')->with('toast', ['message' => 'Đã xóa mục tiêu "'.$goal->name.'".', 'type' => 'success']);
    }

    /** Ghi một lần góp thêm hoặc rút bớt; không rút quá số đã để dành. */
    public function contribute(Request $request, Goal $goal): RedirectResponse
    {
        Gate::authorize('update', $goal);
        $request->merge(['amount' => preg_replace('/\D/', '', (string) $request->input('amount'))]);
        $data = $request->validate([
            'direction' => ['required', 'in:gop,rut'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999999'],
            'contributed_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['amount' => 'số tiền', 'contributed_on' => 'ngày', 'note' => 'ghi chú']);

        $withdraw = $data['direction'] === 'rut';
        if ($withdraw && $data['amount'] > $goal->savedAmount()) {
            throw ValidationException::withMessages(['amount' => 'Chỉ rút được tối đa '.Format::money($goal->savedAmount()).'.']);
        }

        $goal->contributions()->create([
            'amount' => $withdraw ? -$data['amount'] : $data['amount'],
            'contributed_on' => $data['contributed_on'],
            'note' => $data['note'] ?? null,
        ]);
        $goal->offsetUnset('saved'); // tính lại số đã để dành

        $message = $goal->isCompleted() && ! $withdraw ? 'Chúc mừng, bạn đã đạt mục tiêu!' : ($withdraw ? 'Đã ghi khoản rút.' : 'Đã ghi khoản góp.');

        return redirect()->route('personal.goals.show', $goal)->with('toast', ['message' => $message, 'type' => 'success']);
    }

    public function destroyContribution(Goal $goal, GoalContribution $contribution): RedirectResponse
    {
        Gate::authorize('delete', $contribution);
        abort_unless($contribution->goal_id === $goal->id, 404);
        $contribution->delete();

        return redirect()->route('personal.goals.show', $goal)->with('toast', ['message' => 'Đã xóa một lần góp/rút.', 'type' => 'success']);
    }
}
