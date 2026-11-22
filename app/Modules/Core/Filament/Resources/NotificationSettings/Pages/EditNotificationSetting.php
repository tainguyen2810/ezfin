<?php

namespace App\Modules\Core\Filament\Resources\NotificationSettings\Pages;

use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Actions\SendTestNotification;
use App\Modules\Core\Filament\Resources\NotificationSettings\NotificationSettingResource;
use App\Modules\Core\Models\NotificationSetting;
use App\Modules\Core\Models\NotificationTemplate;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\NotificationType;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sửa một loại thông báo. Form hiện giá trị đang áp dụng; khi lưu, ô nào trùng mặc định thì lưu trống để sau này
 * mặc định trong code thay đổi vẫn được áp dụng.
 *
 * @property NotificationSetting $record
 */
class EditNotificationSetting extends EditRecord
{
    protected static string $resource = NotificationSettingResource::class;

    public function getTitle(): string
    {
        return $this->type()->label;
    }

    public function getBreadcrumb(): string
    {
        return 'Sửa';
    }

    protected function resolveRecord(int|string $key): Model
    {
        NotificationSetting::syncCatalog();

        $record = parent::resolveRecord($key);
        abort_if($record->type() === null, 404);

        return $record;
    }

    private function type(): NotificationType
    {
        return $this->record->type();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $type = $this->type();
        $center = app(NotificationCenter::class);
        $data['enabled'] = $center->enabled($type->key);
        $data['channels'] = array_map(fn (Channel $channel) => $channel->value, $center->configuredChannels($type->key));
        $data['recipient_roles'] = $center->recipientRoles($type->key);
        $data['extra_emails'] = $this->record->extra_emails ?? [];
        $data['repeat_hours'] = $this->record->repeat_hours ?? $type->defaultRepeatHours;
        $data['templates'] = [];

        // Ô hiện chữ đang áp dụng (đã sửa hoặc mặc định) để quản trị viên sửa trực tiếp trên đó.
        foreach ($type->channels() as $channel) {
            $data['templates'][$channel->value] = $center->template($type->key, $channel);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var NotificationSetting $record */
        $type = $this->type();

        DB::transaction(function () use ($record, $data, $type) {
            $channels = array_values(array_intersect(array_map(fn (Channel $c) => $c->value, $type->channels()), $data['channels'] ?? []));
            $defaultChannels = array_map(fn (Channel $c) => $c->value, $type->defaultChannels);
            $roles = array_values($data['recipient_roles'] ?? $type->defaultRoles);
            $repeat = filled($data['repeat_hours'] ?? null) ? (int) $data['repeat_hours'] : null;

            $record->fill([
                'enabled' => $type->canDisable ? (bool) ($data['enabled'] ?? true) : true,
                'channels' => ! $type->canDisable || $this->sameSet($channels, $defaultChannels) ? null : $channels,
                'recipient_roles' => $this->sameSet($roles, $type->defaultRoles) ? null : $roles,
                'extra_emails' => array_values(array_unique(array_map('strtolower', array_map('trim', $data['extra_emails'] ?? [])))) ?: null,
                'repeat_hours' => $repeat === $type->defaultRepeatHours ? null : $repeat,
            ]);
            $record->save();
            $record->touch();

            foreach ($type->channels() as $channel) {
                $this->saveTemplate($type, $channel, $data['templates'][$channel->value] ?? []);
            }
        });

        return $record;
    }

    /** @param  array<string, mixed>  $draft */
    private function saveTemplate(NotificationType $type, Channel $channel, array $draft): void
    {
        $default = $type->defaultTemplate($channel);
        $values = [];

        foreach (array_keys($default) as $field) {
            $value = NotificationSettingResource::normalize($draft[$field] ?? null);
            $values[$field] = $value === null || $value === $default[$field] ? null : $value;
        }

        $existing = NotificationTemplate::where('key', $type->key)->where('channel', $channel->value)->first();

        if (array_filter($values) === []) {
            $existing?->delete();

            return;
        }

        $template = $existing ?? new NotificationTemplate(['key' => $type->key, 'channel' => $channel->value]);
        $template->fill($values + ['updated_by' => auth()->id()])->save();
    }

    /** @param  list<string>  $a @param  list<string>  $b */
    private function sameSet(array $a, array $b): bool
    {
        sort($a);
        sort($b);

        return $a === $b;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')->label('Gửi thử cho tôi')->icon(Heroicon::OutlinedPaperAirplane)->color('gray')
                ->modalDescription(fn () => 'Gửi nội dung đang soạn (kể cả chưa lưu) với dữ liệu mẫu tới '.auth()->user()->email.'.')
                ->modalSubmitActionLabel('Gửi thử')
                ->action(function () {
                    $type = $this->type();
                    $state = $this->form->getRawState();
                    $channels = array_values(array_filter(array_map(fn ($value) => Channel::tryFrom((string) $value), $state['channels'] ?? [])));
                    $templates = [];

                    foreach ($type->channels() as $channel) {
                        $templates[$channel->value] = NotificationSettingResource::draftTemplate($type, $channel, (array) ($state['templates'][$channel->value] ?? []));
                    }

                    if ($channels === []) {
                        Notification::make()->warning()->title('Chưa chọn kênh nào để gửi thử.')->send();

                        return;
                    }

                    try {
                        app(SendTestNotification::class)->handle($type->key, auth()->user(), $channels, $templates);
                        Notification::make()->success()->title('Đã gửi thử')
                            ->body(in_array(Channel::Mail, $channels, true) ? 'Kiểm tra hộp thư '.auth()->user()->email.'.' : 'Xem trong chuông thông báo.')->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->persistent()->title('Không gửi được email')
                            ->body('Kiểm tra cấu hình SMTP (MAIL_* trong .env). Chi tiết: '.Str::limit($e->getMessage(), 200))->send();
                    }
                }),
            Action::make('restoreDefaults')->label('Khôi phục mặc định')->icon(Heroicon::OutlinedArrowPath)->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Xóa mọi nội dung và cài đặt đã sửa của loại thông báo này, dùng lại mặc định của hệ thống.')
                ->action(function () {
                    DB::transaction(function () {
                        NotificationTemplate::where('key', $this->record->key)->get()->each->delete();
                        $this->record->fill(['enabled' => true, 'channels' => null, 'recipient_roles' => null, 'extra_emails' => null, 'repeat_hours' => null])->save();
                    });
                    $this->fillForm();
                    Notification::make()->success()->title('Đã khôi phục mặc định')->send();
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Đã lưu cài đặt thông báo';
    }
}
