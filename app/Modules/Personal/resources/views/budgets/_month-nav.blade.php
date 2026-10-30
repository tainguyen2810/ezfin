{{-- Chuyển tháng: $route, $month, $label (tiêu đề). --}}
<div class="flex items-center gap-1">
    <a href="{{ route($route, ['thang' => $month->copy()->subMonth()->format('Y-m')]) }}" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100"><x-ui.icon name="chevron-left" /><span class="sr-only">Tháng trước</span></a>
    <h2 class="min-w-32 text-center text-base font-semibold">{{ $label ?? 'Tháng '.$month->format('m/Y') }}</h2>
    <a href="{{ route($route, ['thang' => $month->copy()->addMonth()->format('Y-m')]) }}" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100"><x-ui.icon name="chevron-right" /><span class="sr-only">Tháng sau</span></a>
</div>
