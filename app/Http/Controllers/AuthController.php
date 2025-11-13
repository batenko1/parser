<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        if ($request->isMethod('POST')) {

            $user = User::query()->where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return back()
                    ->withInput()
                    ->with('error', 'Невірний email або пароль.');
            }

            Auth::login($user, true);

            return redirect()->route('index')->with('success', 'Успішний вхід!');
        }

        return view('auth.login');
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login')->with('success', 'Ви вийшли з системи.');
    }
}
