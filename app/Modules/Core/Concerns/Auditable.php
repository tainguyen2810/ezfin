<?php

namespace App\Modules\Core\Concerns;

use App\Modules\Core\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Ghi audit log khi model được tạo, sửa, xóa. Giá trị trường nhạy cảm (mật khẩu, khóa 2FA)
 * không bao giờ được ghi: chỉ ghi nhận là "đã thay đổi". Model có thể loại thêm trường qua $auditExclude.
 *
 * @mixin Model
 */
trait Auditable
{
    /** Trường bí mật: ghi nhận có thay đổi, không ghi giá trị. */
    private static array $auditSecret = ['password', 'app_authentication_secret', 'app_authentication_recovery_codes'];

    /** Trường kỹ thuật: bỏ qua hoàn toàn. */
    private static array $auditIgnored = ['remember_token', 'created_at', 'updated_at', 'last_login_at'];

    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => $model->shouldAudit() && AuditLog::record('created', $model, null, $model->auditValues($model->getAttributes())));

        static::updated(function (Model $model) {
            if (! $model->shouldAudit()) {
                return;
            }

            $changed = $model->getChanges();
            $new = $model->auditValues($changed);

            foreach (array_intersect(array_keys($changed), self::$auditSecret) as $field) {
                $new[$field] = '[đã thay đổi]';
            }

            if ($new === []) {
                return;
            }

            $old = array_intersect_key($model->auditValues($model->getOriginal()), $new);

            AuditLog::record('updated', $model, $old, $new);
        });

        static::deleted(fn (Model $model) => $model->shouldAudit() && AuditLog::record('deleted', $model, $model->auditValues($model->getAttributes()), null));
    }

    /** Model ghi đè để bỏ qua một số bản ghi (ví dụ tài khoản khách hàng tự sửa thông tin của mình). */
    public function shouldAudit(): bool
    {
        return true;
    }

    /** Tên đối tượng hiển thị trong nhật ký; model ghi đè để đặt tên tiếng Việt. */
    public static function auditSubjectLabel(): string
    {
        return class_basename(static::class);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function auditValues(array $values): array
    {
        $excluded = [...self::$auditSecret, ...self::$auditIgnored, ...($this->auditExclude ?? [])];

        return array_diff_key($values, array_flip($excluded));
    }
}
