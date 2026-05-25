<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function index()
    {
        $user = Auth::user();
        $transactions = Transaction::where('is_active', true)->orderBy('name')->get();

        return view('account.profile', compact('user', 'transactions'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\-\.]+,(\s[a-zA-Z\s\-\.]+)+$/'],
            'transaction_id' => ['nullable', 'exists:transactions,id'],
            'counter_id' => ['nullable', 'integer', 'min:1', 'max:99'],
        ], [
            'name.regex' => 'The full name must be in "Lastname, Firstname Middlename" format.',
        ]);

        $user->fill([
            'name' => $validated['name'],
            'transaction_id' => $validated['transaction_id'] ?? null,
            'counter_id' => $validated['counter_id'] ?? null,
        ]);

        $user->save();

        return redirect()->route('account.profile')->with('status', 'profile-updated');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
