<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = auth()->user();

        // Redirect based on user role
        $redirectUrl = match ($user->role) {
            'admin' => route('admin.dashboard'),
            'instructor' => route('instructor.dashboard'),
            'student' => route('student.dashboard'),
            default => null,
        };

        if ($redirectUrl) {
            // Force full page redirect to ensure session cookie is properly sent.
            // Inertia XHR redirects can fail to attach the new session on first request after login.
            if ($request->header('X-Inertia')) {
                return Inertia::location($redirectUrl);
            }
            return redirect()->intended($redirectUrl);
        }

        // If role is not recognized, logout and return error
        Auth::logout();
        return back()->withErrors([
            'id_number' => 'Invalid user role.',
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
