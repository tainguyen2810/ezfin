<?php

namespace App\Modules\Personal\Http\Controllers\Auth;

use App\Models\User;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController
{
    public function create(): View
    {
        return view('personal::auth.register');
    }

    public function store(Request $request, CreateDefaultCategories $defaults): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Bạn cần đồng ý với điều khoản sử dụng và chính sách quyền riêng tư.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->forceFill([
            'terms_accepted_at' => now(),
            'marketing_consent_at' => $request->boolean('marketing') ? now() : null,
        ])->saveQuietly();

        $defaults->handle($user);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('personal.dashboard')
            ->with('toast', ['message' => 'Chào mừng bạn đến với EzFin! Hãy tạo ví đầu tiên để bắt đầu.', 'type' => 'success']);
    }
}
