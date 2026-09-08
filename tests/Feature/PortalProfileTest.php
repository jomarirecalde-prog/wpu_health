<?php

namespace Tests\Feature;

use App\Models\PortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalProfileTest extends TestCase
{
    use RefreshDatabase;

    private PortalUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\CompressResponse::class);
        $this->seed(\Database\Seeders\AppointmentModuleSeeder::class);
        $this->user = PortalUser::factory()->create([
            'contact_number' => '09170000001',
        ]);
    }

    public function test_profile_page_loads_for_authenticated_patient(): void
    {
        $response = $this->actingAs($this->user, 'portal')->get(route('portal.profile'));

        $response->assertOk();
        $response->assertSee('Personal Information');
        $response->assertSee('Account Information');
        $response->assertSee('Change Password');
    }

    public function test_patient_can_update_allowed_profile_fields(): void
    {
        $response = $this->actingAs($this->user, 'portal')->put(route('portal.profile.update'), [
            'name' => 'Updated Name',
            'contact_number' => '09998887777',
            'address' => 'New address',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('portal_users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'contact_number' => '09998887777',
        ]);
    }

    public function test_patient_cannot_update_protected_fields_via_mass_assignment(): void
    {
        $originalType = $this->user->patient_type_id;
        $originalStatus = $this->user->status;

        $this->actingAs($this->user, 'portal')->put(route('portal.profile.update'), [
            'name' => $this->user->name,
            'contact_number' => $this->user->contact_number,
            'patient_type_id' => 999,
            'department_id' => 999,
            'employee_student_id' => 'HACKED',
            'status' => 'suspended',
        ]);

        $this->user->refresh();
        $this->assertSame($originalType, $this->user->patient_type_id);
        $this->assertSame($originalStatus, $this->user->status);
        $this->assertNotSame('HACKED', $this->user->employee_student_id);
    }

    public function test_patient_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);

        $response = $this->actingAs($this->user, 'portal')->post(route('portal.profile.photo'), [
            'photo' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->user->refresh();
        $this->assertNotNull($this->user->profile_photo_path);
        Storage::disk('public')->assertExists($this->user->profile_photo_path);
    }

    public function test_profile_photo_rejects_invalid_mime(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $response = $this->actingAs($this->user, 'portal')->post(route('portal.profile.photo'), [
            'photo' => $file,
        ]);

        $response->assertSessionHasErrors('photo');
    }

    public function test_patient_can_change_password_with_valid_current_password(): void
    {
        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));

        $response = $this->actingAs($this->user->fresh(), 'portal')->put(route('portal.profile.password'), [
            'current_password' => 'password',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Your password has been successfully updated.');

        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword456', $this->user->password));
        $this->assertNotNull($this->user->password_changed_at);
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $response = $this->actingAs($this->user, 'portal')->put(route('portal.profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'newpassword456',
            'password_confirmation' => 'newpassword456',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_patient_cannot_access_another_users_profile_update(): void
    {
        $other = PortalUser::factory()->create();

        $response = $this->actingAs($this->user, 'portal')->put(route('portal.profile.update'), [
            'name' => 'Hacked',
            'contact_number' => '09171111111',
            'user_id' => $other->id,
        ]);

        $response->assertRedirect();
        $other->refresh();
        $this->assertNotSame('Hacked', $other->name);
    }
}
