<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\PortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class AdminPortalUserTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private PortalUser $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\CompressResponse::class);

        $this->ensureReferenceTables();

        $this->admin = Admin::query()->create([
            'username' => 'admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $this->patient = PortalUser::factory()->create([
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'contact_number' => '09170000001',
            'employee_student_id' => '2026-001',
            'status' => 'active',
            'address' => 'Old address',
        ]);
    }

    public function test_guest_cannot_access_portal_users(): void
    {
        $this->get(route('admin.calendar.portal-users'))->assertRedirect(route('login'));
        $this->get(route('admin.calendar.portal-users.show', $this->patient))->assertRedirect(route('login'));
        $this->get(route('admin.calendar.portal-users.edit', $this->patient))->assertRedirect(route('login'));
    }

    public function test_patient_cannot_access_admin_portal_user_endpoints(): void
    {
        $this->actingAs($this->patient, 'portal')
            ->get(route('admin.calendar.portal-users'))
            ->assertRedirect(route('login'));

        $this->actingAs($this->patient, 'portal')
            ->put(route('admin.calendar.portal-users.update', $this->patient), [
                'name' => 'Hacked',
                'email' => $this->patient->email,
                'status' => 'active',
            ])
            ->assertRedirect(route('login'));

        $this->patient->refresh();
        $this->assertSame('Juan Dela Cruz', $this->patient->name);
    }

    public function test_admin_can_view_portal_users_index(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.calendar.portal-users'));

        $response->assertOk();
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('juan@example.com');
        $response->assertSee('2026-001');
        $response->assertSee('View');
        $response->assertSee('Edit');
    }

    public function test_admin_can_search_portal_users(): void
    {
        PortalUser::factory()->create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'contact_number' => '09998887777',
            'employee_student_id' => '2026-002',
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.calendar.portal-users', [
            'q' => 'maria@example.com',
        ]));

        $response->assertOk();
        $response->assertSee('Maria Santos');
        $response->assertDontSee('Juan Dela Cruz');
    }

    public function test_admin_can_filter_by_status(): void
    {
        PortalUser::factory()->create([
            'name' => 'Inactive Patient',
            'email' => 'inactive@example.com',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.calendar.portal-users', [
            'status' => 'inactive',
        ]));

        $response->assertOk();
        $response->assertSee('Inactive Patient');
        $response->assertDontSee('Juan Dela Cruz');
    }

    public function test_admin_can_view_patient_details(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.calendar.portal-users.show', $this->patient));

        $response->assertOk();
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('juan@example.com');
        $response->assertSee('09170000001');
        $response->assertSee('Edit Patient');
        $response->assertDontSee($this->patient->password);
    }

    public function test_admin_can_open_edit_page_without_seeing_password_hash(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.calendar.portal-users.edit', $this->patient));

        $response->assertOk();
        $response->assertSee('Personal Information');
        $response->assertSee('Account Information');
        $response->assertSee('Set New Password');
        $response->assertDontSee($this->patient->getRawOriginal('password'));
        $response->assertDontSee('name="role"', false);
    }

    public function test_admin_can_update_patient_profile(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.calendar.portal-users.update', $this->patient),
            [
                'name' => 'Juan Updated',
                'email' => 'juan.updated@example.com',
                'contact_number' => '09171112222',
                'date_of_birth' => '2000-01-15',
                'address' => 'New address',
                'employee_student_id' => '2026-099',
                'status' => 'suspended',
            ]
        );

        $response->assertRedirect(route('admin.calendar.portal-users.show', $this->patient));
        $response->assertSessionHas('status', 'Patient information updated successfully.');

        $this->patient->refresh();
        $this->assertSame('Juan Updated', $this->patient->name);
        $this->assertSame('juan.updated@example.com', $this->patient->email);
        $this->assertSame('09171112222', $this->patient->contact_number);
        $this->assertSame('2026-099', $this->patient->employee_student_id);
        $this->assertSame('suspended', $this->patient->status);
        $this->assertSame('New address', $this->patient->address);
        $this->assertNull($this->patient->email_verified_at);

        $this->assertDatabaseHas('security_audit_log', [
            'username' => 'admin',
            'action' => 'patient_profile_updated',
        ]);
        $this->assertDatabaseHas('security_audit_log', [
            'username' => 'admin',
            'action' => 'patient_email_changed',
        ]);
        $this->assertDatabaseHas('security_audit_log', [
            'username' => 'admin',
            'action' => 'patient_account_status_changed',
        ]);
    }

    public function test_admin_cannot_use_duplicate_email(): void
    {
        PortalUser::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.calendar.portal-users.update', $this->patient),
            [
                'name' => $this->patient->name,
                'email' => 'taken@example.com',
                'status' => 'active',
            ]
        );

        $response->assertSessionHasErrors(['email' => 'This email address is already being used by another account.']);
        $this->patient->refresh();
        $this->assertSame('juan@example.com', $this->patient->email);
    }

    public function test_admin_can_reset_patient_password_without_storing_plaintext(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.calendar.portal-users.password', $this->patient),
            [
                'password' => 'newsecure99',
                'password_confirmation' => 'newsecure99',
            ]
        );

        $response->assertRedirect(route('admin.calendar.portal-users.edit', $this->patient));
        $response->assertSessionHas('status', 'Patient password has been changed.');

        $this->patient->refresh();
        $this->assertTrue(Hash::check('newsecure99', $this->patient->password));
        $this->assertNotSame('newsecure99', $this->patient->getRawOriginal('password'));
        $this->assertNotNull($this->patient->password_changed_at);

        $this->assertDatabaseHas('security_audit_log', [
            'username' => 'admin',
            'action' => 'patient_password_changed',
        ]);

        $details = (string) DB::table('security_audit_log')
            ->where('action', 'patient_password_changed')
            ->value('details');
        $this->assertStringNotContainsString('newsecure99', $details);
        $this->assertStringNotContainsString($this->patient->getRawOriginal('password'), $details);
    }

    public function test_admin_can_upload_patient_photo(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.calendar.portal-users.update', $this->patient),
            [
                'name' => $this->patient->name,
                'email' => $this->patient->email,
                'status' => 'active',
                'photo' => $file,
            ]
        );

        $response->assertRedirect(route('admin.calendar.portal-users.show', $this->patient));
        $this->patient->refresh();
        $this->assertNotNull($this->patient->profile_photo_path);
        Storage::disk('public')->assertExists($this->patient->profile_photo_path);
    }

    public function test_admin_photo_rejects_invalid_mime(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.calendar.portal-users.update', $this->patient),
            [
                'name' => $this->patient->name,
                'email' => $this->patient->email,
                'status' => 'active',
                'photo' => $file,
            ]
        );

        $response->assertSessionHasErrors('photo');
    }

    public function test_empty_state_message_when_no_portal_users(): void
    {
        $this->patient->delete();

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.calendar.portal-users'));

        $response->assertOk();
        $response->assertSee('No Portal Users');
        $response->assertSee('There are currently no patient accounts registered');
    }

    private function ensureReferenceTables(): void
    {
        if (! Schema::hasTable('patient_types')) {
            Schema::create('patient_types', function (Blueprint $table): void {
                $table->id();
                $table->string('type_name', 50);
                $table->string('color_code', 7)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 100);
                $table->timestamp('created_at')->nullable();
            });
        }
    }
}
