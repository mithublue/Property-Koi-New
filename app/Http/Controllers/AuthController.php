<?php

namespace App\Http\Controllers;

use App\Mail\AccountActivationMail;
use App\Models\User;
use App\Services\AccessControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect(AccessControlService::getLoginRedirectUrl(Auth::user()));
        }

        $isRegisterTab = request('tab') === 'register';

        return view('pages.login', ['isRegisterTab' => $isRegisterTab]);
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect(AccessControlService::getLoginRedirectUrl(Auth::user()));
        }

        return view('pages.login', ['isRegisterTab' => true]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email_or_phone' => 'required|string',
            'password'       => 'required|string',
        ]);

        $input = trim($credentials['email_or_phone']);

        $user = User::where('email', $input)
            ->orWhere('username', $input)
            ->orWhere('phone', $input)
            ->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $request->boolean('remember', true));
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            $targetUrl = AccessControlService::getLoginRedirectUrl($user);
            return redirect()->intended($targetUrl);
        }

        return back()->withErrors([
            'email_or_phone' => 'প্রদত্ত তথ্য সঠিক নয়। ইমেইল, ইউজারনেম অথবা মোবাইল নম্বর এবং পাসওয়ার্ড যাচাই করুন।',
        ])->withInput();
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'username'        => 'required|string|alpha_dash|max:50|unique:users,username',
            'email'           => 'required|email|max:100|unique:users,email',
            'password'        => 'required|string|min:6|confirmed',
            'whatsapp_number' => 'nullable|string|max:30|unique:users,phone|unique:users,whatsapp_number',
        ], [
            'username.unique'        => 'এই ইউজারনেমটি ইতিমধ্যে ব্যবহৃত হয়েছে। অন্য একটি ইউজারনেম নির্বাচন করুন।',
            'username.alpha_dash'    => 'ইউজারনেমে শুধুমাত্র ইংরেজি অক্ষর, সংখ্যা, ড্যাশ ও আন্ডারস্কোর ব্যবহার করতে পারবেন।',
            'email.unique'           => 'এই ইমেইল ঠিকানাটি দিয়ে ইতিমধ্যে একটি অ্যাকাউন্ট রয়েছে। লগইন করার চেষ্টা করুন।',
            'whatsapp_number.unique' => 'এই মোবাইল বা WhatsApp নম্বরটি দিয়ে ইতিমধ্যে একটি অ্যাকাউন্ট রয়েছে।',
            'password.confirmed'     => 'পাসওয়ার্ড নিশ্চিতকরণ মিলছে না।',
            'password.min'           => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
        ]);

        $token = Str::random(64);
        $code  = (string) random_int(100000, 999999);

        $phone = $validated['whatsapp_number'] ?? null;

        $user = User::create([
            'name'                      => $validated['name'],
            'username'                  => strtolower($validated['username']),
            'email'                     => strtolower($validated['email']),
            'whatsapp_number'           => $phone,
            'phone'                     => $phone,
            'password'                  => Hash::make($validated['password']),
            'role'                      => 'buyer',
            'email_verified_at'         => null,
            'email_verification_token'  => $token,
            'email_verification_code'   => $code,
            'email_verification_sent_at'=> now(),
            'preferred_type'            => 'any',
        ]);

        // Send branded account activation mail
        $activationUrl = route('auth.verify-email', ['token' => $token]);
        try {
            Mail::to($user->email)->send(new AccountActivationMail($user, $activationUrl, $code));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Activation email sending failed: ' . $e->getMessage());
        }

        // Direct login right into buyer dashboard
        Auth::login($user, true);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return redirect()->route('buyer.dashboard')->with('success', 'আপনার অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে! একটি অ্যাক্টিভেশন লিঙ্ক ও ওটিপি কোড আপনার ইমেইলে পাঠানো হয়েছে।');
    }

    public function verifyEmail(string $token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return redirect()->route('buyer.dashboard')->with('error', 'অ্যাক্টিভেশন লিঙ্কটি সঠিক নয় অথবা ইতিমধ্যে ব্যবহৃত হয়েছে।');
        }

        $user->update([
            'email_verified_at'        => now(),
            'email_verification_token' => null,
            'email_verification_code'  => null,
        ]);

        if (!Auth::check() || Auth::id() !== $user->id) {
            Auth::login($user, true);
            if (request()->hasSession()) {
                request()->session()->regenerate();
            }
        }

        return redirect(route('buyer.dashboard') . '?email_verified=1')
            ->with('success', 'অভিনন্দন! আপনার ইমেইল সফলভাবে ভেরিফাই সম্পন্ন হয়েছে।');
    }

    public function resendVerification(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়। অনুগ্রহ করে লগইন করুন।'], 401);
        }

        if (empty($user->email)) {
            return response()->json(['success' => false, 'message' => 'আপনার অ্যাকাউন্টে কোনো ইমেইল ঠিকানা যুক্ত নেই।'], 422);
        }

        $token = Str::random(64);
        $code  = (string) random_int(100000, 999999);

        $user->update([
            'email_verification_token'   => $token,
            'email_verification_code'    => $code,
            'email_verification_sent_at' => now(),
        ]);

        $activationUrl = route('auth.verify-email', ['token' => $token]);

        try {
            Mail::to($user->email)->send(new AccountActivationMail($user, $activationUrl, $code));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ইমেইল পাঠাতে সমস্যা হয়েছে: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'আপনার ইমেইলে (' . $user->email . ') নতুন ভেরিফিকেশন কোড ও লিঙ্ক পাঠানো হয়েছে!',
            'code_sent' => true,
        ]);
    }

    public function verifyEmailCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'অনুমোদিত নয়। লগইন করুন।'], 401);
        }

        $user->refresh();
        $code = trim($request->code);

        if (!empty($user->email_verification_code) && $user->email_verification_code === $code) {
            $user->update([
                'email_verified_at'        => now(),
                'email_verification_code'  => null,
                'email_verification_token' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ইমেইল সফলভাবে ভেরিফাই সম্পন্ন হয়েছে!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'ভুল বা মেয়াদোত্তীর্ণ ওটিপি কোড! দয়া করে সঠিক কোডটি লিখুন।',
        ], 422);
    }

    // ==========================================
    // GOOGLE / GMAIL OAUTH LOGIN & REGISTRATION
    // ==========================================
    public function redirectToGoogle()
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            // Local fallback / Mock tester for smooth testing without requiring Google Cloud keys in dev
            if (app()->environment('local')) {
                return view('pages.google-mock');
            }
            return redirect()->route('login');
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'গুগল সাইন-ইনে ত্রুটি ঘটেছে: ' . $e->getMessage());
        }

        $email = strtolower($googleUser->getEmail());
        $googleId = $googleUser->getId();

        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if ($user) {
            $user->update([
                'google_id'          => $googleId,
                'email_verified_at'  => $user->email_verified_at ?: now(),
                'avatar_url'         => $user->avatar_url ?: $googleUser->getAvatar(),
            ]);
        } else {
            $baseUsername = Str::slug($googleUser->getName(), '') ?: 'user';
            $uniqueUsername = $baseUsername . random_int(100, 999);
            while (User::where('username', $uniqueUsername)->exists()) {
                $uniqueUsername = $baseUsername . random_int(1000, 9999);
            }

            $user = User::create([
                'name'               => $googleUser->getName() ?: 'Google User',
                'username'           => $uniqueUsername,
                'email'              => $email,
                'google_id'          => $googleId,
                'avatar_url'         => $googleUser->getAvatar(),
                'email_verified_at'  => now(), // Google verified email
                'role'               => 'buyer',
                'password'           => Hash::make(Str::random(32)),
                'preferred_type'     => 'any',
            ]);
        }

        Auth::login($user, true);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $targetUrl = AccessControlService::getLoginRedirectUrl($user);
        return redirect()->intended($targetUrl)->with('success', 'গুগল অ্যাকাউন্টের মাধ্যমে সফলভাবে লগইন সম্পন্ন হয়েছে!');
    }

    /**
     * Local development helper for instant Google 1-click test simulation
     */
    public function handleGoogleMockLogin(Request $request)
    {
        if (!app()->environment('local')) {
            abort(403);
        }

        $email = $request->input('mock_email', 'buyer.google@example.com');
        $name = $request->input('mock_name', 'Google Buyer User');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'username'          => 'google_buyer_' . random_int(100, 999),
                'google_id'         => 'mock_google_id_' . random_int(10000, 99999),
                'avatar_url'        => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
                'email_verified_at' => now(),
                'role'              => 'buyer',
                'password'          => Hash::make(Str::random(24)),
                'preferred_type'    => 'any',
            ]
        );

        Auth::login($user, true);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return redirect()->route('buyer.dashboard')->with('success', 'গুগল টেস্ট অ্যাকাউন্টে সফলভাবে প্রবেশ করা হয়েছে!');
    }

    public function magicLogin(string $token, Request $request)
    {
        if (empty($token) || strlen($token) < 24) {
            abort(403, 'অবৈধ ম্যাজিক টোকেন।');
        }

        $user = User::where('magic_token', $token)->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'ম্যাজিক লগইন লিঙ্কটি সঠিক নয় বা মেয়াদোত্তীর্ণ হয়েছে।');
        }

        // Security: Never allow magic link auto-login for administrators
        if ($user->isAdmin()) {
            abort(403, 'অ্যাডমিন অ্যাকাউন্টের ক্ষেত্রে ম্যাজিক লিঙ্ক অনুমোদিত নয়। পাসওয়ার্ড ব্যবহার করুন।');
        }

        Auth::login($user, true);
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user->update(['last_magic_login_at' => now()]);

        $redirect = $request->query('redirect');
        if ($redirect && filter_var($redirect, FILTER_VALIDATE_URL) === false && str_starts_with($redirect, '/')) {
            return redirect($redirect);
        }

        $targetUrl = AccessControlService::getLoginRedirectUrl($user);
        return redirect($targetUrl);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('home')->with('success', 'সফলভাবে লগআউট সম্পন্ন হয়েছে।');
    }
}
