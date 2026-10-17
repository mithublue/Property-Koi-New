<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerDemand;
use App\Models\Lead;
use App\Models\Property;
use App\Models\PropertyVerification;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_properties'      => Property::count(),
            'live_properties'       => Property::where('status', 'live')->count(),
            'pending_verifications' => Property::whereIn('status', ['pending', 'draft'])->count(),
            'total_leads'           => Lead::count(),
            'total_demands'         => BuyerDemand::count(),
            'total_agents'          => User::where('role', 'agent')->count(),
            'total_owners'          => User::where('role', 'owner')->count(),
            'gross_volume'          => Transaction::sum('agreed_price'),
            'total_commission'      => Transaction::sum('platform_fee_amount'),
        ];

        $recentProperties = Property::with(['media', 'creator'])
            ->latest()
            ->take(6)
            ->get();

        $recentLeads = Lead::with(['property', 'user'])
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentProperties', 'recentLeads'));
    }
}
