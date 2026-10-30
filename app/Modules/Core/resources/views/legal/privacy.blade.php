<x-core::legal-page title="Quyền riêng tư" updated="30/10/2026"
    description="EzFin thu thập dữ liệu gì, dùng để làm gì và quyền của bạn theo Nghị định 13/2023/NĐ-CP về bảo vệ dữ liệu cá nhân.">
    <p>EzFin tôn trọng quyền riêng tư của bạn và xử lý dữ liệu cá nhân theo Nghị định 13/2023/NĐ-CP về bảo vệ dữ liệu cá nhân. Trang này mô tả đúng những gì website đang thu thập ở thời điểm cập nhật. Khi bổ sung tính năng mới (ví dụ tài khoản người dùng hay công cụ đo lường truy cập), chúng tôi cập nhật trang này trước khi triển khai.</p>

    <h2>1. Dữ liệu EzFin đang thu thập</h2>
    <ul>
        <li><strong>Nhật ký máy chủ:</strong> địa chỉ IP, loại trình duyệt, thời gian và đường dẫn truy cập. Dùng để vận hành, bảo mật và xử lý lỗi.</li>
        <li><strong>Cookie cần thiết:</strong> cookie phiên làm việc và cookie chống giả mạo yêu cầu (XSRF). Không dùng cho quảng cáo.</li>
        <li><strong>Danh sách so sánh:</strong> sản phẩm bạn chọn để so sánh được lưu trong bộ nhớ phiên của trình duyệt (sessionStorage), không gửi về máy chủ và tự xóa khi đóng tab.</li>
        <li><strong>Số liệu bạn nhập vào công cụ tính và bộ lọc:</strong> nằm trên địa chỉ trang (URL) để bạn chia sẻ được kết quả. EzFin không lưu riêng các số liệu này ngoài nhật ký máy chủ.</li>
        <li><strong>Lượt bấm sang đối tác:</strong> khi bạn bấm nút đến ngân hàng hoặc đối tác (đường dẫn bắt đầu bằng <code>/di/</code>), EzFin ghi thời điểm, liên kết được bấm, trang EzFin bạn đang xem, loại trình duyệt và một mã băm một chiều của địa chỉ IP (không lưu IP gốc). Mỗi lượt bấm có một mã ngẫu nhiên được gửi kèm sang đối tác để đối soát hoa hồng; mã này không chứa thông tin cá nhân. Dữ liệu dùng để thống kê, chống gian lận và đối soát, tự xóa sau {{ \App\Modules\Partner\Models\OutboundClick::RETENTION_MONTHS }} tháng.</li>
        @if (\Laravel\Pennant\Feature::active('personal'))
            <li><strong>Tài khoản ứng dụng quản lý tiền (app.ezfin.vn), nếu bạn đăng ký:</strong> họ tên, email, mật khẩu (chỉ lưu dạng băm), thời điểm bạn đồng ý điều khoản và (nếu chọn) đồng ý nhận email giới thiệu, cùng dữ liệu tài chính bạn tự nhập như ví, số dư. Dữ liệu này chỉ dùng để hiển thị cho chính bạn, không chia sẻ hay bán cho ngân hàng, đối tác hoặc bên quảng cáo, và không ghi vào nhật ký quản trị.</li>
        @endif
        <li><strong>Thông tin bạn chủ động gửi:</strong> khi bạn liên hệ qua email, chúng tôi lưu nội dung thư để trả lời.</li>
    </ul>
    <p>Hiện EzFin <strong>chưa</strong> dùng công cụ phân tích truy cập hay cookie quảng cáo của bên thứ ba.</p>

    <h2>2. Mục đích sử dụng</h2>
    <p>Vận hành và bảo vệ website, sửa lỗi, trả lời liên hệ và tuân thủ yêu cầu của cơ quan nhà nước có thẩm quyền. EzFin không bán dữ liệu cá nhân.</p>

    <h2>3. Chia sẻ dữ liệu</h2>
    <p>Dữ liệu chỉ được chia sẻ với nhà cung cấp hạ tầng (máy chủ, gửi email) trong phạm vi cần thiết để vận hành, hoặc khi pháp luật yêu cầu. Khi bạn bấm sang website của ngân hàng hay đối tác, chính sách quyền riêng tư của bên đó được áp dụng.</p>

    <h2>4. Thời gian lưu trữ</h2>
    <p>Nhật ký máy chủ được lưu trong thời gian cần cho vận hành và bảo mật rồi xóa định kỳ. Nhật ký lượt bấm sang đối tác tự xóa sau {{ \App\Modules\Partner\Models\OutboundClick::RETENTION_MONTHS }} tháng. Thư liên hệ được lưu đến khi xử lý xong và không quá thời hạn pháp luật cho phép.</p>

    <h2>5. Quyền của bạn</h2>
    <p>Theo Nghị định 13/2023/NĐ-CP, bạn có quyền được biết, đồng ý, truy cập, chỉnh sửa, xóa, hạn chế xử lý, phản đối xử lý dữ liệu cá nhân của mình và khiếu nại. Bạn có thể xóa cookie và bộ nhớ phiên bất kỳ lúc nào trong cài đặt trình duyệt. Để thực hiện các quyền khác, vui lòng gửi yêu cầu qua trang <a href="{{ route('legal.contact') }}">Liên hệ</a>.</p>
    @if (\Laravel\Pennant\Feature::active('personal'))
        <p>Nếu dùng ứng dụng quản lý tiền, bạn tự sửa thông tin, rút lại đồng ý nhận email, tải toàn bộ giao dịch (CSV) và xóa vĩnh viễn tài khoản cùng mọi dữ liệu tài chính tại trang Tài khoản trong ứng dụng. Nhật ký đăng nhập (thời điểm, địa chỉ IP) được giữ lại một thời gian để bảo vệ an toàn hệ thống.</p>
    @endif

    <h2>6. Bảo mật</h2>
    <p>Website dùng kết nối mã hóa HTTPS. Khu vực quản trị yêu cầu xác thực hai lớp và ghi nhật ký thao tác.</p>
</x-core::legal-page>
