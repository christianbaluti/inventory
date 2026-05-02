<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        try {
            DB::beginTransaction();

            $otp = (string) random_int(100000, 999999);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'otp_code' => Hash::make($otp),
                'otp_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            Mail::raw("Your Mgawi Inventory OTP is {$otp}. It expires in 10 minutes.", function ($message) use ($user) {
                $message->to($user->email)
                    ->replyTo(config('mail.reply_to.address'), config('mail.reply_to.name'))
                    ->subject('Mgawi Inventory email OTP');
            });

            DB::commit();
        } catch (Throwable) {
            DB::rollBack();

            throw ValidationException::withMessages(['email' => 'The OTP email could not be delivered. Check the email address and try again.']);
        }

        return response()->json([
            'message' => 'Registration successful. Check your email for the OTP.',
            'email' => $user->email,
        ], 201);
    }

    public function verifyOtp(Request $request, JwtService $jwt): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $user = User::query()->where('email', strtolower($data['email']))->first();

        if (! $user || ! $user->otp_code || ! Hash::check($data['otp'], $user->otp_code) || $user->otp_expires_at?->isPast()) {
            throw ValidationException::withMessages(['otp' => 'The OTP is invalid or expired.']);
        }

        $user->forceFill([
            'email_verified_at' => Carbon::now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();

        return response()->json([
            'message' => 'Email verified successfully.',
            'token' => $jwt->issue($user->id, $user->company_id),
            'user' => $user->fresh('company'),
        ]);
    }

    public function login(Request $request, JwtService $jwt): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->with('company')->where('email', strtolower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'The credentials are incorrect.']);
        }

        if (! $user->email_verified_at) {
            throw ValidationException::withMessages(['email' => 'Verify your email OTP before logging in.']);
        }

        return response()->json([
            'message' => 'Login successful.',
            'token' => $jwt->issue($user->id, $user->company_id),
            'user' => $user,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('company'),
        ]);
    }
}
