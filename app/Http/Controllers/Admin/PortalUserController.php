<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PatientType;
use App\Models\PortalUser;
use App\Services\PortalProfilePhotoService;
use App\Services\PortalSecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use RuntimeException;

class PortalUserController extends Controller
{
    public function __construct(
        private readonly PortalProfilePhotoService $photos,
        private readonly PortalSecurityAuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $users = $this->filteredQuery($request)
            ->paginate(25)
            ->withQueryString();

        if ($request->ajax()) {
            return view('admin.calendar.partials.portal-users-results', [
                'users' => $users,
            ]);
        }

        return view('admin.calendar.portal-users', [
            'users' => $users,
            'patientTypes' => $this->patientTypes(),
            'statuses' => PortalUser::STATUSES,
        ]);
    }

    public function show(PortalUser $portalUser): View
    {
        $this->loadRelations($portalUser);

        return view('admin.calendar.portal-user-show', [
            'user' => $portalUser,
        ]);
    }

    public function edit(PortalUser $portalUser): View
    {
        $this->loadRelations($portalUser);

        return view('admin.calendar.portal-user-edit', [
            'user' => $portalUser,
            'patientTypes' => $this->patientTypes(),
            'departments' => $this->departments(),
            'statuses' => PortalUser::STATUSES,
        ]);
    }

    public function update(Request $request, PortalUser $portalUser): RedirectResponse
    {
        $validated = $request->validate($this->profileRules($portalUser), $this->profileMessages());

        if ($request->hasFile('photo')) {
            try {
                $this->photos->validateUploadedFile($request->file('photo'));
            } catch (RuntimeException $e) {
                return back()->withErrors(['photo' => $e->getMessage()])->withInput();
            }
        }

        $emailChanged = strcasecmp((string) $portalUser->email, (string) $validated['email']) !== 0;
        $statusChanged = (string) $portalUser->status !== (string) $validated['status'];

        $tracked = [
            'name',
            'contact_number',
            'date_of_birth',
            'address',
            'email',
            'patient_type_id',
            'department_id',
            'employee_student_id',
            'status',
        ];

        $changedFields = [];
        foreach ($tracked as $field) {
            $old = $portalUser->{$field};
            $new = $validated[$field] ?? null;
            if ($field === 'date_of_birth') {
                $old = $old ? $old->format('Y-m-d') : '';
                $new = $new ? (string) $new : '';
            }
            if ((string) ($old ?? '') !== (string) ($new ?? '')) {
                $changedFields[] = $field;
            }
        }

        if ($emailChanged) {
            $validated['email_verified_at'] = null;
        }

        $portalUser->fill(collect($validated)->except('photo')->all());
        $portalUser->save();

        $photoChanged = false;
        if ($request->hasFile('photo')) {
            try {
                $this->photos->store($portalUser, $request->file('photo'), $this->adminUsername());
                $photoChanged = true;
            } catch (RuntimeException $e) {
                return back()->withErrors(['photo' => $e->getMessage()])->withInput();
            }
        }

        $this->logProfileChanges($portalUser, $changedFields, $emailChanged, $statusChanged, $photoChanged);

        return redirect()
            ->route('admin.calendar.portal-users.show', $portalUser)
            ->with('status', 'Patient information updated successfully.');
    }

    public function updatePhoto(Request $request, PortalUser $portalUser): RedirectResponse|JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'max:5120'],
        ]);

        try {
            $result = $this->photos->store($portalUser, $request->file('photo'), $this->adminUsername());
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['photo' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Patient information updated successfully.',
                'url' => $result['url'],
            ]);
        }

        return back()->with('status', 'Patient information updated successfully.');
    }

    public function updatePassword(Request $request, PortalUser $portalUser): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $portalUser->forceFill([
            'password' => $validated['password'],
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ])->save();

        $this->audit->logAdmin($portalUser, 'patient_password_changed', 'Patient password changed');

        return redirect()
            ->route('admin.calendar.portal-users.edit', $portalUser)
            ->with('status', 'Patient password has been changed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function profileRules(PortalUser $user): array
    {
        $patientTypeRule = ['nullable', 'integer'];
        if (Schema::hasTable('patient_types')) {
            $patientTypeRule[] = Rule::exists('patient_types', 'id');
        }

        $departmentRule = ['nullable', 'integer'];
        if (Schema::hasTable('departments')) {
            $departmentRule[] = Rule::exists('departments', 'id');
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('portal_users', 'email')->ignore($user->id),
            ],
            'patient_type_id' => $patientTypeRule,
            'department_id' => $departmentRule,
            'employee_student_id' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(PortalUser::STATUSES)],
            'photo' => ['nullable', 'file', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function profileMessages(): array
    {
        return [
            'email.unique' => 'This email address is already being used by another account.',
            'email.email' => 'Please enter a valid email address.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'photo.max' => 'File too large. Maximum size is 5 MB.',
        ];
    }

    /**
     * @param  list<string>  $changedFields
     */
    private function logProfileChanges(
        PortalUser $user,
        array $changedFields,
        bool $emailChanged,
        bool $statusChanged,
        bool $photoChanged,
    ): void {
        if ($changedFields !== []) {
            $this->audit->logAdmin(
                $user,
                'patient_profile_updated',
                'Patient profile updated. Fields: '.implode(', ', $changedFields)
            );
        }

        if ($emailChanged) {
            $this->audit->logAdmin($user, 'patient_email_changed', 'Patient email changed');
        }

        if ($statusChanged) {
            $this->audit->logAdmin(
                $user,
                'patient_account_status_changed',
                'Patient account status changed to '.$user->status
            );
        }

        if ($photoChanged && $changedFields === []) {
            // Photo service already wrote patient_profile_photo_changed.
            return;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<PortalUser>
     */
    private function filteredQuery(Request $request)
    {
        $query = PortalUser::query()->orderByDesc('created_at');
        $with = [];
        if (Schema::hasTable('patient_types')) {
            $with[] = 'patientType';
        }
        if (Schema::hasTable('departments')) {
            $with[] = 'department';
        }
        if ($with !== []) {
            $query->with($with);
        }

        if ($request->filled('q')) {
            $term = str_replace(['%', '_'], ['\%', '\_'], (string) $request->string('q'));
            $query->where(function ($inner) use ($term): void {
                $inner->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('employee_student_id', 'like', '%'.$term.'%')
                    ->orWhere('contact_number', 'like', '%'.$term.'%');
            });
        }

        $status = (string) $request->string('status');
        if ($status !== '' && in_array($status, PortalUser::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($request->filled('patient_type_id') && Schema::hasTable('patient_types')) {
            $query->where('patient_type_id', $request->integer('patient_type_id'));
        }

        return $query;
    }

    /**
     * @return \Illuminate\Support\Collection<int, PatientType>
     */
    private function patientTypes()
    {
        if (! Schema::hasTable('patient_types')) {
            return collect();
        }

        return PatientType::query()->orderBy('type_name')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Department>
     */
    private function departments()
    {
        if (! Schema::hasTable('departments')) {
            return collect();
        }

        return Department::query()->orderBy('name')->get();
    }

    private function adminUsername(): string
    {
        return mb_substr((string) (auth('admin')->user()?->username ?? 'admin'), 0, 50);
    }

    private function loadRelations(PortalUser $user): void
    {
        $with = [];
        if (Schema::hasTable('patient_types')) {
            $with[] = 'patientType';
        }
        if (Schema::hasTable('departments')) {
            $with[] = 'department';
        }
        if ($with !== []) {
            $user->load($with);
        }
    }
}
