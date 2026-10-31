<?php

namespace Database\Seeders\Demo;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Partner\Models\OutboundClick;
use App\Modules\Partner\Models\OutboundLink;
use App\Support\Demo\DemoManifest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ngân hàng, lãi suất tiết kiệm, thẻ tín dụng, tài khoản ngân hàng và link đối tác DEMO.
 * Số liệu là giả lập để duyệt giao diện, không phải biểu phí thật của ngân hàng.
 */
class DemoCatalog
{
    public const SOURCE_NOTE = 'Dữ liệu demo để duyệt giao diện, chưa phải lãi suất, biểu phí thật của ngân hàng.';

    /** slug => [tên, tên ngắn, website, loại, mức lãi so với mặt bằng (điểm %)] */
    private const INSTITUTIONS = [
        'vietcombank' => ['Ngân hàng TMCP Ngoại thương Việt Nam', 'Vietcombank', 'https://www.vietcombank.com.vn', 'bank', -1.0],
        'bidv' => ['Ngân hàng TMCP Đầu tư và Phát triển Việt Nam', 'BIDV', 'https://bidv.com.vn', 'bank', -0.6],
        'vietinbank' => ['Ngân hàng TMCP Công Thương Việt Nam', 'VietinBank', 'https://www.vietinbank.vn', 'bank', -0.6],
        'techcombank' => ['Ngân hàng TMCP Kỹ Thương Việt Nam', 'Techcombank', 'https://techcombank.com', 'bank', -0.1],
        'vpbank' => ['Ngân hàng TMCP Việt Nam Thịnh Vượng', 'VPBank', 'https://www.vpbank.com.vn', 'bank', 0.2],
        'mb' => ['Ngân hàng TMCP Quân đội', 'MB', 'https://www.mbbank.com.vn', 'bank', -0.2],
        'acb' => ['Ngân hàng TMCP Á Châu', 'ACB', 'https://acb.com.vn', 'bank', -0.1],
        'tpbank' => ['Ngân hàng TMCP Tiên Phong', 'TPBank', 'https://tpb.vn', 'bank', 0.3],
        'vib' => ['Ngân hàng TMCP Quốc tế Việt Nam', 'VIB', 'https://www.vib.com.vn', 'bank', 0.4],
        'sacombank' => ['Ngân hàng TMCP Sài Gòn Thương Tín', 'Sacombank', 'https://www.sacombank.com.vn', 'bank', 0.1],
        'hdbank' => ['Ngân hàng TMCP Phát triển TP. Hồ Chí Minh', 'HDBank', 'https://hdbank.com.vn', 'bank', 0.6],
        'shinhan' => ['Ngân hàng TNHH MTV Shinhan Việt Nam', 'Shinhan Bank', 'https://shinhan.com.vn', 'bank', -0.3],
        'fe-credit' => ['Công ty Tài chính TNHH MTV Ngân hàng Việt Nam Thịnh Vượng', 'FE Credit', 'https://fecredit.com.vn', 'finance_company', 0],
        'home-credit' => ['Công ty Tài chính TNHH MTV Home Credit Việt Nam', 'Home Credit', 'https://www.homecredit.vn', 'finance_company', 0],
    ];

    /** Lãi suất gửi online mặt bằng theo kỳ hạn (%/năm); kỳ hạn dưới 6 tháng không vượt trần 4,75%. */
    private const BASE_RATES = [1 => 3.6, 3 => 3.9, 6 => 5.0, 9 => 5.1, 12 => 5.6, 13 => 5.7, 18 => 5.8, 24 => 5.8, 36 => 5.8];

