# STANDAR PENGEMBANGAN DAN ATURAN KODING SISTEM ABSENSI BIMBEL
> **Stack**: Laravel 12/13 + Vue 3 (SPA) + MySQL + Redis + Tailwind CSS  
> **Target**: Aplikasi Absensi Bimbingan Belajar Multi-Tenant & Multi-Role Berstandar Enterprise

Dokumen ini adalah pedoman mutlak (mandatory rules) yang wajib dipatuhi dalam setiap penulisan kode, perancangan database, arsitektur backend, frontend, hingga sistem keamanan agar konsisten, profesional, dan kebal dari ancaman keamanan siber (OWASP Top 10).

---

## 1. Primary Key Menggunakan UUID
- **Wajib UUID v4**: Seluruh tabel bisnis tidak boleh menggunakan auto-incrementing integer sebagai primary key ID publik.
- **Model Eloquent**:
  ```php
  use Illuminate\Database\Eloquent\Concerns\HasUuids;
  use Illuminate\Database\Eloquent\Model;
  
  abstract class BaseModel extends Model
  {
      use HasUuids;
      
      protected $keyType = 'string';
      public $incrementing = false;
  }
  ```
- **Migration**:
  ```php
  $table->uuid('id')->primary();
  $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
  $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
  ```
- **Keamanan**: Mencegah serangan *ID Enumeration* dan *Insecure Direct Object Reference (IDOR)*.

---

## 2. Penanganan Error dengan Try & Catch Terstruktur
- **Wajib Try-Catch**: Setiap method di Controller, Service, dan Job wajib dibungkus dalam blok `try ... catch (\Throwable $e)`.
- **Standar Response API**:
  Gunakan format seragam (Unified JSON Response):
  ```php
  // Format Response Sukses
  return response()->json([
      'success' => true,
      'message' => 'Data kehadiran berhasil dicatat.',
      'data' => $data
  ], 200);

  // Format Response Error
  \Illuminate\Support\Facades\Log::error($e->getMessage(), [
      'file' => $e->getFile(),
      'line' => $e->getLine(),
      'trace' => $e->getTraceAsString()
  ]);
  
  return response()->json([
      'success' => false,
      'message' => config('app.debug') ? $e->getMessage() : 'Terjadi kendala pada sistem. Silakan hubungi admin.',
      'error_code' => 'ATTENDANCE_RECORD_FAILED'
  ], 500);
  ```
- **Keamanan**: Larang keras mengekspos SQL Query, DB password, stack trace internal ke klien di mode produksi (*Information Leakage Prevention*).

---

## 3. Sistem Notifikasi: Toast & SweetAlert2
- **Feedback Langsung (Toast)**:
  - Setiap aksi sukses, warning, atau error langsung memunculkan **Toast notification** di pojok kanan atas dengan auto-dismiss (3-4 detik).
- **Konfirmasi Aksi Destruktif (SweetAlert2)**:
  - Setiap aksi penghapusan data, pembatalan absensi, reset credential, atau mutasi data massal **WAJIB** meminta konfirmasi pengguna melalui modal dialog SweetAlert2 sebelum dieksekusi.
  - Alur konfirmasi:
    1. Pengguna klik tombol "Hapus Sesi".
    2. Dialog SweetAlert muncul: `"Apakah Anda yakin ingin menghapus data ini? Data yang dihapus akan masuk ke arsip (soft delete)."`
    3. Jika pengguna konfirmasi `"Ya, Lanjutkan"`, proses dikirimkan ke backend.
    4. Setelah respons berhasil, muncul Toast: `"Data berhasil dihapus."`

---

## 4. Penerapan Soft Deletes
- **Audit & Pemulihan**: Seluruh tabel entitas utama (`users`, `students`, `tutors`, `classes`, `attendance_sessions`, `attendances`, `tenants`) wajib menerapkan `SoftDeletes`.
- **Model**:
  ```php
  use Illuminate\Database\Eloquent\SoftDeletes;
  
  class Student extends BaseModel
  {
      use SoftDeletes;
      protected $dates = ['deleted_at'];
  }
  ```
- **Migration**:
  ```php
  $table->softDeletes();
  ```
- Data tidak boleh langsung di-`forceDelete()` kecuali oleh Superadmin melalui prosedur khusus.

---

## 5. Standar Timestamps (`created_at` & `updated_at`)
- Setiap tabel wajib menyertakan `$table->timestamps()`.
- Untuk tabel transaksi log berkecepatan tinggi (seperti `attendance_logs`), buat index pada kolom `created_at`:
  ```php
  $table->timestamp('created_at')->index();
  ```

---

## 6. Autentikasi Menggunakan JSON Web Token (JWT)
- Sistem autentikasi berbasis Stateless JWT.
- Token memuat payload klaim:
  - `sub`: User UUID
  - `tenant_id`: UUID Bimbel terkait
  - `role`: Role pengguna (`admin_bimbel`, `tutor`, `siswa`, dll.)
- **Perlindungan Keamanan Autentikasi**:
  - Implementasikan Rate Limiting ketat pada endpoint login (`throttle:5,1` - maks 5 percobaan per menit per IP).
  - Password wajib di-hash menggunakan algoritma Bcrypt / Argon2ID dengan cost factor aman.
  - Implementasikan Token Refresh dan Blacklisting saat logout.

---

## 7. Redis untuk Proses Resource Berat & Caching
- **Queue Asinkron (Redis Queue)**:
  - Pengiriman notifikasi WhatsApp / Email ke orang tua saat siswa hadir/alpa.
  - Generate rekap laporan kehadiran bulanan (PDF / Excel).
  - Kalkulasi persentase kehadiran massal.
