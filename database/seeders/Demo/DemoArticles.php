<?php

namespace Database\Seeders\Demo;

use App\Modules\Content\Enums\ArticleStatus;
use App\Modules\Content\Models\Article;
use App\Modules\Content\Models\ArticleCategory;
use App\Modules\Content\Models\Author;
use App\Support\Demo\DemoManifest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Tác giả, chuyên mục và bài viết kiến thức DEMO (tác giả là nhân vật giả lập). */
class DemoArticles
{
    private const CATEGORIES = [
        'tiet-kiem' => ['Tiết kiệm', 'Gửi tiết kiệm, lãi suất, kỳ hạn và bảo hiểm tiền gửi.'],
        'the-tin-dung' => ['Thẻ tín dụng', 'Cách chọn, dùng thẻ và tránh phí, lãi không cần thiết.'],
        'vay' => ['Vay và trả góp', 'Hiểu đúng lãi suất, cách tính và chi phí thật của khoản vay.'],
        'quan-ly-tien' => ['Quản lý tiền', 'Lập ngân sách, quỹ dự phòng và thói quen tài chính.'],
    ];

    /** [slug, tên, chức danh, chuyên môn, giới thiệu] */
    private const AUTHORS = [
        ['minh-thu', 'Nguyễn Minh Thư', 'Biên tập viên tài chính cá nhân', 'Tiết kiệm, ngân sách gia đình',
            'Nhân vật demo. Viết về tiết kiệm và quản lý chi tiêu cho người mới đi làm.'],
        ['quoc-huy', 'Trần Quốc Huy', 'Chuyên viên phân tích sản phẩm ngân hàng', 'Thẻ tín dụng, khoản vay',
            'Nhân vật demo. Phân tích biểu phí thẻ và khoản vay tiêu dùng.'],
        ['hai-yen', 'Lê Hải Yến', 'Biên tập viên', 'Kiến thức tài chính cơ bản',
            'Nhân vật demo. Biên tập nội dung kiến thức, kiểm tra nguồn tham khảo.'],
    ];

    public function __construct(private DemoManifest $manifest) {}

    public function run(): int
    {
        $categories = [];
        $sort = (int) ArticleCategory::max('sort_order');
        foreach (self::CATEGORIES as $slug => [$name, $description]) {
            $category = ArticleCategory::where('slug', $slug)->first();
            if (! $category) {
                $category = $this->manifest->track(ArticleCategory::create(['name' => $name, 'slug' => $slug, 'description' => $description, 'sort_order' => ++$sort]));
            }
            $categories[$slug] = $category;
        }

        $authors = [];
        foreach (self::AUTHORS as [$slug, $name, $title, $expertise, $bio]) {
            $authors[$slug] = $this->manifest->track(Author::create(['name' => $name,
                'slug' => Author::where('slug', $slug)->exists() ? "$slug-demo" : $slug,
                'job_title' => $title, 'expertise' => $expertise, 'bio' => $bio, 'is_active' => true]));
        }

        foreach ($this->articles() as $i => [$category, $author, $title, $excerpt, $body, $references]) {
            $cover = 'articles/demo-'.Str::lower(Str::random(8)).'.jpg';
            Storage::disk('public')->put($cover, file_get_contents(__DIR__.'/assets/covers/cover-'.($i % 12 + 1).'.jpg'));
            $this->manifest->trackFile($cover);
            $slug = Str::slug($title);
            $article = new Article(['category_id' => $categories[$category]->id, 'author_id' => $authors[$author]->id,
                'title' => $title, 'slug' => Article::withTrashed()->where('slug', $slug)->exists() ? "$slug-demo" : $slug,
                'excerpt' => $excerpt, 'body' => $body, 'references' => $references, 'cover_path' => $cover]);
            $article->status = ArticleStatus::Published;
            $article->published_at = now()->subDays(3 + $i * 6)->setTime(8, 30);
            $article->save();
            $this->manifest->track($article);
        }

        return count($this->articles());
    }

