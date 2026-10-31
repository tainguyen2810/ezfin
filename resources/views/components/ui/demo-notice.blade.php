{{-- Dải báo đang chạy dữ liệu demo (lệnh ezfin:demo). Tự ẩn khi đã xóa dữ liệu demo. --}}
@props(['show' => null])
@if ($show ?? \App\Support\Demo\DemoManifest::active())
    <p {{ $attributes->merge(['class' => 'bg-highlight px-4 py-1.5 text-center text-xs font-medium text-navy-900']) }} role="note">
        Bản demo: ngân hàng, lãi suất, biểu phí, bài viết và tác giả là dữ liệu giả lập để duyệt giao diện, không dùng để ra quyết định.
    </p>
@endif
