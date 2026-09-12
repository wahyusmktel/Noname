<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait ApiResponse
{
    /**
     * Return standardized success JSON response.
     */
    public function success(mixed $data = null, string $message = 'Operasi berhasil', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Return standardized error JSON response with safety logging.
     */
    public function error(string $message = 'Terjadi kesalahan pada sistem', int $code = 500, mixed $errors = null, ?\Throwable $exception = null): JsonResponse
    {
        if ($exception) {
            Log::error($exception->getMessage(), [
                'endpoint' => request()->fullUrl(),
                'method'   => request()->method(),
                'ip'       => request()->ip(),
                'user_id'  => auth()->id(),
                'trace'    => $exception->getTraceAsString(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => (config('app.debug') && $exception) ? $exception->getMessage() : $message,
            'errors'  => $errors,
        ], $code);
    }
}
