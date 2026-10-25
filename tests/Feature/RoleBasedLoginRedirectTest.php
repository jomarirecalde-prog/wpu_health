<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Physician;
use App\Services\AuthRoleService;
use App\Services\ClinicalWorkspaceAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleBasedLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\CompressResponse::class);
    }

    public function test_clinical_physician_admin_lands_on_physician_workspace_dashboard(): void
    {
        Admin::query()->create([
            'username' => 'dr_smith',
            'password' => Hash::make('password'),
            'role' => 'physician',
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => 'dr_smith',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringContainsString('/admin/workspace/', (string) $target);
        $this->assertStringContainsString('page=dashboard', (string) $target);
    }

    public function test_dentist_admin_lands_on_dental_records_workspace(): void
    {
        Admin::query()->create([
            'username' => 'dr_dental',
            'password' => Hash::make('password'),
            'role' => 'dentist',
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => 'dr_dental',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringContainsString('page=health_dental_records', (string) $target);
        $this->assertStringContainsString('records_tab=dental', (string) $target);
    }

    public function test_super_admin_still_lands_on_laravel_admin_dashboard(): void
    {
        Admin::query()->create([
            'username' => 'super',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        $this->post(route('login.store'), [
            'identifier' => 'super',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_dentist_cannot_open_admin_management_workspace_page(): void
    {
        $dentist = Admin::query()->create([
            'username' => 'dr_dental',
            'password' => Hash::make('password'),
            'role' => 'dentist',
        ]);

        $this->actingAs($dentist, 'admin')
            ->get(route('admin.workspace', ['path' => 'admin/admin.php']).'?page=admin_management')
            ->assertForbidden();
    }

    public function test_dentist_cannot_open_medical_health_records_tab(): void
    {
        $dentist = Admin::query()->create([
            'username' => 'dr_dental',
            'password' => Hash::make('password'),
            'role' => 'dentist',
        ]);

        $this->actingAs($dentist, 'admin')
            ->get(route('admin.workspace', ['path' => 'admin/admin.php']).'?page=health_dental_records&records_tab=health')
            ->assertForbidden();
    }

    public function test_dentist_can_open_records_page_without_explicit_tab(): void
    {
        $dentist = Admin::query()->create([
            'username' => 'dr_dental',
            'password' => Hash::make('password'),
            'role' => 'dentist',
        ]);

        $this->actingAs($dentist, 'admin')
            ->get(route('admin.workspace', ['path' => 'admin/admin.php']).'?page=health_dental_records')
            ->assertOk();
    }

    public function test_physician_admin_can_open_records_page_without_explicit_tab(): void
    {
        $physician = Admin::query()->create([
            'username' => 'dr_phys',
            'password' => Hash::make('password'),
            'role' => 'physician',
        ]);

        $this->actingAs($physician, 'admin')
            ->get(route('admin.workspace', ['path' => 'admin/admin.php']).'?page=health_dental_records')
            ->assertOk();
    }

    public function test_dentist_cannot_open_calendar_physician_management(): void
    {
        $dentist = Admin::query()->create([
            'username' => 'dr_dental',
            'password' => Hash::make('password'),
            'role' => 'dentist',
        ]);

        $this->actingAs($dentist, 'admin')
            ->get(route('admin.calendar.physicians'))
            ->assertForbidden();
    }

    public function test_physician_provider_guard_still_uses_appointment_dashboard(): void
    {
        Physician::query()->create([
            'name' => 'Dr Portal',
            'email' => 'portal.physician@wpu.edu.ph',
            'password' => Hash::make('password'),
            'status' => 'active',
            'provider_category' => 'physician',
            'professional_title' => 'MD',
        ]);

        $this->post(route('login.store'), [
            'identifier' => 'portal.physician@wpu.edu.ph',
            'password' => 'password',
        ])->assertRedirect(route('physician.dashboard'));
    }

    public function test_dentist_provider_redirects_to_dental_workspace(): void
    {
        Physician::query()->create([
            'name' => 'Jomari B. Recalde',
            'email' => 'dentist.provider@wpu.edu.ph',
            'password' => Hash::make('password'),
            'status' => 'active',
            'provider_category' => 'dentist',
            'professional_title' => 'DMD',
        ]);

        $response = $this->post(route('login.store'), [
            'identifier' => 'dentist.provider@wpu.edu.ph',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $target = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/physician/workspace/', $target);
        $this->assertStringContainsString('health_dental_records', $target);
    }

    public function test_auth_role_service_resolves_admin_clinical_roles(): void
    {
        $physician = Admin::query()->create([
            'username' => 'dr_a',
            'password' => Hash::make('password'),
            'role' => 'physician',
        ]);

        $this->actingAs($physician, 'admin');

        $roles = app(AuthRoleService::class);
        $this->assertSame('physician', $roles->currentRole());
        $this->assertTrue($roles->hasRole('physician'));
        $this->assertFalse($roles->hasRole('admin'));
    }

    public function test_workspace_access_service_blocks_dentist_from_certificates(): void
    {
        $access = app(ClinicalWorkspaceAccessService::class);
        $this->assertFalse($access->canAccessPage('dentist', 'certificates_referrals'));
        $this->assertTrue($access->canAccessPage('physician', 'certificates_referrals'));
    }
}
