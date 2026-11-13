@php
    // Danh sách lấy từ liên kết đối tác đang bật (module Partner), không ghi tay.
    $partners = class_exists(\App\Modules\Partner\Actions\PartnerLinks::class) ? app(\App\Modules\Partner\Actions\PartnerLinks::class)->activeInstitutions() : [];
@endphp
<x-core::legal-page title="Công bố liên kết" updated="13/11/2026"
    description="EzFin có thể nhận hoa hồng khi bạn mở sản phẩm qua liên kết đối tác. Hoa hồng không ảnh hưởng thứ tự xếp hạng và bạn không phải trả thêm phí.">
    <p>EzFin miễn phí cho người dùng. Để duy trì website, EzFin có thể nhận hoa hồng từ ngân hàng, tổ chức tài chính hoặc mạng tiếp thị liên kết (affiliate) khi bạn mở sản phẩm qua liên kết trên website. Đó là liên kết đối tác.</p>

    <h2>Cách EzFin kiếm tiền</h2>
    <ul>
        <li><strong>Hoa hồng theo kết quả:</strong> đối tác trả cho EzFin khi bạn bấm nút đối tác và hoàn tất đăng ký (mở thẻ, mở tài khoản, gửi tiết kiệm...) và được đối tác duyệt. Mức hoa hồng khác nhau theo đối tác và sản phẩm.</li>
        <li>EzFin <strong>không</strong> nhận tiền để đăng bài, để đưa sản phẩm lên đầu bảng, hay để sửa số liệu. EzFin không bán dữ liệu cá nhân của bạn.</li>
    </ul>

    <h2>Cam kết của EzFin</h2>
    <ul>
        <li><strong>Không ảnh hưởng xếp hạng.</strong> Thứ tự sản phẩm chỉ dựa vào tiêu chí bạn chọn, như tiền lãi, phí hay điều kiện, theo <a href="{{ route('legal.methodology') }}">Phương pháp đánh giá</a>. Sản phẩm không có liên kết đối tác vẫn được so sánh như mọi sản phẩm khác.</li>
        <li><strong>Không phải trả thêm.</strong> Lãi suất, phí và ưu đãi khi bạn mở qua EzFin giống như khi bạn mở trực tiếp với ngân hàng.</li>
        <li><strong>Đánh dấu rõ ràng.</strong> Trang nào có liên kết đối tác đều hiển thị thông báo công bố liên kết. Liên kết đối tác đi qua đường dẫn <code>ezfin.vn/di/…</code> và được gắn thuộc tính <code>rel="sponsored"</code> theo hướng dẫn của công cụ tìm kiếm.</li>
        <li><strong>Nội dung độc lập.</strong> Bài viết và so sánh do biên tập viên EzFin thực hiện. Đối tác không được duyệt hay sửa nội dung trước khi đăng.</li>
    </ul>

    <h2>Đối tác đang có liên kết</h2>
    @if ($partners)
        <p>Hiện EzFin có liên kết đối tác với sản phẩm của: {{ implode(', ', $partners) }}. Danh sách tự cập nhật khi liên kết được bật hoặc tắt.</p>
    @else
        <p>Hiện EzFin chưa có liên kết đối tác nào đang hoạt động.</p>
    @endif

    <h2>EzFin đo lường gì khi bạn bấm liên kết đối tác</h2>
    <p>Khi bạn bấm nút đối tác, EzFin ghi một lượt bấm (thời điểm, liên kết, trang EzFin bạn đang xem, loại trình duyệt, mã băm một chiều của địa chỉ IP) và gửi kèm một mã ngẫu nhiên sang đối tác. Nếu bạn đăng ký thành công, đối tác báo lại cho EzFin mã đó cùng trạng thái và mức hoa hồng, không kèm họ tên, số điện thoại hay thông tin tài chính của bạn. Chi tiết ở <a href="{{ route('legal.privacy') }}">Quyền riêng tư</a>.</p>

    <h2>Thông tin chỉ để tham khảo</h2>
    <p>EzFin không phải ngân hàng và không tư vấn tài chính cho trường hợp riêng của bạn. Lãi suất, phí và điều kiện có thể thay đổi; vui lòng đọc kỹ điều khoản và xác nhận với tổ chức phát hành trước khi quyết định. Xem <a href="{{ route('legal.disclaimer') }}">Miễn trừ trách nhiệm</a>.</p>

    <p>Có câu hỏi về liên kết đối tác? Xem trang <a href="{{ route('legal.contact') }}">Liên hệ</a>.</p>
</x-core::legal-page>
