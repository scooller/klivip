<?php

namespace App\Http\Controllers\Front;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CustomerSessionController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $attemptCredentials = [
            ...$credentials,
            'role' => UserRole::User->value,
        ];

        $remember = $request->boolean('remember');

        if (! Auth::guard('customer')->attempt($attemptCredentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son validas para una cuenta de cliente.',
            ]);
        }

        $request->session()->regenerate();

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back();
    }
}
