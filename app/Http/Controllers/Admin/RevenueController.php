<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistoricalArchive;
use App\Models\Property;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class RevenueController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = Transaction::with(['property', 'seller', 'lead']);

        if ($status !== 'all') {
            $query->where('fee_status', $status);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('transaction_code', 'like', "%{$s}%")
                  ->orWhere('buyer_name', 'like', "%{$s}%")
                  ->orWhere('buyer_phone', 'like', "%{$s}%");
            });
        }

        $transactions = $query->latest('closed_at')->paginate(15)->withQueryString();

        $grossVolume = Transaction::sum('agreed_price');
        $totalCommission = Transaction::sum('platform_fee_amount');
        $collectedCommission = Transaction::where('fee_status', 'collected')->sum('platform_fee_amount');
        $pendingCommission = Transaction::where('fee_status', 'pending')->sum('platform_fee_amount');

        $stats = [
            'gross_volume'         => $grossVolume,
            'total_commission'     => $totalCommission,
            'collected_commission' => $collectedCommission,
            'pending_commission'   => $pendingCommission,
            'closed_deals_count'   => Transaction::count(),
        ];

        $properties = Property::whereIn('status', ['live', 'verified', 'sold'])->orderBy('title')->get();
        $sellers = User::whereIn('role', ['agent', 'owner'])->orderBy('name')->get();

        return view('admin.revenue', compact('transactions', 'stats', 'status', 'properties', 'sellers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id'          => 'required|exists:properties,id',
            'buyer_name'           => 'required|string|max:255',
            'buyer_phone'          => 'required|string|max:50',
            'seller_type'          => 'required|in:direct_owner,direct_agent',
            'seller_user_id'       => 'nullable|exists:users,id',
            'agreed_price'         => 'required|numeric|min:1',
            'platform_fee_percent' => 'nullable|numeric|min:0|max:100',
            'fee_status'           => 'required|in:pending,partial,collected,waived',
            'closed_at'            => 'required|date',
            'notes'                => 'nullable|string',
        ]);

        $feePercent = $validated['platform_fee_percent'] ?? 0.50;
        $feeAmount = round(($validated['agreed_price'] * $feePercent) / 100, 2);

        $transaction = Transaction::create(array_merge($validated, [
            'platform_fee_percent' => $feePercent,
            'platform_fee_amount'  => $feeAmount,
        ]));

        // Mark property as sold
        $property = Property::find($validated['property_id']);
        if ($property) {
            $property->update(['status' => 'sold']);

            // Record to Historical Archives for pricing intelligence
            HistoricalArchive::create([
                'property_id'            => $property->id,
                'property_type'          => $property->type,
                'block'                  => $property->block,
                'road_width_ft'          => 20,
                'facing'                 => $property->facing,
                'size_katha_or_sqft'     => $property->size ?: ($property->flat_size_sqft ?: 1),
                'initial_asking_price'   => $property->asking_price ?: $validated['agreed_price'],
                'final_negotiated_price' => $validated['agreed_price'],
                'sold_price'             => $validated['agreed_price'],
                'sold_date'              => $validated['closed_at'],
            ]);
        }

        return redirect()->route('admin.revenue.index')->with('success', "লেনদেন সফলভাবে রেকর্ড করা হয়েছে! কোড: #{$transaction->transaction_code}");
    }

    public function updateFeeStatus(Request $request, Transaction $transaction)
    {
        $status = $request->validate([
            'fee_status' => 'required|in:pending,partial,collected,waived'
        ])['fee_status'];

        $transaction->update(['fee_status' => $status]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'fee_status' => $status]);
        }

        return back()->with('success', "ফি স্ট্যাটাস '{$status}' আপডেট করা হয়েছে।");
    }
}
