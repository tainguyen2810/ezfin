<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Cài đặt EzFin</title>
    {{-- Trang độc lập, không phụ thuộc Vite: chạy được cả khi chưa build giao diện. --}}
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #f1f5f9; color: #0f172a; line-height: 1.5; }
        main { max-width: 34rem; margin: 0 auto; padding: 2rem 1rem 3rem; }
        h1 { font-size: 1.5rem; margin: 0 0 .25rem; }
        h2 { font-size: 1.125rem; margin: 0 0 1rem; }
        p { margin: 0 0 1rem; }
        .muted { color: #475569; font-size: .875rem; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; padding: 1.25rem; margin-top: 1.25rem; }
        ol.steps { display: flex; gap: .5rem; list-style: none; padding: 0; margin: 1.25rem 0 0; counter-reset: step; }
        ol.steps li { flex: 1; font-size: .75rem; color: #64748b; border-top: 3px solid #cbd5e1; padding-top: .375rem; counter-increment: step; }
        ol.steps li::before { content: counter(step) ". "; }
        ol.steps li.done { border-color: #15803d; color: #15803d; }
        ol.steps li.current { border-color: #1d4ed8; color: #1d4ed8; font-weight: 600; }
        ul.checks { list-style: none; padding: 0; margin: 0 0 1rem; }
        ul.checks li { padding: .375rem 0; border-bottom: 1px solid #f1f5f9; font-size: .9375rem; }
        .ok { color: #15803d; font-weight: 600; }
        .fail { color: #b91c1c; font-weight: 600; }
        .hint { display: block; color: #475569; font-size: .8125rem; margin-left: 1.5rem; }
        label { display: block; font-size: .875rem; font-weight: 600; margin: .75rem 0 .25rem; }
        input { width: 100%; font: inherit; padding: .5rem .75rem; border: 1px solid #94a3b8; border-radius: .5rem; background: #fff; }
        input:focus { outline: 2px solid #1d4ed8; outline-offset: 1px; }
        .row { display: grid; grid-template-columns: 1fr 6rem; gap: .75rem; }
        button, .button { display: inline-block; margin-top: 1.25rem; font: inherit; font-weight: 600; color: #fff; background: #1d4ed8; border: 0; border-radius: .5rem; padding: .625rem 1.25rem; cursor: pointer; text-decoration: none; }
        button:hover, .button:hover { background: #1e40af; }
        .alert { border-radius: .5rem; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .875rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert ul { margin: 0; padding-left: 1.25rem; }
        code { font-size: .8125rem; background: #f1f5f9; padding: .125rem .25rem; border-radius: .25rem; word-break: break-all; }
    </style>
</head>
<body>
<main>
    <h1>Cài đặt EzFin</h1>
    <p class="muted">Làm lần lượt từng bước. Khi tạo xong tài khoản quản trị, trang này tự khóa.</p>

    @php($keys = array_keys($steps))
    <ol class="steps">
        @foreach ($steps as $key => $label)
            <li @class(['done' => array_search($key, $keys) < array_search($step, $keys), 'current' => $key === $step])>{{ $label }}</li>
        @endforeach
    </ol>

    <section class="card">
        <h2>{{ $steps[$step] }}</h2>

        @if ($problems)
            <div class="alert" role="alert">
                <ul>
                    @foreach ($problems as $problem)
                        <li>{{ $problem }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($step === 'requirements')
            <p>Máy chủ chưa đủ điều kiện. Sửa các mục đánh dấu ✗ rồi kiểm tra lại.</p>
            <ul class="checks">
                @foreach ($requirements as $check)
                    <li>
                        <span class="{{ $check['ok'] ? 'ok' : 'fail' }}">{{ $check['ok'] ? '✓' : '✗' }}</span>
                        {{ $check['label'] }}
                        @unless ($check['ok'])
                            <span class="hint">{{ $check['hint'] }}</span>
                        @endunless
                    </li>
                @endforeach
            </ul>
            <a class="button" href="{{ route('install.show') }}">Kiểm tra lại</a>

        @elseif ($step === 'database')
            <p>Nhập thông tin MySQL. Nếu database chưa có, EzFin sẽ tự tạo (cần quyền tạo database; trên WAMP dùng tài khoản <code>root</code>, mật khẩu để trống).</p>
            @if ($databaseError && ! $problems)
                <p class="muted">Chưa kết nối được: {{ $databaseError }}</p>
            @endif
            <form method="post" action="{{ route('install.database') }}">
                <input type="hidden" name="_install" value="{{ $token }}">
                <div class="row">
                    <div>
                        <label for="host">Máy chủ</label>
                        <input id="host" name="host" value="{{ $old['host'] ?? $db['host'] }}" required>
                    </div>
                    <div>
                        <label for="port">Cổng</label>
                        <input id="port" name="port" inputmode="numeric" value="{{ $old['port'] ?? $db['port'] }}" required>
                    </div>
                </div>
                <label for="database">Tên database</label>
                <input id="database" name="database" value="{{ $old['database'] ?? $db['database'] }}" required>
                <label for="username">Tài khoản MySQL</label>
                <input id="username" name="username" value="{{ $old['username'] ?? $db['username'] }}" autocomplete="off" required>
                <label for="password">Mật khẩu MySQL</label>
                <input id="password" name="password" type="password" autocomplete="off">
                <button type="submit">Lưu và kết nối</button>
            </form>

        @elseif ($step === 'migrate')
            <p>Đã kết nối database <code>{{ $db['database'] }}</code>. Bấm nút dưới đây để tạo các bảng dữ liệu (mất khoảng vài chục giây).</p>
            <form method="post" action="{{ route('install.migrate') }}">
                <input type="hidden" name="_install" value="{{ $token }}">
                <button type="submit">Tạo bảng</button>
            </form>

        @else
            <p>Tạo tài khoản quản trị cao nhất. Sau bước này bạn đăng nhập trang quản trị bằng email và mật khẩu dưới đây.</p>
            <form method="post" action="{{ route('install.admin') }}">
                <input type="hidden" name="_install" value="{{ $token }}">
                <label for="name">Họ tên</label>
                <input id="name" name="name" value="{{ $old['name'] ?? '' }}" autocomplete="name" required>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ $old['email'] ?? '' }}" autocomplete="email" required>
                <label for="password">Mật khẩu</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required>
                <span class="muted">Tối thiểu 12 ký tự, có chữ hoa, chữ thường và số.</span>
                <label for="password_confirmation">Nhập lại mật khẩu</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required>
                <button type="submit">Tạo tài khoản</button>
            </form>
        @endif
    </section>
</main>
</body>
</html>
