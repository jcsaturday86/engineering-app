<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Honeypot check — bots fill hidden fields, humans don't
        if ($request->filled('website') || $request->filled('full_name')) {
            // Silently reject — don't reveal it's a bot trap
            return redirect()->route('register')->with('error', 'Registration failed. Please try again.');
        }

        // Timestamp check — form submitted too fast (under 3 seconds = bot)
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'captcha' => ['required', 'integer'],
            'captcha_answer' => ['required', 'string'],
            'privacy_agreement' => ['required', 'accepted'],
        ], [
            'privacy_agreement.required' => 'You must agree to the Data Privacy Policy to register.',
            'privacy_agreement.accepted' => 'You must agree to the Data Privacy Policy to register.',
            'captcha.required' => 'Please solve the math problem.',
            'captcha.integer' => 'Please enter a valid number.',
        ]);

        // Verify CAPTCHA
        try {
            $correctAnswer = Crypt::decrypt($validated['captcha_answer']);
            if ((int) $validated['captcha'] !== (int) $correctAnswer) {
                return back()->withInput()->withErrors(['captcha' => 'Incorrect answer. Please try again.']);
            }
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['captcha' => 'Security check failed. Please try again.']);
        }

        $user = User::create([
            'name' => $validated['first_name'] . ' ' . $validated['last_name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->assignRole('client');

        Auth::login($user);

        activity()
            ->causedBy($user)
            ->log('Client account registered');

        return redirect()->route('online.dashboard');
    }
}
