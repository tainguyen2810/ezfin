<?php

namespace App\Modules\Content\Support;

/**
 * Bộ bài viết kiến thức khởi đầu do Ban biên tập EzFin soạn (nội dung gốc, tham khảo chủ đề của các trang
 * tài chính cá nhân và văn bản chính thức). Dùng cho `php artisan ezfin:starter-content`, chạy được trên production.
 * Số liệu pháp lý kiểm tra ngày 15/11/2026; sửa ở đây trước khi chạy lại cho website mới.
 */
final class StarterContent
{
    public const VERIFIED_ON = '15/11/2026';

    /** slug => [tên, mô tả] */
    public const CATEGORIES = [
        'tiet-kiem' => ['Tiết kiệm', 'Gửi tiết kiệm, lãi suất, kỳ hạn và bảo hiểm tiền gửi.'],
        'the-tin-dung' => ['Thẻ tín dụng', 'Cách chọn, dùng thẻ và tránh phí, lãi không cần thiết.'],
        'vay' => ['Vay và trả góp', 'Hiểu đúng lãi suất, cách tính và chi phí thật của khoản vay.'],
        'quan-ly-tien' => ['Quản lý tiền', 'Lập ngân sách, quỹ dự phòng, trả nợ và thói quen tài chính.'],
        'an-toan-ngan-hang' => ['An toàn ngân hàng số', 'Bảo vệ tài khoản, nhận diện lừa đảo và quy định xác thực giao dịch.'],
        'vang-ngoai-te' => ['Vàng và ngoại tệ', 'Đọc bảng giá vàng, tỷ giá và các chi phí khi mua bán.'],
    ];

    public const AUTHOR = [
        'slug' => 'ban-bien-tap-ezfin',
        'name' => 'Ban biên tập EzFin',
        'job_title' => 'Ban biên tập',
        'expertise' => 'Tiết kiệm, thẻ tín dụng, khoản vay, quản lý tiền',
        'bio' => 'Nhóm biên tập nội dung kiến thức của EzFin. Mỗi bài dẫn nguồn từ văn bản pháp luật, cơ quan nhà nước hoặc biểu phí công bố của ngân hàng, ghi ngày kiểm tra và được cập nhật khi quy định thay đổi. EzFin không đưa ra khuyến nghị đầu tư cá nhân hóa.',
    ];

