<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function store(Request $request, JwtService $jwt): JsonResponse
    {
        $user = $request->user();

        if ($user->company_id) {
            return response()->json(['message' => 'Company already exists for this account.'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:companies,name'],
        ]);

        $company = Company::query()->create([
            'name' => $data['name'],
            'owner_user_id' => $user->id,
        ]);

        $user->forceFill(['company_id' => $company->id])->save();

        return response()->json([
            'message' => 'Company created successfully.',
            'token' => $jwt->issue($user->id, $company->id),
            'user' => $user->fresh('company'),
        ], 201);
    }
}
