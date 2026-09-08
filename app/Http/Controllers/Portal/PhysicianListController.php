<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Physician;
use Illuminate\View\View;

class PhysicianListController extends Controller
{
    public function index(): View
    {
        return view('portal.physicians.index', [
            'physicians' => Physician::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }
}
