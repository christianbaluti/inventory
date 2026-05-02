<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $items = Inventory::query()
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);
        $data = $this->validated($request);

        try {
            $item = Inventory::query()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'quantity' => $data['quantity'],
            ]);
        } catch (QueryException) {
            throw ValidationException::withMessages(['name' => 'This item already exists for your company.']);
        }

        return response()->json(['item' => $item], 201);
    }

    public function show(Request $request, Inventory $inventory): JsonResponse
    {
        $this->authorizeCompany($request, $inventory);

        return response()->json(['item' => $inventory]);
    }

    public function update(Request $request, Inventory $inventory): JsonResponse
    {
        $this->authorizeCompany($request, $inventory);
        $data = $this->validated($request);

        try {
            $inventory->update($data);
        } catch (QueryException) {
            throw ValidationException::withMessages(['name' => 'This item already exists for your company.']);
        }

        return response()->json(['item' => $inventory->fresh()]);
    }

    public function destroy(Request $request, Inventory $inventory): JsonResponse
    {
        $this->authorizeCompany($request, $inventory);
        $inventory->delete();

        return response()->json(['message' => 'Item deleted successfully.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);
    }

    private function company(Request $request)
    {
        $company = $request->attributes->get('company');

        if (! $company) {
            abort(response()->json(['message' => 'Create a company before managing inventory.'], 422));
        }

        return $company;
    }

    private function authorizeCompany(Request $request, Inventory $inventory): void
    {
        $company = $this->company($request);

        if ((int) $inventory->company_id !== (int) $company->id) {
            abort(404);
        }
    }
}
