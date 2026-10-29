<?php

namespace App\Modules\Core\Filament\Schemas;

use App\Modules\Core\Models\SeoMeta;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

/**
 * Mục "SEO" trong form của model dùng HasSeoMeta. Chỉ tạo bản ghi seo_meta khi có ít nhất một ô được điền.
 */
class SeoFields
{
    public static function section(): Section
    {
        return Section::make('SEO')
            ->description('Để trống để dùng tiêu đề, mô tả và ảnh tự sinh.')
            ->relationship('seoMeta', condition: fn (?array $state) => filled($state['title'] ?? null)
                || filled($state['description'] ?? null)
                || filled($state['og_image_path'] ?? null)
                || ($state['noindex'] ?? false))
            ->collapsed()
            ->schema([
                TextInput::make('title')->label('Tiêu đề SEO')->maxLength(70)
                    ->helperText('Hiện trên Google và khi chia sẻ, nên 50–60 ký tự. Dùng nguyên văn, không thêm "| EzFin".'),
                Textarea::make('description')->label('Mô tả SEO')->rows(2)->maxLength(170)
                    ->helperText('Nên 120–160 ký tự.'),
                FileUpload::make('og_image_path')->label('Ảnh chia sẻ (Facebook, Zalo)')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                    ->maxSize(2048)
                    ->disk('public')->directory(SeoMeta::IMAGE_DIRECTORY)->visibility('public')
                    ->helperText('1200 × 630 px. Không chọn thì dùng ảnh bìa hoặc ảnh mặc định.'),
                Toggle::make('noindex')->label('Không cho công cụ tìm kiếm lập chỉ mục')
                    ->helperText('Trang vẫn xem được nhưng không vào sitemap và có thẻ noindex.'),
            ]);
    }
}
