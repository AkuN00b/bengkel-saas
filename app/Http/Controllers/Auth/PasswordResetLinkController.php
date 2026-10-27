<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->safe()->only('email'));

        // Keep the public response identical for existing and unknown accounts.
        return back()->with('status', __('passwords.sent'));
    }
}
