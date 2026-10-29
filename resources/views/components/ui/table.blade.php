{{-- Bảng: cuộn ngang bên trong khung (trang không bị tràn), cột đầu cố định khi cuộn. --}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-card border border-slate-200 bg-white']) }} tabindex="0">
    <table class="min-w-full text-left text-sm [&_tbody_tr]:border-t [&_tbody_tr]:border-slate-100 [&_tbody_tr:hover]:bg-slate-50 [&_td]:px-4 [&_td]:py-3 [&_td:first-child]:sticky [&_td:first-child]:left-0 [&_td:first-child]:bg-inherit [&_th]:px-4 [&_th]:py-3 [&_thead]:bg-slate-50 [&_thead]:text-xs [&_thead]:font-semibold [&_thead]:uppercase [&_thead]:tracking-wide [&_thead]:text-slate-500 [&_tbody_tr]:bg-white">
        {{ $slot }}
    </table>
</div>
