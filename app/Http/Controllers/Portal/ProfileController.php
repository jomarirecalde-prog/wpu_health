<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalUser;
use App\Services\PortalProfilePhotoService;
use App\Services\PortalSecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly PortalProfilePhotoService $photos,
        private readonly PortalSecurityAuditService $audit,
    ) {}

    public function edit(): View
    {
        /** @var PortalUser $user */
        $user = auth('portal')->user();
        $user->load(['patientType', 'department']);

        return view('portal.profile.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var PortalUser $user */
        $user = auth('portal')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['required', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $changes = [];
        foreach ($validated as $field => $value) {
            $old = $user->{$field};
            if ((string) $old !== (string) ($value ?? '')) {
                $changes[] = $field;
            }
        }

        $user->update($validated);

        if ($changes !== []) {
            $this->audit->log($user, 'profile_updated', 'Fields: '.implode(', ', $changes));
        }

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePhoto(Request $request): RedirectResponse|JsonResponse
    {
        /** @var PortalUser $user */
        $user = auth('portal')->user();

        $request->validate([
            'photo' => ['required', 'file', 'max:5120'],
        ]);

        try {
            $result = $this->photos->store($user, $request->file('photo'));
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['photo' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profile picture updated.',
                'url' => $result['url'],
            ]);
        }

        return back()->with('status', 'Profile picture updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var PortalUser $user */
        $user = auth('portal')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                Password::min(8),
                'confirmed',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    if (Hash::check((string) $value, $user->password)) {
                        $fail('The new password must be different from your current password.');
                    }
                },
            ],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        auth('portal')->logoutOtherDevices($validated['current_password']);

        $user->forceFill([
            'password' => $validated['password'],
            'password_changed_at' => now(),
        ])->save();

        $this->audit->log($user, 'password_changed', 'Self-service password change');

        $request->session()->regenerate();

        return back()->with('status', 'Your password has been successfully updated.');
    }
}
