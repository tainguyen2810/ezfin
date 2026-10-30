<x-layouts.auth title="Đăng nhập" subtitle="Quản lý thu chi, ngân sách và mục tiêu tài chính của bạn.">
    <form method="POST" action="{{ route('personal.login') }}" class="space-y-4">
        @csrf
        <x-ui.input name="email" type="email" label="Email" autocomplete="email" required autofocus />
        <x-ui.input name="password" type="password" label="Mật khẩu" autocomplete="current-password" required />
        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-slate-600">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-slate-300 text-primary-600"> Ghi nhớ đăng nhập
            </label>
            <a href="{{ route('password.request') }}" class="font-medium text-primary-700 hover:underline">Quên mật khẩu?</a>
        </div>
        <x-ui.button type="submit" class="w-full">Đăng nhập</x-ui.button>
    </form>
    <x-slot:footer>Chưa có tài khoản? <a href="{{ route('personal.register') }}" class="font-semibold text-primary-700 hover:underline">Đăng ký miễn phí</a></x-slot:footer>
</x-layouts.auth>
