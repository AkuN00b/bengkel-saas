<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterOwner;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredUserController extends Controller
{
    public function store(RegisterRequest $request, RegisterOwner $registerOwner): RedirectResponse
    {
        $user = $registerOwner->handle($request->safe()->only('name', 'email', 'password', 'workshop_name'));

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
