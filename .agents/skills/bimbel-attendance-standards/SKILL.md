---
name: bimbel-attendance-standards
description: Standar arsitektur, panduan koding, keamanan anti-hacker, dan implementasi 13 pilar aplikasi absensi bimbel (Laravel 12/13 + Vue 3 SPA + JWT + Redis + UUID + Multi-Tenant). Gunakan skill ini saat mendesain model, controller, service, rute, komponen Vue, dan pengamanan sistem.
---

# Skill: Bimbel Attendance Engineering Standards

Skill ini berisi panduan implementasi teknis dan cetak biru (blueprints) untuk 13 pilar arsitektur aplikasi absensi bimbingan belajar masa kini.

---

## 1. Blueprint: UUID & Base Model

Setiap model entitas wajib mewarisi `App\Models\BaseModel` atau menggunakan traits berikut:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\TenantScope;

abstract class BaseModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted(): void
    {
        // Pasang TenantScope otomatis jika tabel memiliki kolom tenant_id
        if (in_array('tenant_id', (new static)->getFillable()) || (new static)->usesTenantScope()) {
            static::addGlobalScope(new TenantScope);
            
            static::creating(function ($model) {
                if (auth()->check() && empty($model->tenant_id)) {
                    $model->tenant_id = auth()->user()->tenant_id;
                }
            });
        }
    }

    public function usesTenantScope(): bool
    {
        return true;
    }
}
```

---

## 2. Blueprint: Try-Catch & Unified API Response Trait

Buat trait `App\Traits\ApiResponse`:

```php
namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

trait ApiResponse
{
    public function success(mixed $data = null, string $message = 'Operasi berhasil dilakukan', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    public function error(string $message = 'Terjadi kesalahan sistem', int $code = 500, mixed $errors = null, ?\Throwable $exception = null): JsonResponse
    {
        if ($exception) {
            Log::error($exception->getMessage(), [
                'endpoint' => request()->fullUrl(),
                'method'   => request()->method(),
                'user_id'  => auth()->id(),
                'trace'    => $exception->getTraceAsString(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => config('app.debug') && $exception ? $exception->getMessage() : $message,
            'errors'  => $errors,
        ], $code);
    }
}
```

---

## 3. Blueprint: Vue 3 Notification Composable (Toast & SweetAlert2)

`resources/js/Composables/useNotification.ts`:

```typescript
import Swal, { type SweetAlertOptions } from 'sweetalert2';

export function useNotification() {
    // Toast notification (Auto dismiss 3s)
    const toast = (message: string, icon: 'success' | 'error' | 'warning' | 'info' = 'success') => {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toastEl) => {
                toastEl.onmouseenter = Swal.stopTimer;
                toastEl.onmouseleave = Swal.resumeTimer;
            }
        });

        Toast.fire({
            icon,
            title: message
        });
    };

    // Konfirmasi SweetAlert2 untuk aksi penting/destruktif
    const confirmAction = async (
        title: string = 'Apakah Anda yakin?',
        text: string = 'Tindakan ini tidak dapat dibatalkan!',
        confirmButtonText: string = 'Ya, Lanjutkan'
    ): Promise<boolean> => {
        const result = await Swal.fire({
            title,
            text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5', // Indigo 600
            cancelButtonColor: '#ef4444',  // Red 500
            confirmButtonText,
            cancelButtonText: 'Batal',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-2xl font-sans',
                confirmButton: 'px-5 py-2.5 rounded-lg text-sm font-semibold shadow-sm',
                cancelButton: 'px-5 py-2.5 rounded-lg text-sm font-semibold'
            }
        });

        return result.isConfirmed;
    };

    return { toast, confirmAction };
}
```

---

## 4. Blueprint: Paginasi & Pencarian Dinamis

Trait `App\Traits\Filterable`:

```php
namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    public function scopeApplyFilters(Builder $query, Request $request, array $searchableColumns = []): Builder
    {
        // 1. Search filter (debounce dari frontend)
        if ($search = $request->query('search')) {
            $query->where(function (Builder $q) use ($search, $searchableColumns) {
                foreach ($searchableColumns as $index => $column) {
                    if ($index === 0) {
                        $q->where($column, 'LIKE', "%{$search}%");
                    } else {
                        $q->orWhere($column, 'LIKE', "%{$search}%");
                    }
                }
            });
        }

        // 2. Sorting
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = strtolower($request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query;
    }
}
```

---

## 5. Blueprint: Redis Queue untuk Proses Resource Berat

Pengiriman WhatsApp/Email kehadiran ke orang tua harus dijalankan di background Redis:

```php
namespace App\Jobs;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAttendanceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Attendance $attendance)
    {
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        // Kirim notifikasi via Redis Queue tanpa membebani response time user
    }
}
```

---

## 6. Blueprint: Keamanan Anti-Hacker (OWASP Checklist)

1. **SQL Injection**:
   - Selalu gunakan Eloquent / Query Builder parameter binding.
   - Jangan gunakan `DB::raw("... $variable ...")`.
2. **IDOR & Cross-Tenant Leak**:
   - Setiap model selalu terikat `TenantScope`.
   - Validasi `tenant_id` pada setiap rute mutasi data.
3. **Cross-Site Scripting (XSS)**:
   - Output string di Vue wajib menggunakan interpolasi standar `{{ data }}`.
   - Hindari `v-html` untuk data yang berasal dari input pengguna.
4. **Brute Force Defense**:
   - Pasang `throttle:login` (maksimal 5 kali salah password per 60 detik).
   - Implementasikan lockout bertahap.
5. **Data Tampering**:
   - Semua input wajib divalidasi dengan Form Request (`php artisan make:request ...`).
   - Tolak data yang tidak lolos aturan validasi tipe dan ukuran data.
