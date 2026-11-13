{{-- Dashboard hiệu quả đối tác. Kiểu dáng: lớp ez-report-* trong resources/css/admin.css (Filament không biên dịch Tailwind của view này). --}}
@php
    $totals = $report['totals'];
    $rate = fn ($value) => $value === null ? '—' : Format::percent($value);
    $limit = 20;
    $maxDay = max(1, collect($report['byDay'])->max('clicks'));
    $tables = [
        ['Theo ngân hàng', 'Ngân hàng', $report['byInstitution'], false],
        ['Theo link / sản phẩm', 'Link', $report['byLink'], true],
        ['Theo trang nguồn', 'Trang trên EzFin', $report['bySource'], false],
    ];
@endphp
<x-filament-panels::page>
    <x-filament::tabs label="Khoảng thời gian">
        @foreach (\App\Modules\Partner\Filament\Pages\AffiliatePerformance::PERIODS as $key => $label)
            <x-filament::tabs.item :active="$period === (string) $key" wire:click="$set('period', '{{ $key }}')">{{ $label }}</x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
    <p class="ez-report-muted">Từ {{ Format::date($from) }} đến {{ Format::date($to) }}. Click đã lọc bot; chuyển đổi tính theo ngày phát sinh, không gồm dòng bị từ chối.</p>

    <div class="ez-report-stats">
        @foreach ([
            ['Click', number_format($totals['clicks'], 0, ',', '.')],
            ['Chuyển đổi', number_format($totals['conversions'], 0, ',', '.')],
            ['Tỷ lệ chuyển đổi', $rate($totals['rate'])],
            ['Hoa hồng đã duyệt', Format::money($totals['approved'])],
            ['Hoa hồng chờ duyệt', Format::money($totals['pending'])],
        ] as [$label, $value])
            <x-filament::section compact>
                <p class="ez-report-muted">{{ $label }}</p>
                <p class="ez-report-value">{{ $value }}</p>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section heading="Click và chuyển đổi theo ngày">
        <div class="ez-report-bars" role="img" aria-label="Biểu đồ click theo ngày, chi tiết ở bảng bên dưới">
            @foreach ($report['byDay'] as $day => $values)
                <div class="ez-report-bar" style="height: {{ max(2, round($values['clicks'] / $maxDay * 100)) }}%"
                    title="{{ Format::date($day) }}: {{ $values['clicks'] }} click, {{ $values['conversions'] }} chuyển đổi">
                    @if ($values['conversions'] > 0)<span class="ez-report-dot"></span>@endif
                </div>
            @endforeach
        </div>
        <p class="ez-report-muted">Cột: số click mỗi ngày. Chấm: ngày có chuyển đổi. Rê chuột lên cột để xem số liệu.</p>
    </x-filament::section>

    @foreach ($tables as [$heading, $column, $rows, $withProduct])
        <x-filament::section :heading="$heading">
            @if ($rows->isEmpty())
                <p class="ez-report-muted">Chưa có click hay chuyển đổi trong khoảng này.</p>
            @else
                <div class="ez-report-scroll">
                    <table class="ez-report-table">
                        <thead>
                            <tr>
                                <th>{{ $column }}</th>
                                <th class="num">Click</th>
                                <th class="num">Chuyển đổi</th>
                                <th class="num">Tỷ lệ</th>
                                <th class="num">Đã duyệt</th>
                                <th class="num">Chờ duyệt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows->take($limit) as $row)
                                <tr>
                                    <td>
                                        {{ $row['label'] }}
                                        @if ($withProduct && ($row['product'] ?? null))<br><span class="ez-report-muted">{{ $row['product'] }} · {{ $row['institution'] }}</span>@endif
                                    </td>
                                    <td class="num">{{ number_format($row['clicks'], 0, ',', '.') }}</td>
                                    <td class="num">{{ number_format($row['conversions'], 0, ',', '.') }}</td>
                                    <td class="num">{{ $rate($row['rate']) }}</td>
                                    <td class="num">{{ Format::money($row['approved']) }}</td>
                                    <td class="num">{{ Format::money($row['pending']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($rows->count() > $limit)<p class="ez-report-muted">Hiện {{ $limit }} dòng đầu (xếp theo hoa hồng, chuyển đổi, click); còn {{ $rows->count() - $limit }} dòng khác.</p>@endif
            @endif
        </x-filament::section>
    @endforeach

    <p class="ez-report-muted">
        Chuyển đổi đến từ postback của mạng affiliate (<code>/postback/{mạng}?token=…&amp;click_id={click_id}&amp;transaction_id=…&amp;status=…&amp;commission=…</code>)
        hoặc file đối soát nhập ở mục "Chuyển đổi". Nhật ký click tự xóa sau {{ \App\Modules\Partner\Models\OutboundClick::RETENTION_MONTHS }} tháng; chuyển đổi được giữ để đối soát.
    </p>
</x-filament-panels::page>
