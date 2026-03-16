<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private CartService $cartService, private WishlistService $wishlistService)
    {
    }

    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        // If there's a redirect query parameter, set it as the intended URL
        if ($request->has('redirect')) {
            $request->session()->put('url.intended', $request->get('redirect'));
        }
        
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Get the guest session ID that was stored before authentication
        $guestSessionId = $request->session()->get('guest_session_before_login');
        $currentSessionId = $request->session()->getId();

        $request->session()->regenerate();

        // After session regeneration, merge the guest cart to the new session
        if ($guestSessionId && $guestSessionId !== $currentSessionId) {
            $this->cartService->mergeSessions($guestSessionId, $request->session()->getId());
        }

        // Merge guest wishlist to authenticated user's wishlist
        if ($guestSessionId) {
            $userId = Auth::id();
            if ($userId) {
                $this->wishlistService->merge($guestSessionId, $userId);
            }
        }

        // Clean up the session variable
        $request->session()->forget('guest_session_before_login');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Get the session ID before logout to clear guest session data
        $sessionId = $request->session()->getId();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // After logout, clear any guest session data that might exist
        // This ensures that when the user returns as a guest, they start with a clean session
        $this->cartService->clear($sessionId);
        $this->wishlistService->clearGuestSession($sessionId);

        return redirect('/');
    }
}
