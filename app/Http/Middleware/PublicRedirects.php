<?php

namespace App\Http\Middleware;

use App\Modules\Core\Models\Redirect;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trên website công khai: bỏ dấu / cuối URL (một trang một URL) và chuyển URL cũ không còn trang
 * theo bảng redirects. Chỉ tra bảng khi trang trả 404.
 */
class PublicRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethodSafe() || $request->getHost() !== config('ezfin.domains.public')) {
            return $next($request);
        }

        $path = $request->getPathInfo();

        if ($path !== '/' && str_ends_with($path, '/')) {
            return redirect()->to($this->withQuery('/'.trim($path, '/'), $request), 301);
        }

        $response = $next($request);

        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        try {
            $redirect = Redirect::where('from_path', Redirect::normalizePath($path))->first();
        } catch (QueryException $exception) {
            // Lỗi database không được biến trang 404 thành lỗi 500.
            report($exception);

            return $response;
        }

        if (! $redirect) {
            return $response;
        }

        $redirect->newQuery()->whereKey($redirect->id)->update(['hits' => $redirect->hits + 1, 'last_hit_at' => now()]);

        return redirect()->to($this->withQuery($redirect->to_url, $request), $redirect->status_code);
    }

    /** Giữ tham số (utm_..., bộ lọc) khi đích chưa có query riêng. */
    private function withQuery(string $target, Request $request): string
    {
        $query = $request->getQueryString();

        return $query && ! str_contains($target, '?') ? $target.'?'.$query : $target;
    }
}
