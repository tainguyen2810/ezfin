<x-ui.money-input name="gia" label="Giá sản phẩm" :value="$input['gia']" required />
<x-ui.input name="tra_truoc" label="Trả trước (%)" type="number" min="0" max="90" :value="$input['tra_truoc']" required />
<x-ui.input name="lai_suat" label="Lãi suất (%/năm)" :value="$input['lai_suat']" inputmode="decimal" required
    hint="Nhập đúng mức ghi trên hợp đồng hoặc bảng tính của bên cho vay." />
<x-ui.input name="so_thang" label="Kỳ hạn (tháng)" type="number" min="1" max="60" :value="$input['so_thang']" required />
<x-ui.select name="cach_tinh" label="Cách tính lãi" :value="$input['cach_tinh']" :options="\App\Modules\Planner\Tools\InstallmentTool::methods()" />
