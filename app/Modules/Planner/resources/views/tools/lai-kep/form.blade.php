<x-ui.money-input name="so_tien" label="Số tiền ban đầu" :value="$input['so_tien']" required />
<x-ui.money-input name="gop_them" label="Góp thêm mỗi tháng" :value="$input['gop_them']" hint="Nhập 0 nếu không góp thêm." />
<x-ui.input name="lai_suat" label="Lãi suất (%/năm)" :value="$input['lai_suat']" inputmode="decimal" required />
<x-ui.input name="so_nam" label="Số năm" type="number" min="1" max="50" :value="$input['so_nam']" required />
<x-ui.select name="ghep_lai" label="Ghép lãi" :value="$input['ghep_lai']"
    :options="collect(\App\Modules\Planner\Enums\Compounding::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])" />
