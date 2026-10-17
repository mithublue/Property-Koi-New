<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentNote;
use App\Models\AgentProfile;
use App\Models\User;
use App\Services\MagicLoginService;
use App\Services\MessageTemplateService;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role', 'all');
        $query = User::with(['agentProfile', 'properties'])->whereIn('role', ['agent', 'owner']);

        if ($role !== 'all') {
            $query->where('role', $role);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $users = $query->latest()->paginate(15)->withQueryString();

        // Ensure every user has a profile & magic token
        foreach ($users as $user) {
            if (!$user->agentProfile) {
                AgentProfile::create(['user_id' => $user->id]);
                $user->load('agentProfile');
            }
            if (empty($user->magic_token)) {
                MagicLoginService::getOrCreateToken($user);
            }
        }

        $stats = [
            'total_agents' => User::where('role', 'agent')->count(),
            'total_owners' => User::where('role', 'owner')->count(),
            'verified'     => AgentProfile::where('is_verified_direct_agent', true)->count(),
            'penalized'    => AgentProfile::whereIn('penalty_status', ['penalty_spam', 'bypassed_warning'])->count(),
        ];

        return view('admin.agents', compact('users', 'stats', 'role'));
    }

    public function updateProfile(Request $request, User $user)
    {
        $profile = $user->agentProfile ?: new AgentProfile(['user_id' => $user->id]);

        if ($request->has('rating')) {
            $profile->admin_fixed_rating = $request->input('rating');
        }

        if ($request->has('penalty_status')) {
            $profile->penalty_status = $request->input('penalty_status');
            $profile->penalty_reason = $request->input('penalty_reason');
        }

        if ($request->has('is_verified_direct_agent')) {
            $profile->is_verified_direct_agent = $request->boolean('is_verified_direct_agent');
            if ($profile->is_verified_direct_agent && !$profile->verified_at) {
                $profile->verified_at = now();
            }
        }

        $profile->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'প্রোফাইল আপডেট সফল হয়েছে।']);
        }

        return back()->with('success', 'প্রোফাইল তথ্য আপডেট করা হয়েছে।');
    }

    public function addNote(Request $request, User $user)
    {
        $validated = $request->validate([
            'note' => 'required|string',
        ]);

        AgentNote::create([
            'user_id'    => $user->id,
            'author_id'  => auth()->id(),
            'note'       => $validated['note'],
        ]);

        return back()->with('success', 'নোট সফলভাবে সংরক্ষণ করা হয়েছে।');
    }

    public function regenerateMagicToken(User $user)
    {
        $token = MagicLoginService::regenerateToken($user);
        $url = MagicLoginService::getMagicLoginUrl($user);

        return response()->json([
            'success' => true,
            'token'   => $token,
            'url'     => $url,
            'message' => 'নতুন ম্যাজিক লিঙ্ক তৈরি করা হয়েছে!'
        ]);
    }
}