    /** @return list<array{string, string, string, string, string, list<array{title: string, url?: string}>}> */
    private function articles(): array
    {
        $sbv = ['title' => 'Ngân hàng Nhà nước Việt Nam', 'url' => 'https://www.sbv.gov.vn'];
        $di = ['title' => 'Bảo hiểm tiền gửi Việt Nam', 'url' => 'https://div.gov.vn'];

        return [
            ['tiet-kiem', 'minh-thu', 'Lãi kép là gì và vì sao gửi sớm có lợi',
                'Lãi kép là tiền lãi được cộng vào gốc để sinh lãi tiếp. Hiểu cơ chế này giúp bạn chọn kỳ hạn và cách tái tục hợp lý.',
                '<p>Khi gửi tiết kiệm và chọn tái tục cả gốc lẫn lãi, tiền lãi kỳ trước được cộng vào gốc của kỳ sau. Đó là <strong>lãi kép</strong>: lãi sinh ra lãi.</p>
<h2>Ví dụ đơn giản</h2>
<p>Gửi 100.000.000 đ, lãi 5%/năm, tái tục mỗi năm. Năm đầu nhận 5.000.000 đ. Năm thứ hai gốc là 105.000.000 đ nên lãi là 5.250.000 đ. Sau 10 năm, số tiền khoảng 162.900.000 đ, trong khi nếu rút lãi ra mỗi năm thì chỉ có 150.000.000 đ.</p>
<h2>Ba yếu tố quyết định</h2>
<ul><li><strong>Thời gian:</strong> càng để lâu, phần lãi của lãi càng lớn.</li><li><strong>Lãi suất:</strong> chênh 0,5 điểm % mỗi năm cũng tạo khác biệt rõ sau nhiều năm.</li><li><strong>Tần suất nhập lãi:</strong> nhập lãi hằng tháng cho kết quả nhỉnh hơn nhập lãi cuối năm.</li></ul>
<h2>Áp dụng thế nào</h2>
<p>Nếu không cần dùng tiền lãi, hãy chọn tái tục gốc và lãi. Dùng công cụ Tính lãi kép của EzFin để thử các mức lãi suất và số năm khác nhau trước khi quyết định.</p>',
                [$sbv]],
            ['tiet-kiem', 'minh-thu', 'Gửi tiết kiệm online có an toàn không',
                'Tiền gửi online được ghi nhận như gửi tại quầy và được bảo hiểm tiền gửi theo cùng quy định. Điều cần cẩn thận là bảo mật tài khoản của bạn.',
                '<p>Sổ tiết kiệm online là hợp đồng tiền gửi giữa bạn và ngân hàng, chỉ khác ở chỗ được mở trên ứng dụng thay vì tại quầy. Ngân hàng vẫn có nghĩa vụ trả gốc và lãi như tiền gửi thông thường.</p>
<h2>Bảo hiểm tiền gửi</h2>
<p>Tiền gửi bằng đồng Việt Nam của cá nhân tại ngân hàng tham gia bảo hiểm tiền gửi được chi trả tối đa <strong>125 triệu đồng</strong> cho mỗi người tại mỗi tổ chức tín dụng nếu tổ chức đó mất khả năng chi trả.</p>
<h2>Rủi ro thật nằm ở đâu</h2>
<ul><li>Lộ mật khẩu, mã OTP cho người lạ.</li><li>Cài ứng dụng giả mạo ngân hàng.</li><li>Bấm vào đường link lạ gửi qua tin nhắn.</li></ul>
<p>Hãy bật xác thực sinh trắc học, không chia sẻ OTP và kiểm tra sổ trên ứng dụng ngay sau khi mở.</p>',
                [$di, $sbv]],
            ['tiet-kiem', 'hai-yen', 'Rút tiết kiệm trước hạn: bạn mất bao nhiêu tiền lãi',
                'Rút trước hạn thường chỉ được hưởng lãi không kỳ hạn rất thấp. Một vài cách giúp bạn giảm thiệt hại khi cần tiền gấp.',
                '<p>Khi tất toán sổ trước ngày đáo hạn, phần lớn ngân hàng chỉ trả lãi theo mức <strong>không kỳ hạn</strong>, thường quanh 0,1 đến 0,5%/năm, thay vì lãi suất kỳ hạn bạn đã chọn.</p>
<h2>Ví dụ</h2>
<p>Gửi 200.000.000 đ kỳ hạn 12 tháng lãi 5,5%/năm. Nếu rút sau 8 tháng với lãi không kỳ hạn 0,2%/năm, bạn nhận khoảng 266.000 đ tiền lãi thay vì khoảng 7.300.000 đ nếu chờ đủ 8 tháng ở kỳ hạn tương ứng.</p>
<h2>Cách giảm thiệt hại</h2>
<ul><li>Chia tiền thành nhiều sổ nhỏ để chỉ phải rút một phần.</li><li>Hỏi ngân hàng về rút gốc một phần, một số sản phẩm cho phép.</li><li>Cân nhắc cầm cố sổ để vay ngắn hạn nếu chỉ cần tiền trong vài tuần.</li></ul>',
                [$sbv]],
            ['the-tin-dung', 'quoc-huy', 'Cách đọc biểu phí thẻ tín dụng trước khi mở thẻ',
                'Phí thường niên chỉ là một phần. Lãi suất, phí chậm thanh toán, phí ngoại tệ và phí rút tiền mặt mới quyết định thẻ có rẻ hay không.',
                '<p>Mỗi ngân hàng công bố biểu phí cho từng dòng thẻ. Trước khi đăng ký, hãy đọc kỹ năm khoản dưới đây.</p>
<h2>Năm khoản cần xem</h2>
<ol><li><strong>Phí thường niên:</strong> có được miễn năm đầu hay miễn khi chi tiêu đủ mức không.</li><li><strong>Lãi suất:</strong> áp dụng khi bạn không trả đủ dư nợ sao kê đúng hạn.</li><li><strong>Phí chậm thanh toán:</strong> thường tính theo % số tiền thanh toán tối thiểu.</li><li><strong>Phí giao dịch ngoại tệ:</strong> quan trọng nếu bạn mua hàng nước ngoài hoặc đi du lịch.</li><li><strong>Phí rút tiền mặt:</strong> thường cao và bị tính lãi ngay từ ngày rút.</li></ol>
<p>Trang so sánh thẻ của EzFin hiển thị các thông số này cạnh nhau, kèm nguồn và ngày xác minh.</p>',
                [$sbv]],
            ['the-tin-dung', 'quoc-huy', 'Thời gian miễn lãi của thẻ tín dụng hoạt động thế nào',
                'Được miễn lãi tới 45 hoặc 55 ngày không có nghĩa mọi giao dịch đều được 55 ngày. Thời gian thực tế phụ thuộc ngày bạn quẹt thẻ.',
                '<p>Thời gian miễn lãi gồm hai phần: từ ngày giao dịch đến ngày sao kê, cộng thêm thời gian từ ngày sao kê đến hạn thanh toán.</p>
<h2>Ví dụ với thẻ miễn lãi tối đa 45 ngày</h2>
<p>Ngày sao kê là 25 hằng tháng, hạn thanh toán sau 15 ngày. Giao dịch ngày 26/9 được miễn lãi đến 10/11, khoảng 45 ngày. Giao dịch ngày 24/10 cũng phải trả trước 10/11, chỉ còn khoảng 17 ngày.</p>
<h2>Lưu ý</h2>
<ul><li>Chỉ được miễn lãi khi trả <strong>toàn bộ</strong> dư nợ sao kê đúng hạn.</li><li>Rút tiền mặt thường không được miễn lãi.</li><li>Trả mức tối thiểu giúp tránh phí trễ hạn nhưng vẫn bị tính lãi.</li></ul>',
                [$sbv]],
            ['the-tin-dung', 'hai-yen', 'Trả góp 0% có thật sự là 0%',
                'Nhiều chương trình trả góp 0% vẫn có phí chuyển đổi hoặc yêu cầu mua bảo hiểm. Hãy tính tổng số tiền phải trả trước khi đồng ý.',
                '<p>"Trả góp 0%" nghĩa là không tính lãi trên khoản trả góp, nhưng không có nghĩa là không có chi phí.</p>
<h2>Các khoản thường gặp</h2>
<ul><li>Phí chuyển đổi trả góp, tính một lần theo % giá trị.</li><li>Giá bán khi trả góp cao hơn giá trả thẳng.</li><li>Bảo hiểm khoản vay bắt buộc.</li><li>Phí tất toán trước hạn.</li></ul>
<h2>Cách so sánh</h2>
<p>Cộng tất cả số tiền bạn sẽ trả, trừ giá trả thẳng, rồi chia cho giá trả thẳng. Con số đó mới là chi phí thật. Công cụ Tính trả góp của EzFin giúp bạn làm phép tính này.</p>',
                []],
            ['vay', 'quoc-huy', 'Lãi phẳng và lãi trên dư nợ giảm dần khác nhau thế nào',
                'Cùng một con số lãi suất, khoản vay tính lãi phẳng có chi phí gần gấp đôi khoản vay tính trên dư nợ giảm dần.',
                '<p><strong>Lãi phẳng</strong> tính trên số tiền vay ban đầu trong suốt thời hạn vay. <strong>Dư nợ giảm dần</strong> tính trên số tiền bạn còn nợ, nên tiền lãi giảm dần theo thời gian.</p>
<h2>Ví dụ vay 50.000.000 đ trong 12 tháng</h2>
<table><thead><tr><th>Cách tính</th><th>Lãi suất</th><th>Tổng tiền lãi</th></tr></thead><tbody><tr><td>Lãi phẳng</td><td>12%/năm</td><td>6.000.000 đ</td></tr><tr><td>Dư nợ giảm dần</td><td>12%/năm</td><td>khoảng 3.310.000 đ</td></tr></tbody></table>
<p>Khi so sánh khoản vay, hãy hỏi rõ cách tính lãi và quy đổi về lãi suất trên dư nợ giảm dần để so cùng một thước đo.</p>',
                [$sbv]],
            ['vay', 'hai-yen', 'Vay mua nhà: thu nhập bao nhiêu là đủ',
                'Nguyên tắc phổ biến là tổng tiền trả nợ mỗi tháng không vượt 30 đến 40% thu nhập. Đừng quên tính lãi sau thời gian ưu đãi.',
                '<p>Ngân hàng xem xét khả năng trả nợ dựa trên thu nhập và các khoản nợ hiện có. Bản thân bạn cũng nên tự đặt giới hạn an toàn.</p>
<h2>Các bước tự kiểm tra</h2>
<ol><li>Tính tiền trả mỗi tháng với lãi suất <strong>sau ưu đãi</strong>, không chỉ lãi ưu đãi năm đầu.</li><li>Cộng các khoản nợ khác như thẻ tín dụng, vay tiêu dùng.</li><li>So với thu nhập ròng hằng tháng; nên dưới 40%.</li><li>Giữ quỹ dự phòng ít nhất 6 tháng tiền trả nợ.</li></ol>
<p>Công cụ Tính khoản vay của EzFin cho thấy lịch trả nợ theo từng tháng để bạn kiểm tra trước khi ký hợp đồng.</p>',
                [$sbv]],
            ['quan-ly-tien', 'minh-thu', 'Quy tắc 50/30/20 cho người mới đi làm',
                'Chia thu nhập thành ba phần: nhu cầu thiết yếu, mong muốn và tiết kiệm. Cách đơn giản để bắt đầu lập ngân sách.',
                '<p>Quy tắc 50/30/20 chia thu nhập sau thuế thành ba phần:</p>
<ul><li><strong>50% nhu cầu:</strong> nhà ở, ăn uống, đi lại, hóa đơn.</li><li><strong>30% mong muốn:</strong> giải trí, mua sắm, du lịch.</li><li><strong>20% tiết kiệm và trả nợ:</strong> quỹ dự phòng, tiết kiệm mục tiêu.</li></ul>
<h2>Ví dụ với thu nhập 15.000.000 đ</h2>
<p>Thiết yếu 7.500.000 đ, mong muốn 4.500.000 đ, tiết kiệm 3.000.000 đ. Nếu tiền nhà quá cao, hãy giảm phần mong muốn trước, đừng cắt phần tiết kiệm.</p>
<p>Trong ứng dụng EzFin, bạn đặt ngân sách theo danh mục và nhận cảnh báo khi sắp vượt.</p>',
                []],
            ['quan-ly-tien', 'hai-yen', 'Quỹ dự phòng bao nhiêu là đủ',
                'Quỹ dự phòng nên bằng 3 đến 6 tháng chi tiêu thiết yếu, để ở nơi rút được nhanh và không mất giá trị.',
                '<p>Quỹ dự phòng giúp bạn không phải vay nóng hay rút tiết kiệm trước hạn khi mất việc, ốm đau hoặc sửa chữa đột xuất.</p>
<h2>Bao nhiêu là đủ</h2>
<ul><li>3 tháng chi tiêu thiết yếu nếu thu nhập ổn định và chưa có người phụ thuộc.</li><li>6 tháng trở lên nếu thu nhập không đều hoặc là trụ cột gia đình.</li></ul>
<h2>Để ở đâu</h2>
<p>Chia làm hai phần: một phần trong tài khoản thanh toán để dùng ngay, phần còn lại gửi tiết kiệm kỳ hạn ngắn hoặc sản phẩm cho phép rút gốc linh hoạt.</p>
<p>Tạo mục tiêu "Quỹ dự phòng" trong ứng dụng EzFin để theo dõi tiến độ mỗi tháng.</p>',
                [$di]],
        ];
    }
}
