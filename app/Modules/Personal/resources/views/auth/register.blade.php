<x-layouts.auth title="Tạo tài khoản" subtitle="Miễn phí. Dữ liệu của bạn chỉ bạn xem được và không chia sẻ cho đối tác.">
    <form method="POST" action="{{ route('personal.register') }}" class="space-y-4">
        @csrf
        <x-ui.input name="name" label="Họ tên" autocomplete="name" required autofocus />
        <x-ui.input name="email" type="email" label="Email" autocomplete="email" required />
        <x-ui.input name="password" type="password" label="Mật khẩu" autocomplete="new-password" hint="Tối thiểu 8 ký tự, có chữ và số." required />
        <x-ui.input name="password_confirmation" type="password" label="Nhập lại mật khẩu" autocomplete="new-password" required />
        <div class="space-y-2 text-sm text-slate-600">
            <label class="flex items-start gap-2">
                <input type="checkbox" name="terms" value="1" @checked(old('terms')) class="mt-0.5 size-4 rounded border-slate-300 text-primary-600" required>
                <span>Tôi đồng ý với <a href="{{ route('legal.terms') }}" class="font-medium text-primary-700 underline" target="_blank">Điều khoản sử dụng</a> và <a href="{{ route('legal.privacy') }}" class="font-medium text-primary-700 underline" target="_blank">Chính sách quyền riêng tư</a>.</span>
            </label>
            @error('terms')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            <label class="flex items-start gap-2">
                <input type="checkbox" name="marketing" value="1" @checked(old('marketing')) class="mt-0.5 size-4 rounded border-slate-300 text-primary-600">
                <span>Nhận email về kiến thức và sản phẩm tài chính mới (không bắt buộc, hủy lúc nào cũng được).</span>
            </label>
        </div>
        <x-ui.button type="submit" class="w-full">Đăng ký</x-ui.button>
    </form>
    <x-slot:footer>Đã có tài khoản? <a href="{{ route('personal.login') }}" class="font-semibold text-primary-700 hover:underline">Đăng nhập</a></x-slot:footer>
</x-layouts.auth>
