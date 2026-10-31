<x-personal::shell title="Nhập sao kê ngân hàng">
    <x-ui.card class="mx-auto max-w-xl">
        <p class="text-sm text-slate-600">Tải file sao kê từ ứng dụng hoặc internet banking của ngân hàng, rồi nhập vào ví tương ứng. Bạn sẽ được xem trước và chọn cột trước khi nhập.</p>

        <form method="POST" action="{{ route('personal.imports.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
            @csrf
            <x-ui.select name="wallet_id" label="Nhập vào ví / tài khoản" :options="$wallets" :value="$walletId" placeholder="Chọn ví" required />

            <x-ui.field label="File sao kê" for="file" :error="$errors->first('file')" required hint="CSV hoặc Excel (.xlsx), tối đa 5 MB, 2.000 dòng.">
                <input id="file" name="file" type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required
                    @if ($errors->has('file')) aria-invalid="true" aria-describedby="file-error" @endif
                    class="block w-full rounded-control border border-slate-300 bg-white text-sm text-slate-700 file:mr-3 file:border-0 file:bg-navy-900 file:px-4 file:py-2.5 file:font-semibold file:text-white hover:file:bg-navy-800">
            </x-ui.field>

            <div class="flex flex-wrap gap-2 pt-2">
                <x-ui.button type="submit" icon="arrow-up-tray" class="flex-1 sm:flex-none">Tải lên và xem trước</x-ui.button>
                <x-ui.button :href="route('personal.transactions.index')" variant="outline">Hủy</x-ui.button>
            </div>
        </form>

        <details class="mt-6 border-t border-slate-100 pt-4 text-sm text-slate-600">
            <summary class="cursor-pointer font-medium text-primary-700">Lấy file sao kê ở đâu?</summary>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>Phần lớn ngân hàng cho tải sao kê dạng Excel hoặc CSV trong internet banking, mục Tài khoản → Sao kê / Lịch sử giao dịch.</li>
                <li>File PDF chưa đọc được. Nếu chỉ có PDF, mở bằng Excel hoặc chọn xuất Excel trong ứng dụng ngân hàng.</li>
                <li>Sao kê nhiều tháng nên tách theo từng tháng cho dễ kiểm tra.</li>
            </ul>
        </details>
        <p class="mt-4 text-xs text-slate-500">File chỉ dùng để đọc giao dịch, được xóa sau khi nhập (hoặc sau 1 ngày nếu bạn không nhập). EzFin chỉ lấy ngày, nội dung và số tiền của từng giao dịch; thông tin khác trong file không được lưu.</p>
    </x-ui.card>
</x-personal::shell>