    /**
     * @return list<array{category: string, title: string, slug: string, excerpt: string, body: string,
     *     faq: list<array{question: string, answer: string}>, references: list<array{title: string, url?: string}>}>
     */
    public static function articles(): array
    {
        $sbv = ['title' => 'Ngân hàng Nhà nước Việt Nam', 'url' => 'https://www.sbv.gov.vn'];
        $div = ['title' => 'Bảo hiểm tiền gửi Việt Nam', 'url' => 'https://div.gov.vn'];
        $tt05 = ['title' => 'Thông tư 05/2026/TT-NHNN về hạn mức trả tiền bảo hiểm (áp dụng từ 13/7/2026)', 'url' => 'https://thitruongtaichinhtiente.vn/nang-han-muc-tra-bao-hiem-tien-gui-toi-da-len-350-trieu-dong-tu-ngay-13-7-2026-84306.html'];
        $tt18 = ['title' => 'Thông tư 18/2024/TT-NHNN quy định về hoạt động thẻ ngân hàng', 'url' => 'https://luatvietnam.vn/tai-chinh/thong-tu-18-2024-tt-nhnn-cua-ngan-hang-nha-nuoc-viet-nam-quy-dinh-ve-hoat-dong-the-ngan-hang-358546-d1.html'];
        $qd2345 = ['title' => 'Quyết định 2345/QĐ-NHNN về giải pháp an toàn, bảo mật trong thanh toán trực tuyến', 'url' => 'https://luatvietnam.vn/tin-van-ban-moi/tu-2024-chuyen-khoan-tren-10-trieu-dong-phai-xac-thuc-sinh-trac-hoc-186-96491-article.html'];
        $pit = ['title' => 'Luật Thuế thu nhập cá nhân 2025 (hiệu lực 01/7/2026)', 'url' => 'https://hatinh.gov.vn/vi/bai-viet/21-khoan-thu-nhap-duoc-mien-thue-thu-nhap-ca-nhan-tu-nam-2026'];

        return [
            [
                'category' => 'tiet-kiem',
                'title' => 'Bảo hiểm tiền gửi nâng lên 350 triệu đồng: người gửi tiết kiệm cần biết gì',
                'slug' => 'bao-hiem-tien-gui-350-trieu-dong',
                'excerpt' => 'Từ 13/7/2026, hạn mức trả tiền bảo hiểm tiền gửi tăng từ 125 triệu lên 350 triệu đồng cho mỗi người tại mỗi ngân hàng. Bài này giải thích mức đó bảo vệ gì, không bảo vệ gì và cách tận dụng.',
                'body' => '<p>Bảo hiểm tiền gửi là lưới an toàn cuối cùng cho người gửi tiền: nếu một tổ chức tín dụng tham gia bảo hiểm tiền gửi mất khả năng chi trả, tổ chức bảo hiểm tiền gửi sẽ trả tiền cho người gửi trong hạn mức luật định.</p>
<h2>Hạn mức mới là bao nhiêu</h2>
<p>Theo Thông tư 05/2026/TT-NHNN hướng dẫn Luật Bảo hiểm tiền gửi năm 2025, từ ngày <strong>13/7/2026</strong> hạn mức trả tiền bảo hiểm tối đa là <strong>350 triệu đồng</strong>, gồm cả gốc và lãi, cho <strong>mỗi người tại mỗi tổ chức tham gia bảo hiểm tiền gửi</strong>. Mức cũ là 125 triệu đồng theo Quyết định 32/2021/QĐ-TTg.</p>
<h2>"Mỗi người tại mỗi tổ chức" nghĩa là gì</h2>
<p>Hạn mức tính trên tổng tiền gửi được bảo hiểm của bạn tại một ngân hàng, không tính theo từng sổ. Nếu bạn có ba sổ tại cùng một ngân hàng, tổng ba sổ được bảo hiểm tối đa 350 triệu đồng. Nếu bạn gửi ở hai ngân hàng khác nhau, mỗi nơi được tính riêng.</p>
<h2>Tận dụng thế nào</h2>
<ul><li>Với khoản tiền lớn, có thể chia ra nhiều ngân hàng để phần lớn số tiền nằm trong hạn mức được bảo hiểm.</li><li>Kiểm tra ngân hàng có niêm yết Chứng nhận tham gia bảo hiểm tiền gửi tại quầy hoặc trên website.</li><li>Giữ chứng từ tiền gửi (sổ, hợp đồng, xác nhận trên ứng dụng) để đối chiếu khi cần.</li></ul>
<h2>Không phải mọi sản phẩm đều được bảo hiểm</h2>
<p>Bảo hiểm tiền gửi áp dụng cho tiền gửi theo quy định của luật. Các sản phẩm như hợp đồng bảo hiểm nhân thọ, chứng chỉ quỹ, trái phiếu doanh nghiệp bán qua ngân hàng <strong>không phải tiền gửi</strong> và không thuộc phạm vi này. Hãy đọc kỹ tên hợp đồng trước khi ký.</p>
<p>Muốn so sánh nơi gửi, xem <a href="/lai-suat-tiet-kiem">bảng lãi suất tiết kiệm</a> được EzFin cập nhật kèm ngày công bố của từng ngân hàng.</p>',
                'faq' => [
                    ['question' => 'Hạn mức bảo hiểm tiền gửi hiện nay là bao nhiêu?', 'answer' => 'Từ 13/7/2026 là tối đa 350 triệu đồng, gồm cả gốc và lãi, cho mỗi người tại mỗi tổ chức tham gia bảo hiểm tiền gửi.'],
                    ['question' => 'Gửi nhiều sổ ở cùng một ngân hàng có được bảo hiểm riêng từng sổ không?', 'answer' => 'Không. Hạn mức tính trên tổng tiền gửi được bảo hiểm của một người tại một ngân hàng.'],
                    ['question' => 'Bảo hiểm nhân thọ mua qua ngân hàng có được bảo hiểm tiền gửi không?', 'answer' => 'Không. Đó là hợp đồng bảo hiểm, không phải tiền gửi.'],
                ],
                'references' => [$tt05, $div],
            ],
            [
                'category' => 'tiet-kiem',
                'title' => 'Cách so sánh lãi suất tiết kiệm giữa các ngân hàng cho đúng',
                'slug' => 'cach-so-sanh-lai-suat-tiet-kiem',
                'excerpt' => 'Con số lãi suất cao nhất chưa chắc là lựa chọn tốt nhất. Cần so cùng kỳ hạn, cùng kênh gửi, cùng cách nhận lãi và xem ngày ngân hàng công bố biểu lãi.',
                'body' => '<p>Mỗi ngân hàng công bố biểu lãi suất riêng và có thể thay đổi nhiều lần trong tháng. Để so sánh công bằng, hãy đặt các con số vào cùng điều kiện.</p>
<h2>Năm điều kiện cần giống nhau</h2>
<ol><li><strong>Kỳ hạn:</strong> so 6 tháng với 6 tháng, 12 tháng với 12 tháng.</li><li><strong>Kênh gửi:</strong> gửi online thường có lãi cao hơn gửi tại quầy ở cùng ngân hàng.</li><li><strong>Cách nhận lãi:</strong> lãi cuối kỳ thường cao hơn một chút so với lãi hằng tháng hoặc trả trước.</li><li><strong>Mức tiền:</strong> một số ngân hàng có bậc lãi theo số tiền gửi.</li><li><strong>Ngày hiệu lực:</strong> biểu lãi cũ có thể đã được thay.</li></ol>
<h2>Đọc bảng lãi suất trên EzFin</h2>
<p>Bảng <a href="/lai-suat-tiet-kiem">lãi suất tiết kiệm</a> hiển thị mức đang áp dụng theo lần công bố mới nhất của từng ngân hàng, kèm nguồn và ngày cập nhật. Bạn có thể xem riêng từng kỳ hạn, ví dụ <a href="/lai-suat-tiet-kiem/12-thang">lãi suất 12 tháng</a>.</p>
<h2>Chênh lệch nhỏ có đáng không</h2>
<p>Với 300.000.000 đ gửi 12 tháng, chênh 0,3 điểm % tương đương khoảng 900.000 đ tiền lãi. Hãy cân nhắc cả sự thuận tiện, chất lượng ứng dụng và việc tổng tiền gửi tại mỗi ngân hàng có nằm trong hạn mức bảo hiểm tiền gửi hay không.</p>
<p>Dùng công cụ <a href="/cong-cu/lai-tien-gui">Tính lãi tiền gửi</a> để ra số tiền lãi cụ thể trước khi quyết định.</p>',
                'faq' => [
                    ['question' => 'Gửi online có lãi cao hơn gửi tại quầy không?', 'answer' => 'Ở nhiều ngân hàng có, vì chi phí phục vụ thấp hơn. Hãy xem cột lãi online trong biểu lãi của từng ngân hàng.'],
                    ['question' => 'Nên chọn lãi cuối kỳ hay lãi hằng tháng?', 'answer' => 'Nếu không cần dòng tiền hằng tháng, lãi cuối kỳ thường cho tổng tiền lãi cao hơn một chút.'],
                ],
                'references' => [$sbv],
            ],
            [
                'category' => 'tiet-kiem',
                'title' => 'Chia sổ tiết kiệm theo bậc thang kỳ hạn để vừa có lãi vừa có tiền dùng',
                'slug' => 'chia-so-tiet-kiem-bac-thang-ky-han',
                'excerpt' => 'Thay vì gửi một sổ lớn, chia thành nhiều sổ đáo hạn so le giúp bạn luôn có một phần tiền sắp đến hạn mà vẫn hưởng lãi kỳ hạn dài.',
                'body' => '<p>Rút tiết kiệm trước hạn thường chỉ được hưởng lãi không kỳ hạn rất thấp. Cách "bậc thang kỳ hạn" giúp hạn chế điều đó.</p>
<h2>Cách làm</h2>
<p>Giả sử bạn có 240.000.000 đ. Thay vì gửi một sổ 12 tháng, bạn chia làm bốn sổ 60.000.000 đ với kỳ hạn 3, 6, 9 và 12 tháng. Khi sổ 3 tháng đáo hạn, nếu chưa cần tiền, bạn gửi lại kỳ hạn 12 tháng. Lặp lại với các sổ sau. Sau một năm, cứ mỗi quý lại có một sổ đến hạn, trong khi phần lớn tiền đã ở kỳ hạn dài.</p>
<h2>Ưu điểm</h2>
<ul><li>Luôn có tiền sắp đáo hạn để dùng khi cần, không phải phá cả sổ lớn.</li><li>Giảm rủi ro chọn sai thời điểm khi lãi suất thay đổi.</li><li>Dễ chia sang nhiều ngân hàng để nằm trong hạn mức bảo hiểm tiền gửi.</li></ul>
<h2>Lưu ý</h2>
<p>Bậc thang không thay thế quỹ dự phòng. Tiền cho chi tiêu bất ngờ trong vài tuần nên để ở tài khoản thanh toán hoặc sổ kỳ hạn rất ngắn. Theo dõi ngày đáo hạn của từng sổ, vì nhiều ngân hàng tự tái tục theo lãi suất tại thời điểm đó.</p>
<p>Thử các phương án với công cụ <a href="/cong-cu/lai-tien-gui">Tính lãi tiền gửi</a> và <a href="/cong-cu/tiet-kiem-dinh-ky">Tính tiết kiệm định kỳ</a>.</p>',
                'faq' => [
                    ['question' => 'Bậc thang kỳ hạn có làm giảm tiền lãi không?', 'answer' => 'Năm đầu tổng lãi có thể thấp hơn một chút so với gửi hết kỳ hạn dài, nhưng bạn đổi lại được sự linh hoạt và tránh mất lãi khi phải rút trước hạn.'],
                ],
                'references' => [$sbv, $div],
            ],
            [
                'category' => 'tiet-kiem',
                'title' => 'Lãi tiền gửi tiết kiệm có phải nộp thuế thu nhập cá nhân không',
                'slug' => 'lai-tien-gui-co-phai-nop-thue-tncn',
                'excerpt' => 'Lãi tiền gửi tại tổ chức tín dụng tiếp tục nằm trong nhóm thu nhập được miễn thuế thu nhập cá nhân theo Luật Thuế TNCN 2025.',
                'body' => '<p>Đây là câu hỏi thường gặp mỗi khi luật thuế thay đổi. Câu trả lời ngắn: lãi tiền gửi tại ngân hàng hiện <strong>được miễn thuế thu nhập cá nhân</strong>.</p>
<h2>Căn cứ</h2>
<p>Luật Thuế thu nhập cá nhân 2025, có hiệu lực từ 01/7/2026, giữ quy định miễn thuế đối với <strong>lãi tiền gửi tại tổ chức tín dụng</strong>. Quy định này cũng đã có trong luật trước đó.</p>
<h2>Những khoản khác cần phân biệt</h2>
<ul><li>Lãi từ cho cá nhân, doanh nghiệp vay (không qua tổ chức tín dụng) là thu nhập từ đầu tư vốn, có thể phải chịu thuế.</li><li>Lợi tức từ trái phiếu doanh nghiệp, cổ tức cổ phiếu có quy định thuế riêng.</li><li>Sản phẩm bán qua ngân hàng nhưng không phải tiền gửi (bảo hiểm, chứng chỉ quỹ) theo quy định của từng loại.</li></ul>
<h2>Có cần kê khai không</h2>
<p>Khoản thu nhập được miễn thuế không làm phát sinh tiền thuế phải nộp. Nếu bạn tự quyết toán thuế và có thắc mắc về việc kê khai, hãy hỏi cơ quan thuế quản lý hoặc người làm dịch vụ thuế.</p>
<p>Nội dung mang tính thông tin chung, không thay thế tư vấn thuế cho trường hợp cụ thể.</p>',
                'faq' => [
                    ['question' => 'Gửi tiết kiệm ngân hàng có bị trừ thuế vào tiền lãi không?', 'answer' => 'Không. Lãi tiền gửi tại tổ chức tín dụng được miễn thuế thu nhập cá nhân theo Luật Thuế TNCN 2025.'],
                ],
                'references' => [$pit],
            ],
            [
                'category' => 'the-tin-dung',
                'title' => 'Hạng thẻ tín dụng Classic, Gold, Platinum, Signature khác nhau thế nào',
                'slug' => 'hang-the-tin-dung-khac-nhau-the-nao',
                'excerpt' => 'Hạng thẻ quyết định hạn mức, phí thường niên và đặc quyền đi kèm. Thẻ hạng cao không có nghĩa là hợp với mọi người.',
                'body' => '<p>Các tổ chức thẻ quốc tế như Visa, Mastercard, JCB và thẻ nội địa đều chia sản phẩm thành nhiều hạng. Tên gọi khác nhau giữa các ngân hàng, nhưng có thể xếp chung thành bốn nhóm.</p>
<h2>Bốn nhóm phổ biến</h2>
<ul><li><strong>Phổ thông (Classic, Standard):</strong> hạn mức thấp, phí thường niên thấp hoặc được miễn, phù hợp người mới dùng thẻ.</li><li><strong>Vàng (Gold):</strong> hạn mức cao hơn, thường có ưu đãi hoàn tiền hoặc tích điểm cơ bản.</li><li><strong>Bạch kim (Platinum, Titanium, Ultimate):</strong> hạn mức và phí cao hơn, có thêm bảo hiểm du lịch, ưu đãi nhà hàng, sân bay.</li><li><strong>Cao cấp (Signature, World, Infinite, World Elite):</strong> hạn mức lớn, phí thường niên cao, đặc quyền phòng chờ sân bay, trợ lý cá nhân.</li></ul>
<h2>Chọn hạng nào</h2>
<p>Hãy so tổng giá trị bạn thực sự dùng được trong năm với phí thường niên. Nếu bạn ít đi máy bay, một thẻ Platinum có phòng chờ sân bay có thể tốn hơn thẻ Gold hoàn tiền. Hạn mức được cấp còn phụ thuộc thu nhập và lịch sử tín dụng của bạn, không chỉ hạng thẻ.</p>
<p>Trên trang <a href="/the-tin-dung">so sánh thẻ tín dụng</a>, bạn có thể lọc theo hạng thẻ và ngân hàng, xem phí thường niên, lãi suất và thời gian miễn lãi cạnh nhau. Chưa biết chọn thẻ nào, thử <a href="/the-tin-dung/goi-y">gợi ý thẻ phù hợp</a>.</p>',
                'faq' => [
                    ['question' => 'Thẻ Platinum có hạn mức bao nhiêu?', 'answer' => 'Mỗi ngân hàng quy định khác nhau và hạn mức thực tế được xét theo thu nhập, lịch sử tín dụng của từng người.'],
                    ['question' => 'Có nên mở thẻ hạng cao để được miễn phí phòng chờ sân bay?', 'answer' => 'Chỉ đáng khi giá trị bạn dùng được trong năm lớn hơn phí thường niên và điều kiện chi tiêu tối thiểu.'],
                ],
                'references' => [$tt18],
            ],
            [
                'category' => 'the-tin-dung',
                'title' => 'Thẻ hoàn tiền hay thẻ tích dặm: chọn theo thói quen chi tiêu',
                'slug' => 'the-hoan-tien-hay-the-tich-dam',
                'excerpt' => 'Hoàn tiền đơn giản và dễ tính giá trị, tích dặm có lợi khi bạn bay thường xuyên. Hãy tính theo số tiền bạn thực sự tiêu mỗi tháng.',
                'body' => '<p>Hai loại ưu đãi phổ biến nhất của thẻ tín dụng là hoàn tiền (cashback) và tích dặm bay hoặc điểm thưởng. Loại nào lợi hơn phụ thuộc vào cách bạn tiêu tiền.</p>
<h2>Thẻ hoàn tiền</h2>
<p>Ngân hàng trả lại một tỷ lệ phần trăm số tiền chi tiêu, thường có <strong>trần hoàn tiền mỗi tháng</strong> và chỉ áp dụng cho một số nhóm như siêu thị, ăn uống, mua sắm online. Ví dụ chi 8.000.000 đ/tháng ở nhóm được hoàn 5% nhưng trần 300.000 đ thì bạn chỉ nhận 300.000 đ, không phải 400.000 đ.</p>
<h2>Thẻ tích dặm, tích điểm</h2>
<p>Mỗi khoản chi được quy đổi ra dặm của hãng bay hoặc điểm thưởng. Giá trị phụ thuộc vào cách bạn đổi: đổi vé máy bay thường lợi hơn đổi quà. Dặm có thể hết hạn và cần đủ số lượng mới đổi được.</p>
<h2>Ba câu hỏi để chọn</h2>
<ol><li>Mỗi tháng bạn chi qua thẻ bao nhiêu, vào nhóm nào?</li><li>Bạn có bay đủ nhiều để dùng dặm trước khi hết hạn không?</li><li>Phí thường niên và điều kiện miễn phí là gì?</li></ol>
<p>Dù chọn loại nào, ưu đãi chỉ có ý nghĩa khi bạn <strong>trả đủ dư nợ sao kê đúng hạn</strong>. Lãi thẻ tín dụng cao hơn nhiều so với mọi khoản hoàn tiền. Xem và so sánh ưu đãi tại <a href="/the-tin-dung">trang thẻ tín dụng</a>.</p>',
                'faq' => [
                    ['question' => 'Hoàn tiền có giới hạn không?', 'answer' => 'Hầu hết thẻ có trần hoàn tiền mỗi tháng hoặc mỗi kỳ sao kê và chỉ áp dụng cho một số nhóm chi tiêu.'],
                ],
                'references' => [$tt18],
            ],
            [
                'category' => 'the-tin-dung',
                'title' => 'Chỉ trả mức tối thiểu thẻ tín dụng: tránh phí trễ hạn nhưng vẫn mất lãi',
                'slug' => 'tra-toi-thieu-the-tin-dung',
                'excerpt' => 'Trả mức tối thiểu giúp không bị phạt trễ hạn và giữ lịch sử tín dụng, nhưng toàn bộ dư nợ còn lại bị tính lãi và bạn mất quyền miễn lãi.',
                'body' => '<p>Mỗi kỳ sao kê, ngân hàng ghi hai con số: <strong>tổng dư nợ</strong> và <strong>số tiền thanh toán tối thiểu</strong>, thường chỉ vài phần trăm dư nợ.</p>
<h2>Điều gì xảy ra khi chỉ trả tối thiểu</h2>
<ul><li>Bạn không bị tính phí chậm thanh toán và không bị ghi nợ quá hạn.</li><li>Phần dư nợ còn lại bị tính lãi theo lãi suất thẻ, thường cao hơn nhiều so với vay tiêu dùng có kỳ hạn.</li><li>Nhiều ngân hàng tính lãi trên dư nợ từ ngày giao dịch, không chỉ phần còn thiếu.</li><li>Giao dịch mới trong kỳ sau có thể không còn được miễn lãi cho đến khi bạn trả hết.</li></ul>
<h2>Ví dụ</h2>
<p>Dư nợ 20.000.000 đ, lãi thẻ 30%/năm. Nếu chỉ trả tối thiểu mỗi tháng và không tiêu thêm, phần lớn tiền trả đi vào lãi, khoản nợ có thể kéo dài nhiều năm. Tổng lãi phải trả có thể lên tới hàng chục triệu đồng.</p>
<h2>Nên làm gì</h2>
<ol><li>Cài trích nợ tự động toàn bộ dư nợ sao kê nếu có thể.</li><li>Nếu đang có dư nợ lớn, cân nhắc chuyển sang khoản vay có lãi thấp hơn hoặc trả góp qua ngân hàng, sau khi so sánh tổng chi phí.</li><li>Tạm dừng chi tiêu bằng thẻ cho đến khi trả hết.</li></ol>
<p>Dùng công cụ <a href="/cong-cu/khoan-vay">Tính khoản vay</a> để ước tính tổng lãi khi trả dần.</p>',
                'faq' => [
                    ['question' => 'Trả tối thiểu có bị nợ xấu không?', 'answer' => 'Không, nếu trả đúng hạn. Nhưng phần dư nợ còn lại bị tính lãi theo lãi suất thẻ.'],
                    ['question' => 'Có được miễn lãi khi chỉ trả tối thiểu không?', 'answer' => 'Không. Chỉ được miễn lãi khi trả toàn bộ dư nợ sao kê trước hạn thanh toán.'],
                ],
                'references' => [$tt18, $sbv],
            ],
            [
                'category' => 'vay',
                'title' => 'Lãi suất ưu đãi và lãi thả nổi sau ưu đãi của khoản vay được tính thế nào',
                'slug' => 'lai-suat-uu-dai-va-lai-tha-noi',
                'excerpt' => 'Lãi suất quảng cáo thường chỉ áp dụng vài tháng đầu. Sau đó lãi được tính bằng lãi cơ sở cộng biên độ, và đây mới là con số quyết định tổng chi phí.',
                'body' => '<p>Các khoản vay mua nhà, mua xe thường quảng cáo lãi suất thấp cố định trong 6, 12 hoặc 24 tháng đầu. Phần còn lại của khoản vay, có thể kéo dài 15 đến 25 năm, dùng lãi thả nổi.</p>
<h2>Công thức thường gặp</h2>
<p><strong>Lãi sau ưu đãi = Lãi cơ sở + Biên độ</strong>. Lãi cơ sở thường gắn với lãi suất tiết kiệm kỳ hạn 12 hoặc 24 tháng của chính ngân hàng hoặc bình quân một nhóm ngân hàng, được điều chỉnh định kỳ. Biên độ là phần cộng thêm cố định ghi trong hợp đồng.</p>
<h2>Câu hỏi cần hỏi trước khi ký</h2>
<ol><li>Lãi cơ sở là gì, công bố ở đâu, bao lâu điều chỉnh một lần?</li><li>Biên độ là bao nhiêu và có cố định suốt thời gian vay không?</li><li>Phí trả nợ trước hạn theo từng năm là bao nhiêu?</li><li>Có bắt buộc mua bảo hiểm hay sản phẩm khác kèm khoản vay không?</li></ol>
<h2>Tính thử</h2>
<p>Khi so sánh, hãy tính số tiền trả hằng tháng ở cả giai đoạn ưu đãi và sau ưu đãi. Một khoản vay ưu đãi thấp nhưng biên độ cao có thể đắt hơn khoản vay ưu đãi cao hơn nhưng biên độ thấp.</p>
<p>Trang <a href="/vay">so sánh khoản vay</a> của EzFin luôn hiện lãi ưu đãi kèm lãi sau ưu đãi. Dùng <a href="/cong-cu/khoan-vay">Tính khoản vay</a> để ước tính tiền trả mỗi tháng; kết quả chỉ là ước tính.</p>',
                'faq' => [
                    ['question' => 'Biên độ lãi suất là gì?', 'answer' => 'Là phần cộng thêm vào lãi cơ sở để ra lãi suất thả nổi, ghi trong hợp đồng vay.'],
                    ['question' => 'Lãi thả nổi có thể giảm không?', 'answer' => 'Có. Khi lãi cơ sở giảm thì lãi thả nổi giảm theo, và ngược lại.'],
                ],
                'references' => [$sbv],
            ],
            [
                'category' => 'vay',
                'title' => 'Danh sách kiểm tra trước khi ký hợp đồng vay tiêu dùng',
                'slug' => 'kiem-tra-truoc-khi-ky-hop-dong-vay-tieu-dung',
                'excerpt' => 'Tám điểm cần đọc trong hợp đồng vay: lãi suất năm, tổng số tiền phải trả, phí, bảo hiểm kèm theo, phạt trả chậm và trả trước hạn.',
                'body' => '<p>Vay tiêu dùng nhanh và tiện, nhưng chi phí thật thường nằm ở những dòng nhỏ trong hợp đồng. Trước khi ký, hãy đối chiếu tám điểm sau.</p>
<h2>Tám điểm cần đọc</h2>
<ol><li><strong>Lãi suất tính theo năm:</strong> quy đổi mọi con số "theo tháng" ra "theo năm" để so sánh.</li><li><strong>Cách tính lãi:</strong> trên dư nợ giảm dần hay trên số tiền vay ban đầu (lãi phẳng).</li><li><strong>Tổng số tiền phải trả:</strong> gốc cộng lãi cộng phí trong suốt thời gian vay.</li><li><strong>Phí giải ngân, phí hồ sơ, phí quản lý tài khoản.</strong></li><li><strong>Bảo hiểm khoản vay:</strong> có bắt buộc không, phí bao nhiêu, có được hủy không.</li><li><strong>Phạt trả chậm</strong> và lãi quá hạn.</li><li><strong>Phí trả nợ trước hạn.</strong></li><li><strong>Tổ chức cho vay</strong> có giấy phép của Ngân hàng Nhà nước hay không.</li></ol>
<h2>Dấu hiệu cần dừng lại</h2>
<ul><li>Yêu cầu chuyển tiền "phí giải ngân" trước khi nhận khoản vay.</li><li>Ứng dụng đòi quyền truy cập danh bạ, ảnh.</li><li>Không cung cấp hợp đồng bằng văn bản hoặc bản điện tử để đọc trước.</li></ul>
<h2>Trả được bao nhiêu mỗi tháng</h2>
<p>Tổng tiền trả nợ hằng tháng (gồm cả thẻ tín dụng và các khoản vay khác) nên ở mức bạn vẫn còn tiền cho chi tiêu thiết yếu và tiết kiệm. Dùng <a href="/cong-cu/tra-gop">Tính trả góp</a> để thử nhiều kỳ hạn trước khi vay.</p>',
                'faq' => [
                    ['question' => 'Lãi phẳng 1,5%/tháng tương đương bao nhiêu theo dư nợ giảm dần?', 'answer' => 'Cao hơn nhiều so với 18%/năm theo dư nợ giảm dần, vì lãi phẳng tính trên số tiền vay ban đầu suốt kỳ hạn. Hãy dùng công cụ tính để so sánh cụ thể.'],
                    ['question' => 'Bảo hiểm khoản vay có bắt buộc không?', 'answer' => 'Hãy hỏi rõ và yêu cầu ghi trong hợp đồng. Không ai được ép bạn mua sản phẩm khác như điều kiện bắt buộc nếu pháp luật không quy định.'],
                ],
                'references' => [$sbv],
            ],
            [
                'category' => 'quan-ly-tien',
                'title' => 'Lập ngân sách tháng đầu tiên: bắt đầu từ 30 ngày ghi chép',
                'slug' => 'lap-ngan-sach-thang-dau-tien',
                'excerpt' => 'Ngân sách tốt bắt đầu từ số liệu thật. Ghi lại chi tiêu trong 30 ngày, nhóm lại và đặt hạn mức cho tháng sau.',
                'body' => '<p>Nhiều người bỏ cuộc vì lập ngân sách theo con số tưởng tượng. Cách dễ hơn là nhìn vào những gì bạn đã thực sự chi.</p>
<h2>Bước 1: ghi chép 30 ngày</h2>
<p>Ghi mọi khoản chi, kể cả cà phê và phí giao hàng. Có thể nhập sao kê ngân hàng để không phải gõ tay từng giao dịch.</p>
<h2>Bước 2: chia nhóm</h2>
<ul><li><strong>Thiết yếu:</strong> nhà ở, điện nước, ăn uống, đi lại, học phí.</li><li><strong>Linh hoạt:</strong> ăn ngoài, mua sắm, giải trí.</li><li><strong>Tương lai:</strong> tiết kiệm, quỹ dự phòng, trả nợ.</li></ul>
<h2>Bước 3: đặt hạn mức cho tháng sau</h2>
<p>Bắt đầu bằng việc giảm nhẹ một hoặc hai nhóm linh hoạt, không cắt mọi thứ cùng lúc. Chuyển tiền tiết kiệm ngay khi nhận lương thay vì chờ cuối tháng còn dư.</p>
<h2>Bước 4: xem lại mỗi tuần</h2>
<p>Năm phút mỗi tuần để xem nhóm nào sắp vượt hạn mức hiệu quả hơn một buổi tổng kết dài cuối tháng.</p>
<p><a href="/ung-dung">Ứng dụng quản lý tiền của EzFin</a> miễn phí, cho phép ghi chi tiêu, nhập sao kê và đặt ngân sách theo nhóm.</p>',
                'faq' => [
                    ['question' => 'Nên tiết kiệm bao nhiêu phần trăm thu nhập?', 'answer' => 'Không có con số chung cho mọi người. Nhiều người bắt đầu từ 10 đến 20% và tăng dần khi kiểm soát được chi tiêu.'],
                ],
                'references' => [$sbv],
            ],
            [
                'category' => 'quan-ly-tien',
                'title' => 'Trả nhiều khoản nợ cùng lúc: phương pháp quả cầu tuyết và tuyết lở',
                'slug' => 'tra-no-qua-cau-tuyet-va-tuyet-lo',
                'excerpt' => 'Khi có nhiều khoản nợ, thứ tự trả quan trọng. Trả khoản lãi cao nhất trước tiết kiệm tiền nhất, trả khoản nhỏ nhất trước giúp giữ động lực.',
                'body' => '<p>Nếu bạn đang có thẻ tín dụng, vay tiêu dùng và trả góp điện thoại cùng lúc, hãy luôn trả đủ mức tối thiểu của mọi khoản, rồi dồn phần tiền còn lại vào một khoản theo thứ tự đã chọn.</p>
<h2>Phương pháp tuyết lở (avalanche)</h2>
<p>Dồn tiền vào khoản có <strong>lãi suất cao nhất</strong> trước. Về mặt toán học, đây là cách tốn ít tiền lãi nhất.</p>
<h2>Phương pháp quả cầu tuyết (snowball)</h2>
<p>Dồn tiền vào khoản có <strong>dư nợ nhỏ nhất</strong> trước. Bạn sớm trả xong một khoản, có thêm động lực và số tiền đã dùng cho khoản đó được chuyển sang khoản tiếp theo.</p>
<h2>Ví dụ</h2>
<ul><li>Thẻ tín dụng 15.000.000 đ, lãi 30%/năm.</li><li>Vay tiêu dùng 40.000.000 đ, lãi 20%/năm.</li><li>Trả góp điện thoại 6.000.000 đ, lãi 0% nhưng có phí.</li></ul>
<p>Theo tuyết lở, bạn ưu tiên thẻ tín dụng. Theo quả cầu tuyết, bạn ưu tiên trả góp điện thoại. Cả hai đều tốt hơn việc chia đều không có kế hoạch.</p>
<h2>Tránh vòng lặp nợ</h2>
<p>Không mở thêm khoản vay mới để trả khoản cũ nếu tổng chi phí không thấp hơn. Ghi lại các khoản nợ và ngày đến hạn trong <a href="/ung-dung">ứng dụng quản lý tiền</a> để không bỏ lỡ kỳ trả.</p>',
                'faq' => [
                    ['question' => 'Nên chọn phương pháp nào?', 'answer' => 'Nếu bạn kỷ luật tốt, tuyết lở tiết kiệm tiền lãi hơn. Nếu bạn cần động lực, quả cầu tuyết giúp bạn duy trì kế hoạch.'],
                ],
                'references' => [$sbv],
            ],
            [
                'category' => 'an-toan-ngan-hang',
                'title' => 'Nhận diện tin nhắn, cuộc gọi mạo danh ngân hàng',
                'slug' => 'nhan-dien-lua-dao-mao-danh-ngan-hang',
                'excerpt' => 'Ngân hàng không bao giờ yêu cầu bạn cung cấp mã OTP, mật khẩu hay cài ứng dụng qua đường link lạ. Năm dấu hiệu giúp nhận ra kẻ lừa đảo.',
                'body' => '<p>Kẻ gian thường giả danh nhân viên ngân hàng, công an, nhà mạng hoặc đơn vị giao hàng để lấy thông tin đăng nhập và mã OTP.</p>
<h2>Năm dấu hiệu thường gặp</h2>
<ol><li><strong>Tạo áp lực thời gian:</strong> "tài khoản sẽ bị khóa trong 30 phút".</li><li><strong>Gửi đường link</strong> để "cập nhật thông tin", "nhận hoàn tiền" hoặc "nâng hạn mức".</li><li><strong>Hỏi mã OTP</strong>, mật khẩu, số thẻ đầy đủ, mã CVV.</li><li><strong>Yêu cầu cài ứng dụng</strong> từ file hoặc link ngoài cửa hàng ứng dụng chính thức.</li><li><strong>Đề nghị chuyển tiền</strong> sang "tài khoản an toàn" để bảo vệ tiền.</li></ol>
<h2>Cách tự bảo vệ</h2>
<ul><li>Chỉ cài ứng dụng ngân hàng từ App Store hoặc Google Play, kiểm tra tên nhà phát triển.</li><li>Gọi lại tổng đài in trên thẻ hoặc website chính thức khi nghi ngờ, không gọi số trong tin nhắn.</li><li>Bật xác thực sinh trắc học và thông báo biến động số dư.</li><li>Không chia sẻ màn hình điện thoại với người lạ.</li></ul>
<h2>Khi đã lỡ cung cấp thông tin</h2>
<p>Gọi ngay tổng đài ngân hàng để khóa tài khoản, thẻ và ứng dụng; đổi mật khẩu; báo cơ quan công an nơi gần nhất kèm bằng chứng tin nhắn, cuộc gọi.</p>',
                'faq' => [
                    ['question' => 'Ngân hàng có gọi điện hỏi mã OTP không?', 'answer' => 'Không. Bất kỳ ai hỏi mã OTP đều là dấu hiệu lừa đảo.'],
                    ['question' => 'Lỡ bấm vào link lạ thì phải làm gì?', 'answer' => 'Không nhập thông tin, gọi tổng đài ngân hàng để khóa dịch vụ nếu đã nhập, đổi mật khẩu và quét điện thoại.'],
                ],
                'references' => [$sbv, $qd2345],
            ],
            [
                'category' => 'an-toan-ngan-hang',
                'title' => 'Khi nào chuyển tiền phải xác thực khuôn mặt',
                'slug' => 'khi-nao-chuyen-tien-phai-xac-thuc-khuon-mat',
                'excerpt' => 'Theo Quyết định 2345/QĐ-NHNN, chuyển tiền online trên 10 triệu đồng một lần hoặc tổng trên 20 triệu đồng một ngày phải xác thực sinh trắc học.',
                'body' => '<p>Từ 01/7/2024, các ngân hàng áp dụng xác thực sinh trắc học cho một số giao dịch trực tuyến theo Quyết định 2345/QĐ-NHNN, nhằm hạn chế việc kẻ gian dùng tài khoản của người khác.</p>
<h2>Các trường hợp cần xác thực</h2>
<ul><li>Chuyển tiền hoặc thanh toán trực tuyến <strong>trên 10 triệu đồng một lần</strong>.</li><li>Giao dịch làm <strong>tổng số tiền chuyển trong ngày vượt 20 triệu đồng</strong>.</li><li>Lần đầu đăng nhập ứng dụng trên thiết bị mới hoặc khi đổi thiết bị.</li></ul>
<h2>Xác thực bằng gì</h2>
<p>Ứng dụng so khớp khuôn mặt của bạn với dữ liệu sinh trắc học trên căn cước gắn chip hoặc tài khoản định danh điện tử VNeID. Bạn cần cập nhật sinh trắc học trên ứng dụng ngân hàng một lần trước khi thực hiện các giao dịch này.</p>
<h2>Mẹo sử dụng</h2>
<ul><li>Cập nhật sinh trắc học sớm, đừng chờ đến lúc cần chuyển gấp.</li><li>Nếu căn cước hết hạn hoặc đổi mới, hãy cập nhật lại trên ứng dụng.</li><li>Không thực hiện xác thực khuôn mặt theo yêu cầu của người lạ qua video call.</li></ul>
<p>Quy định cụ thể có thể được Ngân hàng Nhà nước cập nhật; hãy theo dõi thông báo trên ứng dụng ngân hàng của bạn.</p>',
                'faq' => [
                    ['question' => 'Chuyển 5 triệu đồng có phải xác thực khuôn mặt không?', 'answer' => 'Thường là không, trừ khi giao dịch đó làm tổng số tiền chuyển trong ngày vượt 20 triệu đồng.'],
                ],
                'references' => [$qd2345, $sbv],
            ],
            [
                'category' => 'vang-ngoai-te',
                'title' => 'Đọc bảng giá vàng: giá mua vào, bán ra và chênh lệch',
                'slug' => 'doc-bang-gia-vang-mua-vao-ban-ra',
                'excerpt' => 'Giá mua vào là giá cửa hàng mua lại từ bạn, giá bán ra là giá bạn phải trả. Chênh lệch giữa hai giá là chi phí bạn chịu ngay khi mua.',
                'body' => '<p>Bảng giá vàng của các doanh nghiệp kinh doanh vàng luôn có hai cột. Hiểu đúng hai cột này giúp bạn biết mình đang lãi hay lỗ.</p>
<h2>Hai cột giá</h2>
<ul><li><strong>Mua vào:</strong> giá doanh nghiệp trả khi bạn bán vàng cho họ.</li><li><strong>Bán ra:</strong> giá bạn phải trả khi mua vàng.</li></ul>
<p>Nếu giá bán ra là 137.500.000 đ/lượng và mua vào là 133.800.000 đ/lượng, chênh lệch là 3.700.000 đ. Mua xong bán lại ngay, bạn mất khoản đó.</p>
<h2>Các loại vàng thường gặp</h2>
<ul><li><strong>Vàng miếng:</strong> thương hiệu SJC hoặc các thương hiệu được phép, định giá theo lượng.</li><li><strong>Vàng nhẫn trơn 9999:</strong> giá thường sát giá thế giới quy đổi hơn vàng miếng.</li><li><strong>Vàng trang sức:</strong> có tiền công chế tác, giá mua lại thường thấp hơn nhiều so với giá mua.</li></ul>
<h2>Đơn vị</h2>
<p>1 lượng (cây) = 10 chỉ = 37,5 gam. Một số nơi niêm yết theo chỉ hoặc nghìn đồng/chỉ, cần quy đổi trước khi so sánh.</p>
<p>Xem <a href="/gia-vang">giá vàng hôm nay</a> trên EzFin, có ghi nguồn và thời điểm cập nhật của từng doanh nghiệp. Nội dung mang tính thông tin, không phải khuyến nghị mua bán.</p>',
                'faq' => [
                    ['question' => '1 lượng vàng bằng bao nhiêu gam?', 'answer' => '1 lượng (cây) bằng 10 chỉ, tương đương 37,5 gam.'],
                    ['question' => 'Vì sao bán vàng trang sức được giá thấp?', 'answer' => 'Vì giá mua có tiền công chế tác, còn giá mua lại chỉ tính theo trọng lượng và tuổi vàng.'],
                ],
                'references' => [$sbv],
            ],
            [
                'category' => 'vang-ngoai-te',
                'title' => 'Tỷ giá mua tiền mặt, mua chuyển khoản và bán ra khác nhau thế nào',
                'slug' => 'ty-gia-mua-tien-mat-chuyen-khoan-ban-ra',
                'excerpt' => 'Ngân hàng niêm yết ba mức tỷ giá cho mỗi ngoại tệ. Biết dùng mức nào giúp bạn tính đúng số tiền khi đổi tiền đi du lịch hay nhận tiền từ nước ngoài.',
                'body' => '<p>Trên bảng tỷ giá của ngân hàng, mỗi ngoại tệ thường có ba cột.</p>
<h2>Ba cột tỷ giá</h2>
<ul><li><strong>Mua tiền mặt:</strong> ngân hàng mua ngoại tệ tiền mặt từ bạn. Thường thấp nhất vì ngân hàng tốn chi phí kiểm đếm, vận chuyển.</li><li><strong>Mua chuyển khoản:</strong> ngân hàng mua ngoại tệ khi bạn nhận tiền chuyển về tài khoản, ví dụ kiều hối, thanh toán từ nước ngoài.</li><li><strong>Bán ra:</strong> ngân hàng bán ngoại tệ cho bạn, ví dụ khi bạn đổi tiền đi du lịch hay chuyển tiền đóng học phí.</li></ul>
<h2>Ví dụ</h2>
<p>Bạn cần 1.000 USD đi du lịch: số tiền đồng phải trả tính theo cột <strong>bán ra</strong>. Khi về còn dư 200 USD tiền mặt muốn đổi lại: tính theo cột <strong>mua tiền mặt</strong>. Chênh lệch giữa hai cột là chi phí bạn chịu.</p>
<h2>Thanh toán thẻ ở nước ngoài</h2>
<p>Khi quẹt thẻ bằng ngoại tệ, ngân hàng quy đổi theo tỷ giá tại thời điểm ghi nhận giao dịch và thường cộng phí giao dịch ngoại tệ theo biểu phí của thẻ. Khi máy POS hỏi trả bằng VND hay ngoại tệ, chọn ngoại tệ địa phương thường tránh được tỷ giá bất lợi của bên chấp nhận thẻ.</p>
<p>Xem <a href="/ty-gia">tỷ giá hôm nay</a> trên EzFin, cập nhật từ bảng niêm yết của ngân hàng kèm thời điểm.</p>',
                'faq' => [
                    ['question' => 'Đổi tiền đi du lịch dùng tỷ giá nào?', 'answer' => 'Dùng tỷ giá bán ra của ngân hàng.'],
                    ['question' => 'Nhận kiều hối về tài khoản tính theo tỷ giá nào?', 'answer' => 'Thường là tỷ giá mua chuyển khoản của ngân hàng nhận tiền.'],
                ],
                'references' => [$sbv],
            ],
        ];
    }
}
