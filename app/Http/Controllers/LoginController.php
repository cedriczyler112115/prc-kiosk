<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = true;

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function ping(Request $request)
    {
        if (! Auth::check()) {
            return Response::noContent(401);
        }

        $now = time();
        $last = (int) $request->session()->get('auth_refreshed_at', 0);
        $rotateAfterSeconds = 60 * 60 * 24;

        if ($last <= 0 || ($now - $last) >= $rotateAfterSeconds) {
            $request->session()->migrate(true);
            $request->session()->put('auth_refreshed_at', $now);
        }

        return Response::noContent();
    }
}
