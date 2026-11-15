<?php

namespace App\Support\Filament;

use Filament\Forms\Components\Concerns\HasFileAttachments;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;

/**
 * Trình soạn thảo TinyMCE (bản tự lưu trữ, GPL) với thanh công cụ đầy đủ như "TinyMCE Advanced" của WordPress.
 * Ảnh chèn vào bài được tải lên qua Livewire (HasFileAttachments) rồi chèn URL tương đối /storage/...
 * Nội dung vẫn phải lọc qua ArticleHtml::sanitize khi lưu (model Article tự lọc).
 */
class TinyEditor extends Field
{
    use HasFileAttachments;
    use HasPlaceholder;

    protected string $view = 'filament.forms.tiny-editor';

    protected int $height = 600;

    public function height(int $pixels): static
    {
        $this->height = $pixels;

        return $this;
    }

    /** Cấu hình gửi sang JS (resources/js/tinymce-editor.js). */
    public function getEditorOptions(): array
    {
        return [
            'height' => $this->height,
            'placeholder' => $this->getPlaceholder(),
            'canUpload' => $this->hasFileAttachments(),
            'acceptedTypes' => $this->getFileAttachmentsAcceptedFileTypes(),
            'maxSizeKb' => $this->getFileAttachmentsMaxSize(),
        ];
    }
}
