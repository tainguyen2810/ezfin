{{-- Nút ghi nhận / bỏ qua kỳ đang đến hạn của khoản chỉ nhắc. Cần $item. --}}
<div class="flex flex-wrap gap-2">
    <form method="POST" action="{{ route('personal.recurring.record', $item) }}">
        @csrf
        <input type="hidden" name="due" value="{{ $item->next_due_on->toDateString() }}">
        <x-ui.button type="submit" size="sm" icon="check">{{ $item->type->isInflow() ? 'Đã nhận' : 'Đã trả' }}</x-ui.button>
    </form>
    @if ($item->mode->recordsTransaction())
    <form method="POST" action="{{ route('personal.recurring.skip', $item) }}">
        @csrf
        <input type="hidden" name="due" value="{{ $item->next_due_on->toDateString() }}">
        <x-ui.button type="submit" size="sm" variant="outline">Bỏ qua kỳ này</x-ui.button>
    </form>
    @endif
</div>
