<x-ui.money-input name="gui_hang_thang" label="Gửi mỗi tháng" :value="$input['gui_hang_thang']" required />
<x-ui.money-input name="so_tien_ban_dau" label="Số tiền gửi ban đầu" :value="$input['so_tien_ban_dau']" hint="Nhập 0 nếu không có." />
<x-ui.input name="lai_suat" label="Lãi suất (%/năm)" :value="$input['lai_suat']" inputmode="decimal" required />
<x-ui.input name="so_thang" label="Số tháng gửi" type="number" min="1" max="120" :value="$input['so_thang']" required />