    /**
     * [ngân hàng, tên, mạng thẻ, phí năm, phí phát hành, thu nhập tối thiểu, lãi %/năm, ngày miễn lãi,
     *  hoàn tiền %, trần hoàn tiền/tháng, phí giao dịch ngoại tệ %, quyền lợi, ảnh, tóm tắt, ưu, nhược]
     */
    private const CARDS = [
        ['vietcombank', 'Vietcombank Visa Platinum', 'visa', 800_000, 0, 15_000_000, 27.0, 50, 0.3, 600_000, 2.5, ['cashback', 'travel'], 'c11',
            'Thẻ hạng bạch kim cho người có thu nhập ổn định, hoàn tiền mọi chi tiêu và ưu đãi phòng chờ sân bay.',
            ['Hoàn tiền cho mọi chi tiêu', 'Miễn lãi tới 50 ngày', 'Phòng chờ sân bay nội địa'], ['Phí thường niên cao', 'Hoàn tiền thấp so với thẻ chuyên hoàn tiền']],
        ['vietcombank', 'Vietcombank Mastercard World', 'mastercard', 1_200_000, 0, 30_000_000, 26.0, 50, 0.5, 1_000_000, 2.5, ['travel', 'dining'], 'c2',
            'Thẻ cao cấp cho người hay đi nước ngoài, tích ưu đãi nhà hàng và bảo hiểm du lịch.',
            ['Bảo hiểm du lịch toàn cầu', 'Ưu đãi nhà hàng, khách sạn'], ['Yêu cầu thu nhập cao']],
        ['techcombank', 'Techcombank Visa Signature', 'visa', 1_490_000, 0, 30_000_000, 28.0, 55, 1.0, 1_500_000, 2.95, ['cashback', 'travel'], 'c8',
            'Hoàn tiền tới 1% và miễn lãi 55 ngày, kèm dịch vụ phòng chờ sân bay quốc tế.',
            ['Miễn lãi 55 ngày', 'Hoàn tiền 1%', 'Phòng chờ quốc tế'], ['Phí năm cao nếu chi tiêu ít']],
        ['techcombank', 'Techcombank Everyday', 'visa', 0, 0, 7_000_000, 29.0, 45, 0.5, 300_000, 3.0, ['cashback', 'shopping'], 'c4',
            'Thẻ miễn phí thường niên trọn đời, hoàn tiền cho mua sắm online và siêu thị.',
            ['Miễn phí thường niên', 'Hoàn tiền mua sắm online'], ['Trần hoàn tiền thấp']],
        ['vpbank', 'VPBank StepUP', 'mastercard', 499_000, 0, 4_500_000, 36.0, 55, 6.0, 600_000, 3.5, ['cashback', 'shopping', 'dining'], 'c1',
            'Hoàn tiền tới 6% cho ăn uống, mua sắm online và giải trí, phù hợp người trẻ.',
            ['Hoàn tiền cao ở danh mục chọn lọc', 'Thu nhập tối thiểu thấp'], ['Lãi suất cao nếu không trả đủ', 'Hoàn tiền thấp ngoài danh mục ưu đãi']],
        ['vpbank', 'VPBank Lady', 'mastercard', 499_000, 0, 4_500_000, 36.0, 55, 6.0, 600_000, 3.5, ['cashback', 'shopping'], 'c5',
            'Hoàn tiền mua sắm, làm đẹp, chăm sóc sức khỏe; kèm bảo hiểm sức khỏe cho chủ thẻ nữ.',
            ['Hoàn tiền mua sắm, làm đẹp', 'Bảo hiểm sức khỏe đi kèm'], ['Ưu đãi tập trung vào một nhóm chi tiêu']],
        ['vib', 'VIB Online Plus 2in1', 'visa', 899_000, 0, 7_000_000, 37.0, 55, 6.0, 600_000, 2.5, ['cashback', 'shopping'], 'c3',
            'Một thẻ dùng được cả tín dụng và thanh toán, hoàn tiền tới 6% khi mua sắm trực tuyến.',
            ['Thẻ 2 trong 1', 'Hoàn tiền online cao'], ['Phí thường niên khá cao', 'Lãi suất cao']],
        ['vib', 'VIB Travel Élite', 'mastercard', 1_499_000, 0, 20_000_000, 33.0, 55, null, null, 1.5, ['miles', 'travel'], 'c12',
            'Tích dặm bay đổi vé máy bay, phí giao dịch ngoại tệ thấp cho người hay đi nước ngoài.',
            ['Phí ngoại tệ thấp', 'Tích dặm linh hoạt'], ['Phí thường niên cao']],
        ['tpbank', 'TPBank EVO', 'visa', 0, 0, 4_000_000, 34.0, 45, 0.5, 200_000, 3.0, ['cashback', 'installment'], 'c6',
            'Mở hoàn toàn trên ứng dụng, miễn phí thường niên năm đầu, trả góp 0% tại nhiều cửa hàng.',
            ['Mở thẻ online', 'Trả góp 0%'], ['Hạn mức ban đầu thấp']],
        ['mb', 'MB Hi Collection', 'visa', 300_000, 0, 5_000_000, 30.0, 45, 0.5, 300_000, 2.95, ['shopping', 'installment'], 'c9',
            'Thẻ có nhiều mẫu thiết kế, ưu đãi mua sắm và trả góp lãi suất 0%.',
            ['Phí năm thấp', 'Trả góp 0%'], ['Hoàn tiền thấp']],
        ['acb', 'ACB Visa Platinum', 'visa', 899_000, 0, 15_000_000, 27.0, 45, 0.3, 500_000, 2.5, ['travel', 'dining'], 'c2',
            'Thẻ bạch kim với ưu đãi phòng chờ sân bay và giảm giá tại nhà hàng đối tác.',
            ['Lãi suất thấp', 'Phòng chờ sân bay'], ['Ưu đãi hoàn tiền hạn chế']],
        ['sacombank', 'Sacombank Visa Platinum Cashback', 'visa', 999_000, 0, 15_000_000, 28.0, 55, 5.0, 600_000, 2.5, ['cashback'], 'c10',
            'Hoàn tiền tới 5% cho giao dịch tại nước ngoài, giáo dục và y tế.',
            ['Hoàn tiền cao cho giáo dục, y tế', 'Miễn lãi 55 ngày'], ['Cần chi tiêu đủ ngưỡng mới được hoàn tiền tối đa']],
        ['sacombank', 'Sacombank JCB Ultimate', 'jcb', 1_999_000, 0, 50_000_000, 26.0, 55, null, null, 2.0, ['travel', 'dining'], 'c11',
            'Thẻ hạng cao nhất với hạn mức lớn, ưu đãi du lịch Nhật Bản và nhà hàng cao cấp.',
            ['Hạn mức cao', 'Ưu đãi du lịch Nhật Bản'], ['Thu nhập tối thiểu rất cao']],
        ['hdbank', 'HDBank Vietjet Platinum', 'visa', 1_000_000, 0, 10_000_000, 30.0, 55, null, null, 3.0, ['miles', 'travel'], 'c7',
            'Tích điểm SkyJoy đổi vé Vietjet, tặng vé máy bay khi đạt mức chi tiêu năm.',
            ['Đổi điểm lấy vé Vietjet', 'Tặng vé theo mức chi tiêu'], ['Ưu đãi gắn với một hãng bay']],
        ['shinhan', 'Shinhan Hi-Point', 'visa', 500_000, 0, 6_000_000, 28.0, 45, 0.5, 300_000, 2.5, ['cashback', 'shopping'], 'c4',
            'Tích điểm cho mọi chi tiêu và đổi thành tiền mặt, hoàn tiền thêm tại siêu thị.',
            ['Điểm quy đổi được thành tiền', 'Phí năm thấp'], ['Ít ưu đãi du lịch']],
        ['bidv', 'BIDV Visa Flexi', 'visa', 200_000, 100_000, 5_000_000, 26.0, 45, null, null, 2.5, ['installment'], 'c1',
            'Thẻ cơ bản với lãi suất thấp và chuyển đổi trả góp linh hoạt cho khoản chi lớn.',
            ['Lãi suất thấp', 'Phí năm thấp'], ['Ít ưu đãi hoàn tiền']],
        ['vietinbank', 'VietinBank Visa Premium Banking', 'visa', 1_500_000, 0, 50_000_000, 25.0, 55, 0.5, 1_000_000, 2.0, ['travel', 'dining'], 'c8',
            'Dành cho khách hàng ưu tiên, lãi suất thấp và quyền lợi phòng chờ quốc tế không giới hạn.',
            ['Lãi suất thấp nhất nhóm', 'Phòng chờ quốc tế'], ['Chỉ dành cho khách hàng ưu tiên']],
        ['fe-credit', 'FE Credit Shopee Card', 'mastercard', 0, 0, 3_000_000, 42.0, 45, 1.0, 200_000, 4.0, ['shopping', 'installment'], 'c3',
            'Thẻ cho người mới đi làm, duyệt nhanh, ưu đãi khi mua sắm trên sàn thương mại điện tử.',
            ['Duyệt nhanh, thu nhập tối thiểu thấp', 'Miễn phí thường niên'], ['Lãi suất rất cao', 'Phí ngoại tệ cao']],
        ['home-credit', 'Home Credit Mastercard', 'mastercard', 300_000, 0, 3_000_000, 40.0, 45, null, null, 4.0, ['installment'], 'c5',
            'Thẻ tín dụng dễ mở, trả góp 0% tại cửa hàng điện máy, điện thoại đối tác.',
            ['Dễ được duyệt', 'Trả góp 0% tại đối tác'], ['Lãi suất cao', 'Ít ưu đãi hoàn tiền']],
        ['mb', 'MB JCB Sakura', 'jcb', 600_000, 0, 10_000_000, 30.0, 45, 1.0, 500_000, 2.5, ['travel', 'dining'], 'c10',
            'Ưu đãi khi du lịch Nhật Bản, Hàn Quốc và tại nhà hàng Nhật trong nước.',
            ['Ưu đãi Nhật Bản, Hàn Quốc', 'Hoàn tiền 1%'], ['Mạng JCB ít điểm chấp nhận hơn Visa']],
    ];

