@php($company = config('ezfin.company'))
<x-core::legal-page title="Liên hệ"
    description="Liên hệ EzFin để góp ý, báo sai sót dữ liệu sản phẩm tài chính hoặc đề nghị hợp tác.">
    <p>Chúng tôi trân trọng mọi góp ý, đặc biệt là báo cáo số liệu chưa khớp với nguồn của ngân hàng. Khi báo sai sót, vui lòng ghi tên sản phẩm, đường dẫn trang trên EzFin và liên kết nguồn chính thức.</p>

    @if (filled($company['email']) || filled($company['phone']) || filled($company['address']))
        <h2>Thông tin liên hệ</h2>
        <ul>
            @if (filled($company['name']))<li><strong>{{ $company['name'] }}</strong></li>@endif
            @if (filled($company['registration']))<li>{{ $company['registration'] }}</li>@endif
            @if (filled($company['address']))<li>Địa chỉ: {{ $company['address'] }}</li>@endif
            @if (filled($company['email']))<li>Email: <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a></li>@endif
            @if (filled($company['phone']))<li>Điện thoại: <a href="tel:{{ preg_replace('/[^\d+]/', '', $company['phone']) }}">{{ $company['phone'] }}</a></li>@endif
        </ul>
    @else
        <p>Thông tin liên hệ chính thức đang được cập nhật.</p>
    @endif

    <h2>Lưu ý</h2>
    <ul>
        <li>EzFin không tư vấn cho trường hợp riêng và không xử lý giao dịch. Câu hỏi về tài khoản, thẻ hay khoản vay của bạn, vui lòng liên hệ trực tiếp ngân hàng.</li>
        <li>Không gửi mật khẩu, mã OTP hay số thẻ đầy đủ cho EzFin dưới bất kỳ hình thức nào.</li>
    </ul>
</x-core::legal-page>
