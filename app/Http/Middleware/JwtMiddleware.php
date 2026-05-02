<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Authentication token is required.'], 401);
        }

        try {
            $claims = app(JwtService::class)->decode($token);
        } catch (Throwable) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        $user = User::query()->with('company')->find($claims['user_id'] ?? null);

        if (! $user || ! $user->email_verified_at) {
            return response()->json(['message' => 'User is not authorized.'], 401);
        }

        $tokenCompanyId = $claims['company_id'] ?? null;

        if ((int) ($user->company_id ?? 0) !== (int) ($tokenCompanyId ?? 0)) {
            return response()->json(['message' => 'Token company context is invalid.'], 401);
        }

        $company = $tokenCompanyId ? Company::query()->whereKey($tokenCompanyId)->first() : null;

        Auth::setUser($user);
        $request->attributes->set('jwt_claims', $claims);
        $request->attributes->set('company', $company);

        return $next($request);
    }
}