    /** [ngân hàng, tên, phí/tháng, ghi chú phí, số dư tối thiểu, phí chuyển, phí rút ATM, tính năng, tóm tắt, ưu, nhược] */
    private const ACCOUNTS = [
        ['techcombank', 'Techcombank Tài khoản thanh toán', 0, 'Miễn phí quản lý tài khoản', 0, 0, 0, ['online_opening', 'free_transfer', 'vanity_number', 'international_debit_card'],
            'Miễn phí chuyển khoản và quản lý tài khoản, mở online trong vài phút.', ['Miễn mọi phí cơ bản', 'Chọn số tài khoản đẹp'], ['Phí rút ATM khác hệ thống sau số lần miễn phí']],
        ['vpbank', 'VPBank NEO', 0, 'Miễn phí quản lý tài khoản', 0, 0, 0, ['online_opening', 'free_transfer', 'auto_savings', 'vanity_number'],
            'Tài khoản số mở trên ứng dụng, có tính năng tiết kiệm tự động.', ['Mở online', 'Tiết kiệm tự động'], ['Ít phòng giao dịch hơn ngân hàng lớn']],
        ['tpbank', 'TPBank Tài khoản số', 0, 'Miễn phí quản lý tài khoản', 0, 0, 1_100, ['online_opening', 'free_transfer', 'vanity_number'],
            'Mở bằng eKYC, giao dịch tại máy LiveBank 24/7.', ['Máy LiveBank 24/7', 'Miễn phí chuyển khoản'], ['Phí rút ATM ngoài hệ thống']],
        ['mb', 'MB Tài khoản số đẹp', 0, 'Miễn phí năm đầu với số đẹp tiêu chuẩn', 0, 0, 1_100, ['online_opening', 'free_transfer', 'vanity_number'],
            'Chọn số tài khoản theo ngày sinh, số điện thoại, miễn phí chuyển khoản trên ứng dụng.', ['Số đẹp miễn phí', 'Ứng dụng nhiều tiện ích'], ['Số đẹp cao cấp tính phí']],
        ['vietcombank', 'Vietcombank Tài khoản thanh toán', 11_000, 'Miễn phí khi số dư bình quân từ 2 triệu đồng', 50_000, 0, 1_100, ['international_debit_card', 'foreign_currency'],
            'Tài khoản phổ biến, mạng ATM và phòng giao dịch rộng khắp.', ['Mạng lưới rộng', 'Hỗ trợ ngoại tệ'], ['Có phí quản lý nếu số dư thấp']],
        ['bidv', 'BIDV SmartBanking', 10_000, 'Phí quản lý tài khoản hằng tháng', 50_000, 0, 1_100, ['online_opening', 'international_debit_card'],
            'Mở online và nhận thẻ ghi nợ quốc tế, phù hợp người cần giao dịch tại quầy.', ['Nhiều chi nhánh', 'Thẻ ghi nợ quốc tế'], ['Có phí quản lý hằng tháng']],
        ['acb', 'ACB ONE', 0, 'Miễn phí với gói tài khoản online', 0, 0, 0, ['online_opening', 'free_transfer', 'auto_savings'],
            'Gói tài khoản online miễn phí, kèm tiết kiệm tự động từ số dư nhàn rỗi.', ['Miễn phí', 'Tiết kiệm tự động'], ['Ưu đãi ít hơn ở giao dịch tại quầy']],
        ['vib', 'VIB MyVIB', 0, 'Miễn phí quản lý tài khoản', 0, 0, 0, ['online_opening', 'free_transfer', 'foreign_currency'],
            'Mở tài khoản trên ứng dụng, miễn phí chuyển khoản và hỗ trợ tài khoản ngoại tệ.', ['Hỗ trợ ngoại tệ', 'Miễn phí chuyển khoản'], ['Mạng ATM nhỏ hơn']],
        ['sacombank', 'Sacombank Pay', 0, 'Miễn phí trên ứng dụng', 0, 0, 1_100, ['online_opening', 'free_transfer', 'international_debit_card'],
            'Tài khoản và thẻ ghi nợ quốc tế mở trên Sacombank Pay.', ['Thẻ ghi nợ quốc tế', 'Mở online'], ['Phí rút ATM ngoài hệ thống']],
        ['hdbank', 'HDBank Tài khoản thanh toán', 5_500, 'Miễn phí khi có giao dịch lương', 50_000, 0, 1_100, ['international_debit_card'],
            'Tài khoản truyền thống, miễn phí quản lý khi nhận lương qua HDBank.', ['Miễn phí khi nhận lương'], ['Có phí nếu không nhận lương']],
    ];

