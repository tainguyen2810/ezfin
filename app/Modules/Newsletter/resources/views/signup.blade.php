{{-- Form đăng ký nhận email. Dùng: @include('newsletter::signup', ['term' => 12]) (term: kỳ hạn chọn sẵn để báo lãi tăng, null = bản tin tuần).
     Chạy khi tắt JS; lỗi và thông báo hiện tại chỗ (#nhan-tin). --}}
@php
    $term ??= null;
    $errors = $errors->getBag('newsletter');
    $terms = collect(\App\Modules\Compare\Actions\BestSavingsRates::TERMS)->mapWithKeys(fn ($t) => [$t => $t.' tháng'])->all();
@endphp
<section id="nhan-tin" class="mt-10 rounded-card bg-navy-900 p-5 text-white sm:p-6" aria-labelledby="nhan-tin-heading">
    <div class="flex items-start gap-3">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-white/10 text-highlight"><x-ui.icon name="bell-alert" class="size-5" /></span>
        <div>
            <h2 id="nhan-tin-heading" class="text-lg font-bold text-white">{{ $term ? 'Báo cho tôi khi lãi suất '.$term.' tháng tăng' : 'Nhận bản tin lãi suất mỗi tuần' }}</h2>
            <p class="mt-1 text-sm text-white/75">Miễn phí. Chỉ gửi khi mức cao nhất thị trường tăng, hoặc một bản tin sáng thứ Hai. Hủy bằng một lần bấm.</p>
        </div>
    </div>

    @if (session('newsletter'))
        <p class="mt-4 rounded-control bg-highlight px-4 py-3 text-sm font-medium text-navy-900" role="status">{{ session('newsletter') }}</p>
    @else
        <form method="POST" action="{{ route('newsletter.store') }}" class="mt-4 space-y-3" novalidate>
            @csrf
            <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="flex flex-col gap-2 sm:flex-row">
                <label for="nhan-tin-email" class="sr-only">Email</label>
                <input id="nhan-tin-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="email@cua-ban.vn"
                    class="h-11 w-full rounded-control sm:flex-1 border-0 bg-white px-4 text-navy-900 placeholder:text-slate-400 focus:ring-2 focus:ring-highlight"
                    @if ($errors->has('email')) aria-invalid="true" aria-describedby="nhan-tin-error" @endif>
                <button type="submit" class="h-11 shrink-0 rounded-full bg-highlight px-5 text-sm font-semibold text-navy-900 hover:brightness-95">Đăng ký</button>
            </div>
            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-white/85">
                <label class="flex items-center gap-2">
                    <span>Báo khi lãi tăng, kỳ hạn</span>
                    <select name="ky_han" class="h-9 rounded-control border-0 bg-white/10 py-0 pr-8 pl-3 text-sm text-white [&>option]:text-navy-900">
                        <option value="">Không</option>
                        @foreach ($terms as $value => $label)
                            <option value="{{ $value }}" @selected((string) old('ky_han', $term) === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="ban_tin" value="1" @checked(old('ban_tin', $term === null)) class="size-4 rounded border-white/40 bg-white/10 text-highlight">
                    Bản tin tuần
                </label>
            </div>
            <label class="flex items-start gap-2 text-xs text-white/75">
                <input type="checkbox" name="dong_y" value="1" @checked(old('dong_y')) required class="mt-0.5 size-4 rounded border-white/40 bg-white/10 text-highlight">
                <span>Tôi đồng ý để EzFin dùng email này chỉ để gửi các thư đã chọn, theo <a href="{{ route('legal.privacy') }}" class="underline" target="_blank">Chính sách quyền riêng tư</a>. Không chia sẻ cho ngân hàng hay đối tác.</span>
            </label>
            @if ($errors->any())
                <p id="nhan-tin-error" class="text-sm font-medium text-highlight" role="alert">{{ $errors->first() }}</p>
            @endif
        </form>
    @endif
</section>
