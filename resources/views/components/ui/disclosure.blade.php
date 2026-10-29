{{-- Công bố liên kết: bắt buộc trên mọi trang có nút đến đối tác. --}}
<p {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-lg bg-slate-100 px-3 py-2 text-xs text-slate-600']) }}>
    <x-ui.icon name="information-circle" class="mt-px size-4 shrink-0" />
    <span>
        Một số liên kết trên trang là liên kết đối tác: EzFin có thể nhận hoa hồng khi bạn đăng ký, không làm tăng chi phí của bạn và không ảnh hưởng tới thứ tự so sánh.
        @if (Route::has('legal.disclosure'))
            <a href="{{ route('legal.disclosure') }}" class="font-medium underline">Tìm hiểu thêm</a>
        @endif
    </span>
</p>