    /** @var array<string, FinancialInstitution> */
    private array $institutions = [];

    public function __construct(private DemoManifest $manifest) {}

    public function run(): array
    {
        mt_srand(2026);
        foreach (self::INSTITUTIONS as $slug => [$name, $short, $website, $type]) {
            $logo = $this->copyAsset("logos/$slug.png", FinancialInstitution::LOGO_DIRECTORY);
            $this->institutions[$slug] = $this->manifest->track(FinancialInstitution::create([
                'type' => InstitutionType::from($type), 'name' => $name, 'short_name' => $short, 'slug' => $this->uniqueInstitutionSlug($slug),
                'logo_path' => $logo, 'website' => $website, 'is_active' => true,
            ]));
        }

        $savings = $this->savings();
        $cards = $this->cards();
        $accounts = $this->accounts();
        $links = $this->partnerLinks([...$cards, ...$accounts, ...$savings]);

        return ['institutions' => count($this->institutions), 'savings' => count($savings), 'cards' => count($cards),
            'accounts' => count($accounts), 'links' => $links];
    }

    /** @return list<FinancialProduct> */
    private function savings(): array
    {
        $products = [];
        foreach (self::INSTITUTIONS as $slug => [, $short, , $type, $offset]) {
            if ($type !== 'bank') {
                continue;
            }
            foreach (['online' => [0, 'Tiết kiệm online'], 'counter' => [-0.2, 'Tiết kiệm tại quầy']] as $channel => [$channelOffset, $label]) {
                $product = $this->product(ProductType::Savings, $slug, "$short $label",
                    "$label của $short, lãi trả cuối kỳ, gửi từ 1 triệu đồng.", null,
                    $channel === 'online' ? ['Lãi cao hơn gửi tại quầy', 'Mở và tất toán trên ứng dụng'] : ['Gửi và rút tại mọi chi nhánh'],
                    $channel === 'online' ? ['Cần dùng ngân hàng điện tử'] : ['Lãi thấp hơn gửi online']);
                // Ba lần công bố biểu lãi (khoảng 6 tháng, 3 tháng trước và gần đây) để trang ngân hàng có biểu đồ lịch sử.
                $tables = [[mt_rand(170, 190), -mt_rand(30, 60) / 100], [mt_rand(80, 100), -mt_rand(5, 30) / 100], [mt_rand(3, 25), 0]];
                foreach (self::BASE_RATES as $term => $base) {
                    $rate = round($base + $offset + $channelOffset + mt_rand(-10, 10) / 100, 2);
                    foreach ($tables as [$daysAgo, $change]) {
                        $value = round($rate + $change, 2);
                        $value = $term < 6 ? min($value, 4.75) : $value;
                        $product->savingsRates()->create(['term_months' => $term, 'channel' => $channel,
                            'interest_payment' => 'end_of_term', 'min_amount' => 1_000_000, 'rate' => max($value, 1.6),
                            'effective_from' => today()->subDays($daysAgo)->toDateString()]);
                    }
                }
                if ($channel === 'counter') {
                    $product->savingsRates()->create(['term_months' => 0, 'channel' => $channel,
                        'interest_payment' => 'end_of_term', 'rate' => 0.1, 'effective_from' => today()->subDays(20)->toDateString()]);
                }
                $products[] = $product;
            }
        }

        return $products;
    }

