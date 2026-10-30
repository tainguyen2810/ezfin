<x-layouts.auth title="Đặt lại mật khẩu">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-ui.input name="email" type="email" label="Email" :value="$email" autocomplete="email" required />
        <x-ui.input name="password" type="password" label="Mật khẩu mới" autocomplete="new-password" hint="Tối thiểu 8 ký tự, có chữ và số." required />
        <x-ui.input name="password_confirmation" type="password" label="Nhập lại mật khẩu mới" autocomplete="new-password" required />
        <x-ui.button type="submit" class="w-full">Đặt lại mật khẩu</x-ui.button>
    </form>
</x-layouts.auth>
