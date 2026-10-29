<x-ui.money-input name="so_tien" label="Số tiền gửi" :value="$input['so_tien']" required />
<x-ui.input name="lai_suat" label="Lãi suất (%/năm)" :value="$input['lai_suat']" inputmode="decimal" required
    hint="Nhập đúng mức ngân hàng công bố cho kỳ hạn và cách lĩnh lãi bạn chọn." />
<x-ui.input name="ky_han" label="Kỳ hạn (tháng)" type="number" min="1" max="60" :value="$input['ky_han']" required />
<x-ui.select name="linh_lai" label="Lĩnh lãi" :value="$input['linh_lai']"
    :options="collect(\App\Modules\Planner\Enums\DepositPayout::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()])" />