    /** @return list<FinancialProduct> */
    private function cards(): array
    {
        $products = [];
        foreach (self::CARDS as [$bank, $name, $network, $fee, $issue, $income, $rate, $days, $cashback, $cap, $fx, $benefits, $image, $summary, $pros, $cons]) {
            $product = $this->product(ProductType::CreditCard, $bank, $name, $summary, $this->copyAsset("cards/$image.png", FinancialProduct::IMAGE_DIRECTORY), $pros, $cons);
            $product->creditCard()->create(['network' => $network, 'annual_fee' => $fee,
                'annual_fee_note' => $fee > 0 ? 'Miễn phí năm đầu khi chi tiêu đủ điều kiện' : null, 'issuance_fee' => $issue,
                'min_monthly_income' => $income, 'interest_rate' => $rate, 'interest_free_days' => $days, 'cashback_rate' => $cashback,
                'cashback_monthly_cap' => $cap, 'foreign_transaction_fee' => $fx, 'benefits' => $benefits]);
            $products[] = $product;
        }

        return $products;
    }

    /** @return list<FinancialProduct> */
    private function accounts(): array
    {
        $products = [];
        foreach (self::ACCOUNTS as [$bank, $name, $fee, $note, $min, $transfer, $atm, $features, $summary, $pros, $cons]) {
            $product = $this->product(ProductType::BankAccount, $bank, $name, $summary, null, $pros, $cons);
            $product->bankAccount()->create(['monthly_fee' => $fee, 'monthly_fee_note' => $note,
                'min_balance' => $min, 'transfer_fee' => $transfer, 'atm_withdrawal_fee' => $atm, 'features' => $features]);
            $products[] = $product;
        }

        return $products;
    }

