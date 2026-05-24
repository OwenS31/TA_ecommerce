<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Send the password reset link.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return match ($status) {
            Password::RESET_LINK_SENT => back()->with('status', 'Link reset password telah dikirim ke email Anda.'),
            Password::RESET_THROTTLED => back()->withErrors([
                'email' => 'Permintaan terlalu sering. Coba lagi beberapa saat lagi.',
            ]),
            default => back()->withErrors([
                'email' => 'Jika email terdaftar, link reset password akan dikirim ke inbox Anda.',
            ]),
        };
    }
}
