<?php

namespace App\Modules\Planner\Http\Controllers;

use App\Modules\Planner\Calculators\CalculationLimitExceeded;
use App\Modules\Planner\Tools\ToolRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

class ToolController
{
    public function index(ToolRegistry $tools): View
    {
        return view('planner::tools.index', ['tools' => $tools->all()]);
    }

    public function show(Request $request, string $slug, ToolRegistry $tools): View|RedirectResponse
    {
        $tool = $tools->find($slug) ?? abort(404);
        $keys = array_keys($tool->defaults());

        // Một bộ số liệu = một URL gọn (số tiền không dấu chấm, bỏ giá trị mặc định); giữ tham số khác như utm_*.
        $query = $tool->normalize($request->query());
        if ($query != Arr::only($request->query(), $keys)) {
            return redirect()->route('planner.tools.show', [$tool->slug(), ...Arr::except($request->query(), $keys), ...$query]);
        }

        $validator = Validator::make([...$tool->defaults(), ...$query], $tool->rules(), $tool->messages());
        $data = null;

        if ($validator->passes()) {
            try {
                $data = $tool->calculate($validator->validated());
            } catch (CalculationLimitExceeded $exception) {
                $validator->errors()->add('tinh_toan', $exception->getMessage());
            }
        }

        // Ô nhập (x-ui.*) đọc lỗi từ biến $errors dùng chung.
        view()->share('errors', (new ViewErrorBag)->put('default', $validator->errors()));

        return view('planner::tools.show', [
            'tool' => $tool,
            'input' => [...$tool->defaults(), ...$query],
            'data' => $data,
            'customized' => $query !== [],
            'shareUrl' => route('planner.tools.show', [$tool->slug(), ...$query]),
        ]);
    }
}