    /** Link đối tác cho khoảng 2/3 sản phẩm, kèm lượt bấm 60 ngày gần nhất để trang thống kê có số liệu. */
    private function partnerLinks(array $products): int
    {
        $count = 0;
        foreach ($products as $i => $product) {
            if ($i % 3 === 2) {
                continue;
            }
            $cta = match ($product->type) {
                ProductType::CreditCard => 'Mở thẻ',
                ProductType::BankAccount => 'Mở tài khoản',
                default => 'Gửi online',
            };
            $link = $this->manifest->track(OutboundLink::create(['product_id' => $product->id, 'name' => $product->name,
                'partner' => 'Demo', 'destination_url' => $product->institution->website, 'cta_label' => $cta, 'is_active' => true]));
            $rows = [];
            foreach (range(1, mt_rand(5, 90)) as $n) {
                $rows[] = ['link_id' => $link->id, 'click_id' => Str::lower(Str::random(24)), 'ip_hash' => hash('sha256', Str::random(16)),
                    'user_agent' => 'Mozilla/5.0 (demo)', 'referer_path' => $product->type === ProductType::CreditCard ? '/the-tin-dung' : '/lai-suat-tiet-kiem',
                    'created_at' => now()->subMinutes(mt_rand(1, 60 * 24 * 60))];
            }
            OutboundClick::insert($rows);
            $count++;
        }

        return $count;
    }

    private function product(ProductType $type, string $bank, string $name, string $summary, ?string $image, array $pros, array $cons): FinancialProduct
    {
        $institution = $this->institutions[$bank];
        $verified = today()->subDays(mt_rand(2, 40));
        $product = new FinancialProduct([
            'type' => $type, 'institution_id' => $institution->id, 'name' => $name, 'slug' => $this->uniqueProductSlug($name),
            'status' => ProductStatus::Published, 'summary' => $summary, 'pros' => $pros, 'cons' => $cons, 'image_path' => $image,
            'source_url' => $institution->website, 'source_note' => self::SOURCE_NOTE,
            'verified_at' => $verified, 'next_review_at' => $verified->copy()->addMonths(FinancialProduct::REVIEW_INTERVAL_MONTHS),
        ]);
        $product->save();
        $product->setRelation('institution', $institution);

        return $this->manifest->track($product);
    }

    private function copyAsset(string $asset, string $directory): string
    {
        $path = $directory.'/demo-'.Str::lower(Str::random(8)).'-'.basename($asset);
        Storage::disk('public')->put($path, file_get_contents(__DIR__.'/assets/'.$asset));

        return $this->manifest->trackFile($path);
    }

    private function uniqueInstitutionSlug(string $slug): string
    {
        return FinancialInstitution::where('slug', $slug)->exists() ? "$slug-demo" : $slug;
    }

    private function uniqueProductSlug(string $name): string
    {
        $slug = Str::slug($name);

        return FinancialProduct::where('slug', $slug)->exists() ? "$slug-demo" : $slug;
    }
}
