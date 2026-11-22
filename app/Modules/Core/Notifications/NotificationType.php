<?php

namespace App\Modules\Core\Notifications;

use InvalidArgumentException;

/**
 * Một loại thông báo do module khai báo: tên, người nhận, kênh hỗ trợ, biến chèn được và mẫu mặc định.
 * Mẫu mặc định nằm trong code; quản trị viên sửa thì bản sửa lưu ở bảng notification_templates và đè lên mặc định.
 */
final class NotificationType
{
    /** @var list<Channel> */
    public readonly array $defaultChannels;

    /**
     * @param  array<string, array{subject: string, greeting?: string, body: string, action_label?: string}>  $templates  Mẫu mặc định theo kênh (khóa = Channel::value).
     * @param  array<string, array{0: string, 1: string}>  $variables  Biến chèn được: tên => [mô tả, giá trị mẫu để xem trước].
     * @param  list<Channel>|null  $defaultChannels  Kênh bật mặc định; null = mọi kênh có mẫu.
     * @param  list<string>  $defaultRoles  Vai trò nhận mặc định (chỉ thông báo quản trị).
     * @param  bool  $canDisable  false = thông báo bắt buộc (ví dụ thư xác nhận), không tắt được.
     * @param  int|null  $defaultRepeatHours  Khoảng nhắc lại khi lỗi còn tiếp diễn; null = loại này không nhắc lại.
     * @param  string|null  $fixedParts  Phần thư hệ thống luôn tự thêm, không sửa được (bảng số liệu, link hủy...), hiện cho quản trị viên biết.
     */
    public function __construct(
        public readonly string $key,
        public readonly Audience $audience,
        public readonly string $label,
        public readonly string $description,
        public readonly array $templates,
        public readonly array $variables = [],
        ?array $defaultChannels = null,
        public readonly array $defaultRoles = [],
        public readonly bool $canDisable = true,
        public readonly ?int $defaultRepeatHours = null,
        public readonly ?string $fixedParts = null,
    ) {
        foreach (array_keys($templates) as $channel) {
            if (Channel::tryFrom($channel) === null) {
                throw new InvalidArgumentException("Kênh không hợp lệ: $channel");
            }
        }

        $this->defaultChannels = $defaultChannels ?? $this->channels();
    }

    /** @return list<Channel> Kênh loại này hỗ trợ. */
    public function channels(): array
    {
        return array_map(fn (string $channel) => Channel::from($channel), array_keys($this->templates));
    }

    public function supports(Channel $channel): bool
    {
        return isset($this->templates[$channel->value]);
    }

    /** @return array{subject: string, greeting: string, body: string, action_label: string} */
    public function defaultTemplate(Channel $channel): array
    {
        $template = $this->templates[$channel->value] ?? throw new InvalidArgumentException("{$this->key} không hỗ trợ kênh {$channel->value}");

        return ['subject' => $template['subject'], 'greeting' => $template['greeting'] ?? '', 'body' => $template['body'], 'action_label' => $template['action_label'] ?? ''];
    }

    /** @return array<string, string> Giá trị mẫu của các biến, dùng để xem trước và gửi thử. */
    public function samples(): array
    {
        return array_map(fn (array $variable) => $variable[1], $this->variables);
    }
}
