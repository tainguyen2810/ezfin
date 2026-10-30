{{-- Dòng trạng thái của mục tiêu: hạn, số cần để dành mỗi tháng. --}}
@php($months = $goal->monthsLeft())
@if ($goal->isCompleted())
    <span class="font-medium text-income">Đã đạt mục tiêu</span>
@elseif ($months === null)
    Không đặt hạn
@elseif ($months === 0)
    <span class="font-medium text-expense">Đã quá hạn {{ Format::date($goal->deadline) }}</span>
@else
    Còn {{ $months }} tháng · cần {{ Format::money($goal->monthlyNeeded()) }}/tháng
@endif
