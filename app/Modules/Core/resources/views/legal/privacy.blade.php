@php
    use App\Modules\Partner\Models\OutboundClick;
    use Laravel\Pennant\Feature;

    $company = array_filter(config('ezfin.company', []), 'filled');
    $personal = Feature::active('personal');
    $newsletter = Feature::active('newsletter');
    $months = OutboundClick::RETENTION_MONTHS;
@endphp
<x-core::legal-page title="Quyền riêng tư" updated="13/11/2026"
    description="EzFin thu thập dữ liệu cá nhân gì, để làm gì, lưu bao lâu và quyền của bạn theo Luật Bảo vệ dữ liệu cá nhân 2025.">
    <p>Chính sách này giải thích cách EzFin (ezfin.vn và app.ezfin.vn) xử lý dữ liệu cá nhân, theo Luật Bảo vệ dữ liệu cá nhân số 91/2025/QH15 (hiệu lực từ 01/01/2026) và các văn bản hướng dẫn. Trang mô tả đúng những gì website đang thu thập ở thời điểm cập nhật; khi bổ sung tính năng thu thập dữ liệu mới, chúng tôi cập nhật trang này trước khi triển khai.</p>

    <h2>1. Bên kiểm soát và xử lý dữ liệu</h2>
    <p>
        @if (isset($company['name']))
            {{ $company['name'] }}{{ isset($company['registration']) ? ' ('.$company['registration'].')' : '' }}{{ isset($company['address']) ? ', địa chỉ '.$company['address'] : '' }},
        @else
            Chủ sở hữu website EzFin (thông tin ở trang <a href="{{ route('legal.contact') }}">Liên hệ</a>),
        @endif
        quyết định mục đích và cách xử lý dữ liệu mô tả dưới đây.
        Liên hệ về dữ liệu cá nhân:
        @if (isset($company['email']))
            <a href="mailto:{{ $company['email'] }}">{{ $company['email'] }}</a>.
        @else
            trang <a href="{{ route('legal.contact') }}">Liên hệ</a>.
        @endif
    </p>

    <h2>2. Dữ liệu EzFin thu thập, mục đích và thời gian lưu</h2>
    <ul>
        <li><strong>Nhật ký máy chủ</strong> (địa chỉ IP, loại trình duyệt, thời gian, đường dẫn truy cập): để vận hành, bảo mật, xử lý lỗi. Xóa định kỳ sau thời gian cần cho vận hành và bảo mật.</li>
        <li><strong>Cookie cần thiết</strong> (phiên làm việc, chống giả mạo yêu cầu XSRF): để website hoạt động. Không dùng cho quảng cáo. Hết hạn khi đóng trình duyệt hoặc sau thời gian phiên.</li>
        <li><strong>Danh sách so sánh</strong>: lưu trong bộ nhớ phiên của trình duyệt (sessionStorage), không gửi về máy chủ, tự xóa khi đóng tab.</li>
        <li><strong>Số liệu nhập vào công cụ tính và bộ lọc</strong>: nằm trên địa chỉ trang (URL) để bạn chia sẻ được kết quả; EzFin không lưu riêng ngoài nhật ký máy chủ.</li>
        <li><strong>Lượt bấm sang đối tác</strong> (đường dẫn <code>/di/…</code>): thời điểm, liên kết được bấm, trang EzFin bạn đang xem, loại trình duyệt và mã băm một chiều của địa chỉ IP (không lưu IP gốc). Mỗi lượt bấm có một mã ngẫu nhiên gửi kèm sang đối tác. Mục đích: thống kê, chống gian lận, đối soát hoa hồng. Tự xóa sau {{ $months }} tháng.</li>
        <li><strong>Kết quả chuyển đổi do đối tác báo lại</strong>: mã lượt bấm, mã giao dịch của đối tác, trạng thái (chờ duyệt, đã duyệt, từ chối), mức hoa hồng, thời điểm. Đối tác không gửi họ tên, số điện thoại hay thông tin tài chính của bạn cho EzFin. Lưu để đối soát và kế toán; sau khi lượt bấm bị xóa ({{ $months }} tháng), dữ liệu này không còn liên kết được với trình duyệt hay địa chỉ IP nào.</li>
        @if ($newsletter)
            <li><strong>Email nhận tin</strong> (nếu bạn đăng ký báo lãi suất tăng hoặc bản tin tuần): địa chỉ email, loại thư bạn chọn, kỳ hạn theo dõi, thời điểm đồng ý và xác nhận, trang bạn đăng ký. Chỉ dùng để gửi đúng các thư bạn chọn. EzFin chỉ lưu sau khi bạn bấm xác nhận trong email; nếu không xác nhận, địa chỉ chỉ nằm trong hàng đợi gửi thư cho tới khi thư được gửi. Hủy nhận toàn bộ thì địa chỉ email bị xóa ngay.</li>
        @endif
        @if ($personal)
            <li><strong>Tài khoản ứng dụng quản lý tiền</strong> (nếu bạn đăng ký): họ tên, email, mật khẩu (chỉ lưu dạng băm), thời điểm đồng ý điều khoản và (nếu chọn) đồng ý nhận email giới thiệu, cùng dữ liệu tài chính bạn tự nhập hoặc nhập từ sao kê (ví, giao dịch, ngân sách, mục tiêu, tài sản, khoản nợ, khoản định kỳ). Dữ liệu tài chính có thể là dữ liệu cá nhân nhạy cảm; EzFin chỉ dùng để hiển thị và nhắc hạn cho chính bạn, không chia sẻ hay bán cho ngân hàng, đối tác hoặc bên quảng cáo, không ghi vào nhật ký quản trị. Lưu đến khi bạn xóa tài khoản.</li>
        @endif
        <li><strong>Thông tin bạn chủ động gửi</strong> khi liên hệ qua email: lưu để trả lời, đến khi xử lý xong và không quá thời hạn pháp luật cho phép.</li>
    </ul>
    <p>Hiện EzFin <strong>chưa</strong> dùng công cụ phân tích truy cập, cookie quảng cáo hay mã theo dõi của bên thứ ba.</p>

    <h2>3. Căn cứ xử lý</h2>
    <ul>
        <li><strong>Sự đồng ý của bạn</strong>: email nhận tin{{ $personal ? ', email giới thiệu và tài khoản ứng dụng' : '' }}. Bạn đồng ý bằng hành động rõ ràng (tích ô đồng ý, bấm xác nhận) và rút lại được bất kỳ lúc nào; việc rút lại không ảnh hưởng tới xử lý đã thực hiện trước đó.</li>
        <li><strong>Thực hiện dịch vụ bạn yêu cầu</strong>: hiển thị dữ liệu bạn nhập, chuyển bạn sang website đối tác khi bạn bấm.</li>
        <li><strong>Vận hành, bảo vệ hệ thống và nghĩa vụ pháp luật</strong>: nhật ký máy chủ, chống gian lận, lưu chứng từ đối soát, đáp ứng yêu cầu của cơ quan nhà nước có thẩm quyền.</li>
    </ul>

    <h2>4. Chia sẻ dữ liệu</h2>
    <ul>
        <li><strong>Nhà cung cấp hạ tầng</strong> (máy chủ, dịch vụ gửi email) xử lý dữ liệu thay EzFin trong phạm vi cần thiết để vận hành, theo hợp đồng và chỉ dẫn của EzFin. Dịch vụ gửi email có thể đặt máy chủ ngoài Việt Nam, nên địa chỉ email và nội dung thư có thể được chuyển ra nước ngoài để gửi; EzFin thực hiện thủ tục chuyển dữ liệu ra nước ngoài theo quy định.</li>
        <li><strong>Đối tác affiliate</strong> chỉ nhận mã lượt bấm ngẫu nhiên, không nhận email hay dữ liệu tài chính của bạn từ EzFin. Khi bạn đăng ký trên website đối tác, chính sách quyền riêng tư của bên đó được áp dụng.</li>
        <li><strong>Cơ quan nhà nước có thẩm quyền</strong> khi pháp luật yêu cầu.</li>
    </ul>
    <p>EzFin không bán dữ liệu cá nhân.</p>

    <h2>5. Quyền của bạn</h2>
    <p>Theo Luật Bảo vệ dữ liệu cá nhân, bạn có quyền được biết về việc xử lý dữ liệu; đồng ý hoặc không đồng ý, rút lại sự đồng ý; xem, chỉnh sửa, yêu cầu cung cấp dữ liệu; yêu cầu xóa, hạn chế xử lý; phản đối xử lý; khiếu nại, tố cáo, khởi kiện và yêu cầu bồi thường theo quy định của pháp luật.</p>
    <ul>
        @if ($newsletter)
            <li>Email nhận tin: đổi lựa chọn hoặc hủy nhận (xóa email) bằng link cuối mỗi thư, không cần đăng nhập.</li>
        @endif
        @if ($personal)
            <li>Ứng dụng quản lý tiền: tự sửa thông tin, rút lại đồng ý nhận email giới thiệu, tải toàn bộ giao dịch (CSV) và xóa vĩnh viễn tài khoản cùng mọi dữ liệu tài chính ở trang Tài khoản. Nhật ký đăng nhập (thời điểm, địa chỉ IP) được giữ lại một thời gian để bảo vệ an toàn hệ thống.</li>
        @endif
        <li>Cookie và bộ nhớ phiên: xóa bất kỳ lúc nào trong cài đặt trình duyệt.</li>
        <li>Yêu cầu khác: gửi qua trang <a href="{{ route('legal.contact') }}">Liên hệ</a>, ghi rõ yêu cầu và địa chỉ email liên quan. EzFin có thể cần xác minh bạn là chủ dữ liệu trước khi thực hiện, và phản hồi trong thời hạn pháp luật quy định.</li>
    </ul>

    <h2>6. Bảo mật và sự cố</h2>
    <p>Website dùng kết nối mã hóa HTTPS; mật khẩu chỉ lưu dạng băm; địa chỉ IP trong nhật ký lượt bấm chỉ lưu dạng băm có khóa bí mật; khu vực quản trị yêu cầu xác thực hai lớp, phân quyền theo vai trò và ghi nhật ký thao tác. Khi xảy ra sự cố lộ, mất dữ liệu cá nhân, EzFin thông báo cho cơ quan có thẩm quyền và người bị ảnh hưởng theo quy định.</p>

    <h2>7. Trẻ em</h2>
    <p>EzFin không hướng tới trẻ em và không cố ý thu thập dữ liệu của người dưới 16 tuổi. Nếu bạn là cha mẹ hoặc người giám hộ và cho rằng trẻ đã cung cấp dữ liệu, vui lòng liên hệ để EzFin xóa.</p>

    <h2>8. Thay đổi chính sách</h2>
    <p>Ngày cập nhật ghi ở đầu trang. Khi thay đổi mục đích xử lý hoặc loại dữ liệu cần sự đồng ý, EzFin sẽ xin lại sự đồng ý của bạn trước khi áp dụng.</p>
</x-core::legal-page>
