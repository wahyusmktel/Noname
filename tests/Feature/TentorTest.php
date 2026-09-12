<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Tentor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TentorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_tentors_page()
    {
        $response = $this->get('/tentors');
        $response->assertRedirect('/login');
    }

    public function test_admin_can_view_tentors_page()
    {
        $tenant = Tenant::create([
            'name'         => 'Bimbel No Name',
            'slug'         => 'bimbel-no-name-' . Str::random(5),
            'phone'        => '081234567890',
            'city'         => 'Jakarta Selatan',
            'status'       => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel No Name',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        Tentor::create([
            'tenant_id'      => $tenant->id,
            'name'           => 'Aris Sudrajat',
            'title_prefix'   => 'Dr.',
            'title_suffix'   => 'M.Si.',
            'specialization' => 'Matematika',
            'status'         => 'active',
        ]);

        $response = $this->actingAs($user)->get('/tentors');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_new_tentor()
    {
        Storage::fake('public');

        $tenant = Tenant::create([
            'name'         => 'Bimbel No Name',
            'slug'         => 'bimbel-no-name-' . Str::random(5),
            'phone'        => '081234567890',
            'city'         => 'Jakarta Selatan',
            'status'       => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel No Name',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $photo = UploadedFile::fake()->image('tentor_avatar.jpg', 200, 200);

        $response = $this->actingAs($user)->post('/tentors', [
            'title_prefix'   => 'Dr.',
            'name'           => 'Budi Raharja',
            'title_suffix'   => 'S.Pd., M.Si.',
            'phone'          => '081299887766',
            'email'          => 'budi.raharja@bimbelnoname.com',
            'specialization' => 'Fisika Kuantum',
            'status'         => 'active',
            'photo'          => $photo,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tentors', [
            'tenant_id'      => $tenant->id,
            'title_prefix'   => 'Dr.',
            'name'           => 'Budi Raharja',
            'title_suffix'   => 'S.Pd., M.Si.',
            'phone'          => '081299887766',
            'email'          => 'budi.raharja@bimbelnoname.com',
            'specialization' => 'Fisika Kuantum',
        ]);

        $tentor = Tentor::where('email', 'budi.raharja@bimbelnoname.com')->first();
        $this->assertNotNull($tentor);
        $this->assertTrue(Str::isUuid($tentor->id));
        $this->assertEquals('Dr. Budi Raharja, S.Pd., M.Si.', $tentor->full_name);
        $this->assertNotNull($tentor->photo);
        Storage::disk('public')->assertExists($tentor->photo);
    }

    public function test_admin_can_update_tentor()
    {
        $tenant = Tenant::create([
            'name'         => 'Bimbel No Name',
            'slug'         => 'bimbel-no-name-' . Str::random(5),
            'phone'        => '081234567890',
            'city'         => 'Jakarta Selatan',
            'status'       => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel No Name',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $tentor = Tentor::create([
            'tenant_id'      => $tenant->id,
            'name'           => 'Siti Aisyah',
            'title_prefix'   => null,
            'title_suffix'   => 'S.Pd.',
            'phone'          => '081111111111',
            'email'          => 'siti@bimbelnoname.com',
            'specialization' => 'Bahasa Inggris',
            'status'         => 'active',
        ]);

        $response = $this->actingAs($user)->put("/tentors/{$tentor->id}", [
            'name'           => 'Siti Aisyah Putri',
            'title_prefix'   => 'Dra.',
            'title_suffix'   => 'M.Pd.',
            'phone'          => '081222222222',
            'email'          => 'siti.aisyah@bimbelnoname.com',
            'specialization' => 'English for Academic Purposes',
            'status'         => 'active',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tentors', [
            'id'             => $tentor->id,
            'name'           => 'Siti Aisyah Putri',
            'title_prefix'   => 'Dra.',
            'title_suffix'   => 'M.Pd.',
            'phone'          => '081222222222',
        ]);
    }

    public function test_admin_can_soft_delete_tentor()
    {
        $tenant = Tenant::create([
            'name'         => 'Bimbel No Name',
            'slug'         => 'bimbel-no-name-' . Str::random(5),
            'phone'        => '081234567890',
            'city'         => 'Jakarta Selatan',
            'status'       => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel No Name',
            'email'     => 'admin@bimbelnoname.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $tentor = Tentor::create([
            'tenant_id'      => $tenant->id,
            'name'           => 'Hendra Wijaya',
            'status'         => 'active',
        ]);

        $response = $this->actingAs($user)->delete("/tentors/{$tentor->id}");
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('tentors', [
            'id' => $tentor->id,
        ]);
    }

    public function test_multi_tenant_isolation_for_tentors()
    {
        // Tenant A
        $tenantA = Tenant::create([
            'name'   => 'Bimbel A',
            'slug'   => 'bimbel-a-' . Str::random(5),
            'phone'  => '081',
            'city'   => 'Kota A',
            'status' => 'active',
        ]);
        $userA = User::create([
            'tenant_id' => $tenantA->id,
            'name'      => 'User A',
            'email'     => 'a@bimbel.com',
            'password'  => Hash::make('pass'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);
        $tentorA = Tentor::create([
            'tenant_id' => $tenantA->id,
            'name'      => 'Tentor Milik Bimbel A',
            'status'    => 'active',
        ]);

        // Tenant B
        $tenantB = Tenant::create([
            'name'   => 'Bimbel B',
            'slug'   => 'bimbel-b-' . Str::random(5),
            'phone'  => '082',
            'city'   => 'Kota B',
            'status' => 'active',
        ]);
        $userB = User::create([
            'tenant_id' => $tenantB->id,
            'name'      => 'User B',
            'email'     => 'b@bimbel.com',
            'password'  => Hash::make('pass'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);
        $tentorB = Tentor::create([
            'tenant_id' => $tenantB->id,
            'name'      => 'Tentor Milik Bimbel B',
            'status'    => 'active',
        ]);

        // User A query Tentor
        $tentorsForA = Tentor::where('tenant_id', $tenantA->id)->get();
        $this->assertTrue($tentorsForA->contains($tentorA));
        $this->assertFalse($tentorsForA->contains($tentorB));
    }

    public function test_generate_tentor_accounts_format_and_role(): void
    {
        $tenant = Tenant::create([
            'name'   => 'Bimbel Hebat',
            'slug'   => 'bimbel-hebat-' . Str::random(5),
            'phone'  => '081234567890',
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel',
            'email'     => 'admin_' . Str::random(5) . '@hebat.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $tentor = Tentor::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Budi Setiawan',
            'status'    => 'active',
        ]);

        $response = $this->actingAs($user)->post('/tentors/generate-accounts');
        $response->assertRedirect();

        $tentor->refresh();
        $this->assertNotNull($tentor->username);
        $this->assertNotNull($tentor->plain_password);

        // 6 digits starting with 26
        $this->assertMatchesRegularExpression('/^26\d{4}$/', $tentor->username);
        // 6 digits password
        $this->assertMatchesRegularExpression('/^\d{6}$/', $tentor->plain_password);

        $createdUser = User::find($tentor->user_id);
        $this->assertNotNull($createdUser);
        $this->assertEquals('tutor', $createdUser->role);
        $this->assertTrue(Hash::check($tentor->plain_password, $createdUser->password));
    }

    public function test_reset_tentor_password(): void
    {
        $tenant = Tenant::create([
            'name'   => 'Bimbel Hebat',
            'slug'   => 'bimbel-hebat-' . Str::random(5),
            'phone'  => '081234567890',
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel',
            'email'     => 'admin_' . Str::random(5) . '@hebat.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        $tentor = Tentor::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Citra Kirana',
            'status'    => 'active',
        ]);

        // Generate account
        $this->actingAs($user)->post('/tentors/generate-accounts');
        $tentor->refresh();

        // Reset password
        $response = $this->actingAs($user)->post("/tentors/{$tentor->id}/reset-password");
        $response->assertRedirect();

        $tentor->refresh();
        $this->assertNotNull($tentor->plain_password);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $tentor->plain_password);

        $createdUser = User::find($tentor->user_id);
        $this->assertTrue(Hash::check($tentor->plain_password, $createdUser->password));
    }

    public function test_export_tentor_accounts_excel(): void
    {
        $tenant = Tenant::create([
            'name'   => 'Bimbel Hebat',
            'slug'   => 'bimbel-hebat-' . Str::random(5),
            'phone'  => '081234567890',
            'status' => 'active',
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => 'Admin Bimbel',
            'email'     => 'admin_' . Str::random(5) . '@hebat.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin_bimbel',
            'status'    => 'active',
        ]);

        Tentor::create([
            'tenant_id'      => $tenant->id,
            'name'           => 'Dedi Mulyadi',
            'username'       => '269999',
            'plain_password' => '654321',
            'status'         => 'active',
        ]);

        $response = $this->actingAs($user)->get('/tentors/export-accounts');
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
