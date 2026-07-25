<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;
use App\Services\UserService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function register(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();

        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            $existingUser->update([
                'name' => $data['name'],
                'password' => $data['password'],
            ]);

            return response()->json([
                'user' => $existingUser,
                'token' => $existingUser->createToken('api')->plainTextToken,
            ], 200);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? 'trainer',
        ]);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged Successfully'], 200);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        $token = Password::createToken($user);

        $url = env('FRONTEND_URL')
            . '/reset-password?token='
            . $token
            . '&email='
            . urlencode($user->email);

        Mail::to($user->email)->send(
            new ResetPasswordMail($user, $url)
        );

        return response()->json([
            'message' => 'Reset password email sent successfully.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),
            function ($user) use ($request) {

                $user->update([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ]);
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json([
                'message' => 'Password reset successfully'
            ], 200)
            : response()->json([
                'message' => 'Invalid token or email'
            ], 400);
    }

    public function getUser(Request $request)
    {
        $user = $request->user();

        return response()->json(['user' => $user]);
    }

    public function updateUser(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => 'sometimes|string|in:admin,trainer',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'telegram' => 'nullable|string|max:255',
            'current_password' => 'required_with:password|string',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if (isset($validated['password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 422);
            }
        }

        $updateData = array_filter($validated, fn($key) => !in_array($key, ['current_password', 'password_confirmation']), ARRAY_FILTER_USE_KEY);

        $user->update($updateData);

        return response()->json([
            'user' => $user,
            'message' => 'Profile updated successfully',
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $validated = $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = $this->userService->update($request->user(), $validated);

        return response()->json([
            'user' => $user,
            'message' => 'Avatar uploaded successfully',
        ]);
    }

    // TRY TO DO SOCIAL LOGIN 
    //  Redirect the user to the OAuth provider (Google/GitHub).
    //  Returns the redirect URL for the frontend to use.

    public function redirectToProvider(string $provider)
    {
        if (!in_array($provider, ['google', 'github'])) {
            return response()->json([
                'message' => 'Invalid provider. Supported: google, github'
            ], 400);
        }

        $redirectUrl = Socialite::driver($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * Handle the callback from the OAuth provider.
     * Creates or links a user and returns a Sanctum token.
     */
    public function handleProviderCallback(string $provider)
    {
        if (!in_array($provider, ['google', 'github'])) {
            return response()->json([
                'message' => 'Invalid provider. Supported: google, github'
            ], 400);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            // Redirect to frontend with an error flag instead of raw JSON
            return redirect(config('app.frontend_url') . '/login?error=oauth_failed');
        }

        // Check if user already exists by provider info
        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($user) {
            // Refresh avatar from provider on each login
            $avatar = $socialUser->getAvatar();
            if ($avatar && $user->avatar !== $avatar) {
                $user->update(['avatar' => $avatar]);
            }
        }

        // If not found by provider, check by email
        if (!$user) {
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Link the provider to an existing user
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $user->avatar ?: ($socialUser->getAvatar() ?: null),
                ]);
            } else {
                // Create a new user from social data
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname(),
                    'email' => $socialUser->getEmail(),
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'role' => 'trainer',
                    'avatar' => $socialUser->getAvatar() ?: null,
                    'password' => null,
                ]);
            }
        }

        // Generate Sanctum token
        $token = $user->createToken('api')->plainTextToken;

        // Redirect back to the frontend with the token as a query param
        return redirect(config('app.frontend_url') . '/auth/callback?token=' . $token);
    }
}
