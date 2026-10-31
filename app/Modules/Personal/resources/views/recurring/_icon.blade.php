{{-- Icon của khoản định kỳ: icon danh mục, hoặc chuông với khoản chỉ nhắc hạn không có danh mục. Cần $item, tùy chọn $size. --}}
@if ($item->category)
    <x-personal::category-icon :category="$item->category" :size="$size ?? 'md'" />
@else
    <span @class(['flex shrink-0 items-center justify-center bg-slate-100 text-slate-600', 'size-8 rounded-lg' => ($size ?? 'md') === 'sm', 'size-10 rounded-xl' => ($size ?? 'md') !== 'sm'])><x-ui.icon name="bell" @class(['size-4' => ($size ?? 'md') === 'sm', 'size-5' => ($size ?? 'md') !== 'sm']) /></span>
@endif
