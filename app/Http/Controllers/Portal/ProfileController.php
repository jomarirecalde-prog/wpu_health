<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PatientType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('portal.profile.edit', [
            'user' => auth('portal')->user(),
            'patientTypes' => PatientType::query()->orderBy('type_name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = auth('portal')->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:50',
            'patient_type_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'employee_student_id' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
        ]);

        $user->update($validated);

        return back()->with('status', 'Profile updated.');
    }
}
