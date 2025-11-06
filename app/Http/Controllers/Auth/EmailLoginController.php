<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EmailLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('livewire.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->first();
        
        if ($user) {
            Auth::login($user);
            return redirect()->intended(route('dashboard'));
        }

        // This line will only be reached if the email doesn't exist
        // but we don't want to reveal that information for security reasons
        return back()->with('status', 'If an account exists with this email, you are now logged in.');
    }
}
