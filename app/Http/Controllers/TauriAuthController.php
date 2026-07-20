<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class TauriAuthController extends Controller
{
    /**
     * Authenticate via credentials and return a Sanctum token.
     * The Tauri app stores this token in encrypted local storage.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Please check your email and password.',
            ], 401);
        }

        // Ensure user has a counter assignment before issuing token
        if (! $user->transaction_id || ! $user->counter_id) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not assigned to a counter. Please contact your administrator.',
            ], 403);
        }

        // Revoke any old Tauri tokens to keep it clean (1 active token per device)
        $user->tokens()->where('name', 'tauri-desktop')->delete();

        $token = $user->createToken('tauri-desktop', ['*'], now()->addDays(30));

        return response()->json([
            'success'      => true,
            'token'        => $token->plainTextToken,
            'expires_at'   => now()->addDays(30)->toIso8601String(),
            'user'         => $this->formatUser($user),
        ]);
    }

    /**
     * Verify a stored token is still valid and return refreshed user context.
     * Called on every Tauri app launch for auto-login.
     */
    public function verify(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid or expired.',
            ], 401);
        }

        if (! $user->transaction_id || ! $user->counter_id) {
            return response()->json([
                'success' => false,
                'message' => 'Counter assignment missing.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'user'    => $this->formatUser($user),
        ]);
    }

    /**
     * Revoke the current Tauri token (logout from desktop app).
     */
    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Logged out.']);
    }

    /**
     * Return a consistent user context object for the Tauri frontend.
     */
    private function formatUser(User $user): array
    {
        $availableTransactions = Transaction::query()
            ->where('is_active', true)
            ->where('id', '!=', $user->transaction_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'name' => $transaction->name,
            ])
            ->values()
            ->all();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'counter_id' => $user->counter_id,
            'transaction_id' => $user->transaction_id,
            'transaction_name' => $user->transaction?->name,
            'role' => $user->accessLevelLibrary?->name ?? 'Staff',
            'available_transactions' => $availableTransactions,
        ];
    }
}
