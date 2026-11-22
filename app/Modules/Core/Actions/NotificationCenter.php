<?php

namespace App\Modules\Core\Actions;

use App\Modules\Core\Models\NotificationSetting;
use App\Modules\Core\Models\NotificationTemplate;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\NotificationCatalog;
use App\Modules\Core\Notifications\NotificationType;
use App\Modules\Core\Notifications\RenderedMessage;
use App\Modules\Core\Notifications\TemplateRenderer;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cài đặt và nội dung đang áp dụng của từng loại thông báo: phần quản trị viên đã sửa đè lên mặc định trong code.
 * Module gọi class này (hoặc SendAdminNotification) thay vì viết cứng chữ trong thông báo.
 */
class NotificationCenter
{
    public function __construct(private readonly NotificationCatalog $catalog) {}

    public function type(string $key): NotificationType
    {
        return $this->catalog->get($key);
    }

    public function setting(string $key): ?NotificationSetting
    {
        return NotificationSetting::where('key', $key)->first();
    }

    public function enabled(string $key): bool
    {
        $type = $this->type($key);

        return ! $type->canDisable || ($this->setting($key)?->enabled ?? true);
    }

    /** @return list<Channel> Kênh đang bật (rỗng khi loại bị tắt). */
    public function channels(string $key): array
    {
        return $this->enabled($key) ? $this->configuredChannels($key) : [];
    }

    /** @return list<Channel> Kênh đã chọn cho loại này, kể cả khi loại đang tắt. */
    public function configuredChannels(string $key): array
    {
        $type = $this->type($key);
        $saved = $type->canDisable ? $this->setting($key)?->channels : null;

        if ($saved === null) {
            return $type->defaultChannels;
        }

        return array_values(array_filter($type->channels(), fn (Channel $channel) => in_array($channel->value, $saved, true)));
    }

    public function channelEnabled(string $key, Channel $channel): bool
    {
        return in_array($channel, $this->channels($key), true);
    }

    /** @return list<string> Vai trò nhận thông báo quản trị. */
    public function recipientRoles(string $key): array
    {
        return $this->setting($key)?->recipient_roles ?? $this->type($key)->defaultRoles;
    }

    /** @return list<string> Email nhận thêm ngoài tài khoản quản trị (chỉ kênh email). */
    public function extraEmails(string $key): array
    {
        return array_values(array_filter($this->setting($key)?->extra_emails ?? [], fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
    }

    /** Số giờ trước khi nhắc lại lỗi còn tiếp diễn; null = loại này không nhắc lại. */
    public function repeatHours(string $key): ?int
    {
        $type = $this->type($key);

        if ($type->defaultRepeatHours === null) {
            return null;
        }

        return max(1, $this->setting($key)?->repeat_hours ?? $type->defaultRepeatHours);
    }

    /** @return array{subject: string, greeting: string, body: string, action_label: string} Mẫu đang áp dụng (chưa thay biến). */
    public function template(string $key, Channel $channel): array
    {
        $template = $this->type($key)->defaultTemplate($channel);
        $saved = NotificationTemplate::where('key', $key)->where('channel', $channel->value)->first();

        foreach (array_keys($template) as $field) {
            if (filled($saved?->{$field})) {
                $template[$field] = $saved->{$field};
            }
        }

        return $template;
    }

    /**
     * Dựng nội dung đã thay biến. Mẫu đã sửa lỗi (tiêu đề rỗng sau khi thay biến...) thì dùng mẫu mặc định và ghi log,
     * để thông báo quan trọng không bao giờ bị mất.
     *
     * @param  array<string, scalar|null>  $variables
     * @param  array{subject: string, greeting: string, body: string, action_label: string}|null  $template  Mẫu đang soạn (xem trước, gửi thử).
     */
    public function render(string $key, Channel $channel, array $variables, ?array $template = null): RenderedMessage
    {
        $type = $this->type($key);

        try {
            $message = $this->build($key, $channel, $template ?? $this->template($key, $channel), $variables);

            if (trim($message->subject) !== '' && ($channel !== Channel::Mail || trim($message->body) !== '' || trim($type->defaultTemplate($channel)['body']) === '')) {
                return $message;
            }

            Log::warning('Notification template rendered empty, using default', ['key' => $key, 'channel' => $channel->value]);
        } catch (Throwable $e) {
            Log::warning('Notification template failed, using default', ['key' => $key, 'channel' => $channel->value, 'error' => $e->getMessage()]);
        }

        return $this->build($key, $channel, $type->defaultTemplate($channel), $variables);
    }

    /**
     * @param  array{subject: string, greeting: string, body: string, action_label: string}  $template
     * @param  array<string, scalar|null>  $variables
     */
    private function build(string $key, Channel $channel, array $template, array $variables): RenderedMessage
    {
        $plain = fn (string $text) => trim(preg_replace('/\s+/u', ' ', TemplateRenderer::render($text, $variables)) ?? '');
        // Thân email là Markdown: thoát ký tự Markdown trong biến. Thân chuông là HTML đã lọc: thoát HTML trong biến.
        $body = $channel === Channel::Mail
            ? TemplateRenderer::render($template['body'], $variables, TemplateRenderer::escapeMarkdown(...))
            : TemplateRenderer::render($template['body'], $variables, e(...));

        return new RenderedMessage(
            key: $key,
            channel: $channel,
            subject: $plain($template['subject']),
            greeting: $plain($template['greeting']),
            body: trim($body),
            actionLabel: $plain($template['action_label']),
        );
    }
}
