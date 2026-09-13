<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDevEnvironmentActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $isDev = config('app.env') === 'local' 
            || str_starts_with($host, 'dev.') 
            || str_contains($host, 'bimbel-dev') 
            || config('app.is_dev_instance', false);

        // Jika bukan di lingkungan dev (misalnya di production bimbelnoname.com), langsung loloskan
        if (!$isDev) {
            return $next($request);
        }

        // Jalur khusus pengecekan kesehatan server / bypass
        if ($request->is('up') || $request->is('api/dev-status')) {
            return $next($request);
        }

        // Dukungan bypass rahasia untuk developer / admin penguji
        if ($request->query('bypass_dev') === 'bnn_dev_bypass_2026' || $request->cookie('dev_bypass') === 'granted') {
            $response = $next($request);
            if ($request->query('bypass_dev') === 'bnn_dev_bypass_2026') {
                $response->cookie('dev_bypass', 'granted', 1440); // 24 jam
            }
            return $response;
        }

        // Cek file status dev
        $statusFile = storage_path('framework/dev_status.json');
        $isActive = true;
        $inactiveMessage = null;

        if (file_exists($statusFile)) {
            $data = json_decode(file_get_contents($statusFile), true);
            if (is_array($data) && isset($data['active'])) {
                $isActive = (bool) $data['active'];
                $inactiveMessage = $data['message'] ?? null;
            }
        }

        if (!$isActive) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success'    => false,
                    'message'    => $inactiveMessage ?: 'Website development sedang tidak aktif.',
                    'error_code' => 'DEV_ENVIRONMENT_INACTIVE'
                ], 503);
            }

            return response()->view('errors.dev-inactive', [
                'message' => $inactiveMessage
            ], 503);
        }

        return $next($request);
    }
}
