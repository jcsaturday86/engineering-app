<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Billing;
use App\Models\Collection;
use App\Models\CollectionDetail;
use App\Models\VoidTransaction;
use App\Notifications\PaymentPostedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CollectionController extends Controller
{
    public function index()
    {
        $collections = Collection::with('application.permitType')
            ->where('status', 'active')
            ->latest()
            ->paginate(20);

        return view('collections.index', compact('collections'));
    }

    public function create(Application $application)
    {
        if ($application->status !== 'billed') {
            return back()->with('error', 'Application is not ready for payment.');
        }

        $billing = Billing::with('billingItems')
            ->where('application_id', $application->id)
            ->where('status', 'unpaid')
            ->latest()
            ->firstOrFail();

        return view('collections.create', compact('application', 'billing'));
    }

    public function store(Request $request, Application $application)
    {
        $validated = $request->validate([
            'or_number' => 'required|string|max:30|unique:collections,or_number',
            'paid_by' => 'required|string|max:255',
            'amount_received' => 'required|numeric|min:0',
            'payment_mode' => 'required|in:cash,check,online',
            'bank_name' => 'required_if:payment_mode,check|nullable|string|max:255',
            'check_number' => 'required_if:payment_mode,check|nullable|string|max:50',
            'check_date' => 'required_if:payment_mode,check|nullable|date',
            'online_reference' => 'required_if:payment_mode,online|nullable|string|max:100',
        ]);

        $billing = Billing::with('billingItems')
            ->where('application_id', $application->id)
            ->where('status', 'unpaid')
            ->latest()
            ->firstOrFail();

        $existingCollection = Collection::where('application_id', $application->id)
            ->where('status', 'active')
            ->first();

        if ($existingCollection) {
            return back()->with('error', 'Payment already exists for this application.');
        }

        DB::transaction(function () use ($validated, $application, $billing) {
            $collection = Collection::create([
                'application_id' => $application->id,
                'billing_id' => $billing->id,
                'or_number' => $validated['or_number'],
                'or_date' => now()->toDateString(),
                'paid_by' => $validated['paid_by'],
                'amount_due' => $billing->total_amount,
                'amount_received' => $validated['amount_received'],
                'change_amount' => max(0, $validated['amount_received'] - $billing->total_amount),
                'payment_mode' => $validated['payment_mode'],
                'bank_name' => $validated['bank_name'] ?? null,
                'check_number' => $validated['check_number'] ?? null,
                'check_date' => $validated['check_date'] ?? null,
                'online_reference' => $validated['online_reference'] ?? null,
                'collected_by' => Auth::id(),
                'status' => 'active',
            ]);

            foreach ($billing->billingItems as $item) {
                CollectionDetail::create([
                    'collection_id' => $collection->id,
                    'fee_category' => $item->category,
                    'description' => $item->description,
                    'amount' => $item->amount,
                ]);
            }

            $billing->update(['status' => 'paid']);
            $application->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            activity()->causedBy(Auth::user())->performedOn($application)->log('Payment collected');
        });

        $collection = Collection::where('application_id', $application->id)->where('status', 'active')->latest()->first();

        // Notify client user if linked
        if ($application->client_user_id) {
            $application->clientUser->notify(new PaymentPostedNotification($application, $collection));
        }

        return redirect()->route('collections.receipt', $collection)->with('success', 'Payment recorded successfully.');
    }

    public function receipt(Collection $collection)
    {
        $collection->load('application.permitType', 'collectionDetails', 'collectedBy');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.official-receipt', compact('collection'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("or_{$collection->or_number}.pdf");
    }

    public function voidForm()
    {
        return view('collections.void');
    }

    public function processVoid(Request $request)
    {
        $validated = $request->validate([
            'or_number' => 'required|string',
            'reason' => 'required|string|max:500',
            'password' => 'required|string',
        ]);

        if (!Hash::check($validated['password'], Auth::user()->password)) {
            return back()->with('error', 'Invalid password.');
        }

        $collection = Collection::where('or_number', $validated['or_number'])
            ->where('status', 'active')
            ->first();

        if (!$collection) {
            return back()->with('error', 'Active receipt not found.');
        }

        DB::transaction(function () use ($collection, $validated) {
            VoidTransaction::create([
                'collection_id' => $collection->id,
                'or_number' => $collection->or_number,
                'reason' => $validated['reason'],
                'voided_by' => Auth::id(),
                'voided_at' => now(),
            ]);

            $collection->update(['status' => 'void']);
            $collection->collectionDetails()->update(['is_active' => false]);

            if ($collection->billing) {
                $collection->billing->update(['status' => 'unpaid']);
            }

            $application = $collection->application;
            $previousStatus = $application->permitType->code === 'OP' ? 'engineering_assessed' : 'billed';
            $application->update([
                'status' => $previousStatus,
                'paid_at' => null,
            ]);

            activity()->causedBy(Auth::user())->performedOn($collection)->log('Receipt voided');
        });

        return back()->with('success', "Official Receipt {$collection->or_number} has been voided.");
    }
}
