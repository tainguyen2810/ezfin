<x-layouts.auth title="Quên mật khẩu" subtitle="Nhập email đã đăng ký, EzFin sẽ gửi liên kết đặt lại mật khẩu.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-ui.input name="email" type="email" label="Email" autocomplete="email" required autofocus />
        <x-ui.button type="submit" class="w-full">Gửi liên kết</x-ui.button>
    </form>
    <x-slot:footer><a href="{{ route('personal.login') }}" class="font-semibold text-primary-700 hover:underline">Quay lại đăng nhập</a></x-slot:footer>
</x-layouts.auth>
