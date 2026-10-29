<x-ui.money-input name="so_tien" label="Số tiền vay" :value="$input['so_tien']" required />
<x-ui.input name="lai_suat" label="Lãi suất (%/năm)" :value="$input['lai_suat']" inputmode="decimal" required />
<x-ui.input name="so_nam" label="Thời hạn vay (năm)" type="number" min="1" max="35" :value="$input['so_nam']" required />
<x-ui.select name="cach_tra" label="Cách trả nợ" :value="$input['cach_tra']" :options="\App\Modules\Planner\Tools\LoanTool::methods()" />
