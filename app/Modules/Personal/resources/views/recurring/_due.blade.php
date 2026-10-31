{{-- Nhãn hạn của một khoản định kỳ: hôm nay, còn N ngày, quá hạn N ngày. Cần $item, $today. --}}
@php($days = $item->daysUntilDue($today))
@if ($days === null)
    <x-ui.badge>Đã kết thúc</x-ui.badge>
@elseif ($days < 0)
    <x-ui.badge variant="danger" icon="exclamation-circle">Quá hạn {{ -$days }} ngày</x-ui.badge>
@elseif ($days === 0)
    <x-ui.badge variant="warning" icon="clock">Hôm nay</x-ui.badge>
@elseif ($days <= 7)
    <x-ui.badge variant="warning">Còn {{ $days }} ngày</x-ui.badge>
@else
    <x-ui.badge>Còn {{ $days }} ngày</x-ui.badge>
@endif
