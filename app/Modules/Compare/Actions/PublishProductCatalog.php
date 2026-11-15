<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ghi danh mục sản phẩm (thẻ tín dụng, khoản vay, tài khoản thanh toán) đã được duyệt từ nguồn tự động.
 *
 * Sản phẩm khớp theo slug "<ngân hàng>-<tên>" hoặc cùng ngân hàng + loại + tên. Chưa có thì tạo (đã xuất bản: người duyệt
 * đã kiểm tra, có nguồn chính thức và ngày xác minh). Đã có thì chỉ ghi đè các trường nguồn cung cấp (giá trị trống
 * không xóa số liệu quản trị viên tự nhập), giữ nguyên tên, trạng thái, mô tả đã sửa. Ảnh là ảnh gốc của ngân hàng.
 * Sản phẩm không còn trên trang ngân hàng không bị xóa (người duyệt thấy cảnh báo và tự quyết định).
 */
class PublishProductCatalog
{
    /**
     * @param  array{slug: string, name: string, short_name: string, website: string}  $institution
     * @param  list<array{key: string, name: string, url: string, summary?: ?string, details: array<string, mixed>}>  $items
     * @param  array<string, array{0: string, 1: string}>  $images  key sản phẩm => [nội dung ảnh, đuôi file]
     * @return array{created: int, updated: int}
     */
    public function handle(array $institution, ProductType $type, array $items, array $images = []): array
    {
        return DB::transaction(function () use ($institution, $type, $items, $images) {
            $bank = FinancialInstitution::query()->where('slug', $institution['slug'])->first()
                ?? app(UpdateInstitutionProfile::class)->handle($institution);

            $created = $updated = 0;
            foreach ($items as $item) {
                $product = $this->find($bank, $type, $item);
                $isNew = $product === null;

                if ($isNew) {
                    $product = new FinancialProduct([
                        'type' => $type,
                        'name' => $item['name'],
                        'slug' => Str::limit($bank->slug.'-'.Str::slug($item['key']), 150, ''),
                        'status' => ProductStatus::Published,
                        'summary' => $item['summary'] ?? null,
                    ]);
                    $product->institution()->associate($bank);
                } elseif (blank($product->summary) && filled($item['summary'] ?? null)) {
                    $product->summary = $item['summary'];
                }

                $product->source_url = $item['url'];
                $product->source_note = 'Tự động lấy từ trang chính thức của '.$bank->short_name.', đã được quản trị viên duyệt.';
                $product->verified_at = today();
                $product->next_review_at = null;

                if (isset($images[$item['key']])) {
                    [$bytes, $extension] = $images[$item['key']];
                    $path = FinancialProduct::IMAGE_DIRECTORY.'/'.Str::limit($product->slug, 100, '').'-'.substr(sha1($bytes), 0, 10).'.'.$extension;
                    if ($path !== $product->image_path) {
                        Storage::disk('public')->put($path, $bytes, 'public');
                        $product->image_path = $path;
                    }
                }

                $product->save();
                $this->saveDetails($product, $type, $item['details']);
                $isNew ? $created++ : $updated++;
            }

            return ['created' => $created, 'updated' => $updated];
        });
    }

    /** @param  array{key: string, name: string}  $item */
    private function find(FinancialInstitution $bank, ProductType $type, array $item): ?FinancialProduct
    {
        $slug = Str::limit($bank->slug.'-'.Str::slug($item['key']), 150, '');

        return FinancialProduct::query()->where('slug', $slug)->first()
            ?? FinancialProduct::query()->where('institution_id', $bank->id)->where('type', $type)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($item['name'])])->first();
    }

    /** @param  array<string, mixed>  $details */
    private function saveDetails(FinancialProduct $product, ProductType $type, array $details): void
    {
        $relation = match ($type) {
            ProductType::CreditCard => $product->creditCard(),
            ProductType::Loan => $product->loan(),
            ProductType::BankAccount => $product->bankAccount(),
            default => throw new \InvalidArgumentException("Loại sản phẩm {$type->value} không dùng danh mục tự động."),
        };

        $detail = $relation->first() ?? $relation->make();
        $detail->fill(array_filter($details, fn ($value) => $value !== null && $value !== []));
        $detail->save();
    }
}