- **Redis Caching**:
  - Cache konfigurasi tenant, daftar mata pelajaran, dan role permission dengan TTL yang sesuai.
  - Selalu bersihkan (*invalidate*) cache saat ada perubahan data terkait.

---

## 8. Standar Paginasi & Pencarian Dinamis
- Dilarang keras melakukan query `Model::all()` pada data tabel master/transaksi.
- Gunakan paginasi server-side:
  ```php
  $perPage = (int) $request->input('per_page', 15);
  $students = Student::query()
      ->when($request->search, function ($q, $search) {
          $q->where(function($query) use ($search) {
              $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
          });
      })
      ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'))
      ->paginate($perPage);
  ```
- Frontend Vue wajib menerapkan `debounce` (300-500ms) pada input pencarian untuk menghemat resource server.

---

## 9. Desain Mobile-Friendly & Responsif
- Pendekatan **Mobile-First** dengan Tailwind CSS.
- **Adaptasi Tampilan**:
  - Desktop: Tabel data lengkap dengan filter horizontal.
  - Mobile (< 768px): Mode kartu (card view) atau tabel scroll horizontal responsif dengan minimum touch target (min-h 44px).
- Sidebar navigasi dapat ditutup menjadi drawer/off-canvas menu di perangkat smartphone.
- Mendukung pemindaian QR Code kehadiran langsung dari kamera smartphone pengajar/siswa.

---

## 10. Arsitektur Multi-Tenant (Banyak Lembaga Bimbel)
- **Skema Isolasi**: Shared Database with Row-Level Tenant Scoping.
- **Tenant Scope Otomatis**:
  Setiap model tenant-aware wajib mengimplementasikan Global Scope (`TenantScope`) agar tidak ada data yang bocor antar lembaga bimbel (mencegah kebocoran Cross-Tenant / IDOR):
  ```php
  class TenantScope implements Scope
  {
      public function apply(Builder $builder, Model $model): void
      {
          if (auth()->check() && !auth()->user()->isSuperAdmin()) {
              $builder->where($model->getTable() . '.tenant_id', auth()->user()->tenant_id);
          }
      }
  }
  ```
- **Model Creating Event**: Otomatis mengisi `tenant_id` dari user yang sedang login saat create data baru.

---

## 11. Multi-Role (Role-Based Access Control / RBAC)
Hierarki peran dalam aplikasi:
1. **Superadmin**: Mengelola pendaftaran lembaga bimbel (tenant), lisensi, dan monitoring server.
2. **Admin Bimbel (Tenant Admin)**: Mengelola guru/tutor, siswa, kelas, jadwal, paket belajar, dan rekap absensi lembaga miliknya.
3. **Tutor / Pengajar**: Membuka sesi absensi, memindai QR code kehadiran siswa, menginput nilai & jurnal kelas.
4. **Staff / Operasional**: Membantu administrasi data siswa dan presensi harian.
5. **Siswa**: Melihat jadwal belajar, melakukan check-in mandiri (jika diaktifkan), dan memantau riwayat kehadiran.
6. **Orang Tua / Wali**: Memantau kehadiran anak secara real-time dan notifikasi kehadiran.

Setiap controller dan rute wajib dilindungi Middleware Role & Gate Authorization.

---

## 12. Arsitektur SPA (Single Page Application - Vue 3)
- Transisi halaman instan tanpa reload browser.
- Gunakan Vue 3 Composition API `<script setup lang="ts">`.
- State Management terpusat dengan Pinia.
- Pemisahan komponen yang rapi:
  - `resources/js/Components/UI/` (Button, Input, Modal, Table, Pagination)
  - `resources/js/Components/Attendance/` (QRScanner, AttendanceCard, SessionStatus)
  - `resources/js/Pages/` (Halaman views)
  - `resources/js/Composables/` (`useToast`, `useConfirmSwal`, `usePagination`)

---

## 13. Optimasi Kecepatan & Performa Tinggi (Fast Load)
- **Eager Loading Wajib**: Hindari N+1 query problem dengan selalu menyertakan `with(['tenant', 'student', 'classRoom'])`.
- **Database Indexing**: Indeks kolom kunci: `tenant_id`, `created_at`, `status`, `student_id`, `schedule_date`.
- **Vite Code Splitting & Lazy Loading**: Komponen dan halaman di-load secara dinamis (`() => import('./Page.vue')`).
- **Asset Minification**: Gunakan build produksi teroptimasi dari Vite dan Tailwind CSS.

---

## 14. Pertahanan Keamanan Tambahan (Anti-Hacker Hardening)
- **Cegah SQL Injection**: Wajib gunakan Eloquent / Parameterized Binding PDO. Dilarang merangkai query mentah dengan variabel tanpa sanitasi.
- **Cegah Mass Assignment**: Cantumkan `$fillable` secara eksplisit pada setiap Model Eloquent. Dilarang menggunakan `$guarded = []`.
- **Cegah XSS**: Escape teks output secara otomatis via Vue `{{ }}`, gunakan sanitize jika merender HTML.
- **Cegah CSRF & Header Keamanan**: Aktifkan Content-Security-Policy (CSP), X-Frame-Options: SAMEORIGIN, X-Content-Type-Options: nosniff.
- **Audit Logging**: Catat aktivitas krusial (login gagal, mutasi data sensitif, perubahan role) ke dalam audit table.
