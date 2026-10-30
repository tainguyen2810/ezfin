@php
    $sections = collect(\App\Modules\Personal\Support\AppMenu::items())->reject(fn ($item) => $item['route'] === 'personal.settings.edit');
@endphp
<x-personal::shell title="Tài khoản">
    <div class="grid max-w-3xl gap-4">
        {{-- Trên mobile thanh dưới chỉ có 4 mục: liệt kê đủ các màn hình ở đây. --}}
        <x-ui.card title="Tất cả mục" :padding="false" class="lg:hidden">
            <ul class="mt-2 divide-y divide-slate-100">
                @foreach ($sections as $item)
                    <li><a href="{{ $item['href'] }}" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-navy-900 hover:bg-slate-50"><x-ui.icon :name="$item['icon']" class="size-5 text-slate-500" /><span class="flex-1">{{ $item['label'] }}</span><x-ui.icon name="chevron-right" class="size-4 text-slate-400" /></a></li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card title="Thông tin cá nhân">
            <form method="POST" action="{{ route('personal.settings.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-ui.input name="name" label="Họ tên" :value="$user->name" autocomplete="name" required />
                <x-ui.input name="email" type="email" label="Email" :value="$user->email" autocomplete="email" required hint="Dùng để đăng nhập và nhận thư đặt lại mật khẩu." />
                <x-ui.toggle name="marketing" label="Nhận email giới thiệu từ EzFin" :checked="old('marketing', $user->marketing_consent_at !== null)"
                    description="Bản tin, công cụ và sản phẩm mới. Bỏ chọn bất kỳ lúc nào." />
                @if ($user->terms_accepted_at)
                    <p class="text-xs text-slate-500">Bạn đồng ý điều khoản sử dụng ngày {{ Format::date($user->terms_accepted_at) }}.</p>
                @endif
                <x-ui.button type="submit">Lưu thông tin</x-ui.button>
            </form>
        </x-ui.card>

        <x-ui.card title="Đổi mật khẩu">
            <form method="POST" action="{{ route('personal.settings.password') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-ui.input name="current_password" type="password" label="Mật khẩu hiện tại" autocomplete="current-password" required />
                <x-ui.input name="password" type="password" label="Mật khẩu mới" autocomplete="new-password" hint="Tối thiểu 8 ký tự, có chữ và số." required />
                <x-ui.input name="password_confirmation" type="password" label="Nhập lại mật khẩu mới" autocomplete="new-password" required />
                <x-ui.button type="submit" variant="outline">Đổi mật khẩu</x-ui.button>
                <p class="text-xs text-slate-500">Sau khi đổi, các thiết bị khác đang đăng nhập sẽ bị đăng xuất.</p>
            </form>
        </x-ui.card>

        <x-ui.card title="Dữ liệu của bạn">
            <p class="text-sm text-slate-600">Tải toàn bộ giao dịch về máy dạng CSV (mở được bằng Excel, Google Sheets).</p>
            <x-ui.button :href="route('personal.settings.export')" variant="outline" icon="arrow-down-tray" class="mt-3">Tải giao dịch (CSV)</x-ui.button>
        </x-ui.card>

        <x-ui.card title="Xóa tài khoản" class="border-red-200">
            @if ($isStaff)
                <p class="text-sm text-slate-600">Đây là tài khoản quản trị nên không tự xóa được ở đây. Hãy nhờ quản trị viên cao nhất gỡ quyền trước.</p>
            @else
                <p class="text-sm text-slate-600">Xóa vĩnh viễn tài khoản cùng toàn bộ ví, giao dịch, ngân sách, mục tiêu, tài sản và nợ. Không khôi phục được. Bạn nên tải dữ liệu về trước.</p>
                <div x-data class="mt-3">
                    <x-ui.button type="button" variant="danger" icon="trash" x-on:click="$dispatch('open-modal', 'xoa-tai-khoan')">Xóa tài khoản</x-ui.button>
                </div>
                <noscript><p class="mt-2 text-xs text-slate-500">Cần bật JavaScript để mở hộp xác nhận.</p></noscript>
                <x-ui.modal name="xoa-tai-khoan" title="Xóa vĩnh viễn tài khoản?" :show="$errors->has('delete_password')">
                    <form id="form-xoa-tai-khoan" method="POST" action="{{ route('personal.settings.destroy') }}" class="space-y-3">
                        @csrf
                        @method('DELETE')
                        <p>Toàn bộ dữ liệu của bạn sẽ bị xóa ngay và không khôi phục được. Nhập mật khẩu để xác nhận.</p>
                        <x-ui.input name="delete_password" type="password" label="Mật khẩu" autocomplete="current-password" required />
                    </form>
                    <x-slot:footer>
                        <x-ui.button variant="outline" x-on:click="$dispatch('close-modal')">Giữ tài khoản</x-ui.button>
                        <x-ui.button type="submit" form="form-xoa-tai-khoan" variant="danger" class="w-full">Xóa vĩnh viễn</x-ui.button>
                    </x-slot:footer>
                </x-ui.modal>
            @endif
        </x-ui.card>
    </div>
</x-personal::shell>
