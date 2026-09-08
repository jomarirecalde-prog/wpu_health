<?php

namespace App\Http\Controllers\Physician;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('physician.profile.edit', ['physician' => auth('physician')->user()]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $physician = auth('physician')->user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'professional_title' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'consultation_location' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $physician->update($validated);

        return back()->with('status', 'Profile updated.');
    }
}
