<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\DispensaryItem;
use App\Models\DispensaryStockMovement;
use App\Services\HealthHubRbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DispensaryApiController extends Controller
{
    public function __construct(
        private readonly HealthHubRbacService $rbac,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeDispensary($request);

        $items = DispensaryItem::query()
            ->orderBy('item_name')
            ->limit(500)
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $admin = $this->authorizeDispensary($request);

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'unit_of_measure' => 'nullable|string|max:32',
            'quantity_on_hand' => 'nullable|numeric|min:0',
            'batch_lot' => 'nullable|string|max:64',
            'expiration_date' => 'nullable|date',
            'supplier' => 'nullable|string|max:255',
            'reorder_threshold' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:5000',
        ]);

        $quantity = (float) ($validated['quantity_on_hand'] ?? 0);

        $item = DB::transaction(function () use ($validated, $quantity, $admin): DispensaryItem {
            $item = DispensaryItem::query()->create([
                'item_name' => $validated['item_name'],
                'category' => $validated['category'] ?? null,
                'unit_of_measure' => $validated['unit_of_measure'] ?? 'pcs',
                'quantity_on_hand' => $quantity,
                'batch_lot' => $validated['batch_lot'] ?? null,
                'expiration_date' => $validated['expiration_date'] ?? null,
                'supplier' => $validated['supplier'] ?? null,
                'reorder_threshold' => (float) ($validated['reorder_threshold'] ?? 0),
                'remarks' => $validated['remarks'] ?? null,
            ]);

            if ($quantity > 0) {
                $this->recordMovement($item, 'receive', $quantity, $quantity, $admin, 'Initial stock');
            }

            return $item;
        });

        return response()->json([
            'message' => 'Dispensary item created.',
            'data' => $item->fresh(),
        ], 201);
    }

    public function show(DispensaryItem $item, Request $request): JsonResponse
    {
        $this->authorizeDispensary($request);

        $item->load(['stockMovements' => fn ($q) => $q->orderByDesc('created_at')->limit(50)]);

        return response()->json(['data' => $item]);
    }

    public function storeMovement(DispensaryItem $item, Request $request): JsonResponse
    {
        $admin = $this->authorizeDispensary($request);

        $validated = $request->validate([
            'movement_type' => ['required', Rule::in(['receive', 'dispense', 'adjustment', 'expired'])],
            'quantity' => 'required|numeric|min:0.01',
            'patient_record_id' => 'nullable|integer|exists:patient_records,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $type = $validated['movement_type'];
        $quantity = (float) $validated['quantity'];
        $delta = match ($type) {
            'receive' => $quantity,
            'dispense', 'expired' => -$quantity,
            'adjustment' => $request->boolean('increase') ? $quantity : -$quantity,
        };

        $after = (float) $item->quantity_on_hand + $delta;
        if ($after < 0) {
            return response()->json([
                'message' => 'Insufficient quantity on hand for this movement.',
            ], 422);
        }

        DB::transaction(function () use ($item, $type, $delta, $after, $admin, $validated): void {
            $item->update(['quantity_on_hand' => $after]);
            $this->recordMovement(
                $item,
                $type,
                $delta,
                $after,
                $admin,
                $validated['notes'] ?? null,
                isset($validated['patient_record_id']) ? (int) $validated['patient_record_id'] : null,
            );
        });

        return response()->json([
            'message' => 'Stock movement recorded.',
            'data' => $item->fresh(),
        ]);
    }

    private function authorizeDispensary(Request $request): Admin
    {
        $admin = $request->user('admin');
        if (! $admin instanceof Admin) {
            abort(401);
        }

        $role = $this->rbac->resolveRole($admin);
        if (! $this->rbac->canManageDispensary($role)) {
            abort(403, __('You do not have permission to manage dispensary inventory.'));
        }

        return $admin;
    }

    private function recordMovement(
        DispensaryItem $item,
        string $type,
        float $delta,
        float $after,
        Admin $admin,
        ?string $notes = null,
        ?int $patientRecordId = null,
    ): void {
        DispensaryStockMovement::query()->create([
            'item_id' => $item->id,
            'movement_type' => $type,
            'quantity_delta' => $delta,
            'quantity_after' => $after,
            'patient_record_id' => $patientRecordId,
            'performed_by' => $admin->username,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }
}
