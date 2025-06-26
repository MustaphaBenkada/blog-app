<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\SendOtpMail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user,
        ], 201);
    }

    public function requestOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $otp = rand(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => null]
        );

        $user->update([
            'otp_code' => $otp,
            'otp_expires_at' => $expiresAt,
        ]);

        // Queue the email
        Mail::to($user->email)->queue(new SendOtpMail($otp));

        return response()->json([
            'message' => 'OTP sent successfully',
        ]);
    }

    public function loginWithOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp_code' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->otp_code !== $request->otp_code) {
            return response()->json(['message' => 'Invalid OTP'], 401);
        }

        if (Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'OTP expired'], 401);
        }

        // OTP valid — clear OTP and generate token
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $user->name = $request->name;
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'user' => $user,
        ]);
    }
}
