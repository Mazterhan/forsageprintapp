<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\StrongUserPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ForcedPasswordChangeController extends Controller
{
    public function edit(): View
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('forcePasswordChange', [
            'password' => ['required', 'string', 'confirmed', new StrongUserPassword($request->user())],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_password_change' => false,
        ]);

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('status', __('Пароль оновлено.'));
    }
}
