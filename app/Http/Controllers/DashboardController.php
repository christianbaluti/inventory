<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $company = $request->attributes->get('company');

        if (! $company) {
            return response()->json([
                'user' => $request->user(),
                'company' => null,
                'reports' => [
                    'total_items' => 0,
                    'total_quantity' => 0,
                    'low_stock' => 0,
                ],
                'items' => [],
            ]);
        }

        $base = Inventory::query()->where('company_id', $company->id);

        return response()->json([
            'user' => $request->user(),
            'company' => $company,
            'reports' => [
                'total_items' => (clone $base)->count(),
                'total_quantity' => (int) (clone $base)->sum('quantity'),
                'low_stock' => (clone $base)->where('quantity', '<=', 5)->count(),
            ],
            'items' => (clone $base)->orderBy('name')->get(),
        ]);
    }
}
