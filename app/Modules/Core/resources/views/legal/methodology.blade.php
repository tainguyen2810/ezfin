<x-core::legal-page title="Phương pháp đánh giá" updated="29/10/2026"
    description="Cách EzFin thu thập, xác minh và xếp hạng lãi suất tiết kiệm, thẻ tín dụng, tài khoản ngân hàng. Không chấm sao, không xếp theo hoa hồng.">
    <p>Trang này mô tả cách EzFin lấy dữ liệu, kiểm tra và sắp xếp sản phẩm. Mục tiêu là để bạn tự kiểm chứng được mọi con số trên website.</p>

    <h2>Nguồn dữ liệu</h2>
    <ul>
        <li>Dữ liệu lấy từ trang chính thức của ngân hàng và tổ chức tài chính: biểu lãi suất, biểu phí, điều khoản sản phẩm. Mỗi sản phẩm hiển thị liên kết tới nguồn.</li>
        <li>Mỗi sản phẩm có <strong>ngày xác minh</strong>, là ngày biên tập viên đối chiếu số liệu với nguồn. Sản phẩm thiếu nguồn hoặc ngày xác minh không được xuất bản.</li>
        <li>Sản phẩm được hẹn kiểm tra lại sau {{ \App\Modules\Compare\Models\FinancialProduct::REVIEW_INTERVAL_MONTHS }} tháng kể từ ngày xác minh, sớm hơn nếu ngân hàng công bố thay đổi.</li>
        <li>EzFin không tự động thu thập (scrape) dữ liệu từ website khác khi chưa kiểm tra điều khoản sử dụng của website đó.</li>
    </ul>

    <h2>Lãi suất tiết kiệm</h2>
    <ul>
        <li>Bạn nhập số tiền và kỳ hạn. EzFin lấy biểu lãi suất mới nhất đang có hiệu lực của từng ngân hàng, đúng kỳ hạn và đúng bậc số tiền bạn gửi.</li>
        <li>Danh sách xếp theo <strong>tiền lãi bạn thực nhận</strong> khi đáo hạn, tính theo lãi đơn: số tiền × lãi suất năm × số tháng ÷ 12, làm tròn đến đồng. Lãi suất bằng nhau thì tiền lãi bằng nhau.</li>
        <li>Mỗi dòng ghi kênh gửi (tại quầy hay trực tuyến), hình thức lĩnh lãi và ngày hiệu lực của biểu lãi suất.</li>
    </ul>

    <h2>Thẻ tín dụng</h2>
    <p>Mặc định sắp xếp theo phí thường niên thấp. Bạn có thể đổi sang: {{ collect(\App\Modules\Compare\Support\CreditCardFilters::SORTS)->except(\App\Modules\Compare\Support\CreditCardFilters::DEFAULT_SORT)->map(fn ($label) => mb_strtolower($label))->join(', ', ' hoặc ') }}. Bộ lọc theo ngân hàng, mức phí, nhu cầu và thu nhập chỉ loại bớt thẻ, không đổi thứ tự.</p>

    <h2>Tài khoản ngân hàng</h2>
    <p>Mặc định sắp xếp theo phí quản lý tài khoản thấp. Bạn có thể đổi sang: {{ collect(\App\Modules\Compare\Support\BankAccountFilters::SORTS)->except(\App\Modules\Compare\Support\BankAccountFilters::DEFAULT_SORT)->map(fn ($label) => mb_strtolower($label))->join(', ', ' hoặc ') }}.</p>

    <h2>Những điều EzFin không làm</h2>
    <ul>
        <li>Không chấm điểm sao hay đăng đánh giá của người dùng không có thật.</li>
        <li>Đối tác trả hoa hồng không được xếp cao hơn: thứ tự chỉ dựa vào tiêu chí bạn chọn. Xem <a href="{{ route('legal.disclosure') }}">Công bố liên kết</a>.</li>
        <li>Không khuyến nghị sản phẩm cho trường hợp riêng của bạn. Thông tin chỉ để tham khảo; hãy xác nhận với ngân hàng trước khi quyết định.</li>
    </ul>

    <h2>Công cụ tính</h2>
    <p>Mỗi công cụ ghi rõ công thức, giả định và cách làm tròn ngay dưới kết quả. Tiền được tính bằng số nguyên đồng để tránh sai số làm tròn. Kết quả là ước tính; số thực tế phụ thuộc hợp đồng của bạn với ngân hàng.</p>

    <h2>Báo sai sót</h2>
    <p>Nếu thấy số liệu khác với nguồn, vui lòng báo qua trang <a href="{{ route('legal.contact') }}">Liên hệ</a>, kèm tên sản phẩm và liên kết nguồn. Chúng tôi kiểm tra và cập nhật ngày xác minh sau khi sửa.</p>
</x-core::legal-page>
