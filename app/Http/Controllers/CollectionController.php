<?php

namespace App\Http\Controllers;

use App\Contracts\PermitApplicationContract;
use App\Models\Application;
use App\Models\Billing;
use App\Models\Collection;
use App\Models\CollectionDetail;
use App\Models\DemolitionApplication;
use App\Models\FencingApplication;
use App\Models\OccupancyApplication;
use App\Models\SignageApplication;
use App\Models\VoidTransaction;
use App\Notifications\PaymentPostedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        // Barcode scan / exact application number: go straight to the payment form
        if ($search !== '') {
            $bpExact = Application::where('application_number', $search)
                ->where('status', 'billed')
                ->whereDoesntHave('collections', fn ($q) => $q->where('status', 'active'))
                ->first();
            if ($bpExact) {
                return redirect()->route('collections.create', $bpExact);
            }
            $opExact = OccupancyApplication::where('application_number', $search)
                ->where('status', 'billed')
                ->whereDoesntHave('collections', fn ($q) => $q->where('status', 'active'))
                ->first();
            if ($opExact) {
                return redirect()->route('collections.create.op', $opExact);
            }
            $dpExact = DemolitionApplication::where('application_number', $search)
                ->where('status', 'billed')
                ->whereDoesntHave('collections', fn ($q) => $q->where('status', 'active'))
                ->first();
            if ($dpExact) {
                return redirect()->route('collections.create.dp', $dpExact);
            }
        }

        $bpForPayment = Application::with('permitType', 'billings')
            ->where('status', 'billed')
            ->whereDoesntHave('collections', fn ($q) => $q->where('status', 'active'))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('application_number', 'like', "%{$search}%")
                ->orWhere('applicant_last_name', 'like', "%{$search}%")
                ->orWhere('applicant_first_name', 'like', "%{$search}%")))
            ->latest()
            ->get();

        $opForPayment = OccupancyApplication::with('applicationType', 'billings')
            ->where('status', 'billed')
            ->whereDoesntHave('collections', fn ($q) => $q->where('status', 'active'))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('application_number', 'like', "%{$search}%")
                ->orWhere('applicant_last_name', 'like', "%{$search}%")
                ->orWhere('applicant_first_name', 'like', "%{$search}%")))
            ->latest()
            ->get();

        $dpForPayment = DemolitionApplication::with('billings')
            ->where('status', 'billed')
            ->whereDoesntHave('collections', fn ($q) => $q->where('status', 'active'))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('application_number', 'like', "%{$search}%")
                ->orWhere('applicant_last_name', 'like', "%{$search}%")
                ->orWhere('applicant_first_name', 'like', "%{$search}%")))
            ->latest()
            ->get();

        $forPayment = $bpForPayment->concat($opForPayment)->concat($dpForPayment)->sortByDesc('created_at');

        $month = $request->get('month', now()->format('Y-m'));
        $monthStart = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $collections = Collection::with('applicationable', 'collectedBy')
            ->where('status', 'active')
            ->where('collected_by', Auth::id())
            ->whereBetween('or_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('collections.index', compact('collections', 'forPayment', 'search', 'month'));
    }

    // BP payment
    public function create(Application $application)
    {
        return $this->doCreate($application);
    }

    // OP payment
    public function createOp(OccupancyApplication $occupancyApplication)
    {
        return $this->doCreate($occupancyApplication);
    }

    // DP payment
    public function createDp(DemolitionApplication $demolitionApplication)
    {
        return $this->doCreate($demolitionApplication);
    }

    // SGP payment
    public function createSgp(SignageApplication $signageApplication)
    {
        return $this->doCreate($signageApplication);
    }

    // FP payment
    public function createFp(FencingApplication $fencingApplication)
    {
        return $this->doCreate($fencingApplication);
    }

    private function doCreate(PermitApplicationContract $application)
    {
        if ($application->status !== 'billed') {
            return back()->with('error', 'Application is not ready for payment.');
        }

        $billing = $application->billings()
            ->with('billingItems')
            ->where('status', 'unpaid')
            ->latest()
            ->firstOrFail();

        return view('collections.create', compact('application', 'billing'));
    }

    // BP store payment
    public function store(Request $request, Application $application)
    {
        return $this->doStore($request, $application);
    }

    // OP store payment
    public function storeOp(Request $request, OccupancyApplication $occupancyApplication)
    {
        return $this->doStore($request, $occupancyApplication);
    }

    // DP store payment
    public function storeDp(Request $request, DemolitionApplication $demolitionApplication)
    {
        return $this->doStore($request, $demolitionApplication);
    }

    // SGP store payment
    public function storeSgp(Request $request, SignageApplication $signageApplication)
    {
        return $this->doStore($request, $signageApplication);
    }

    // FP store payment
    public function storeFp(Request $request, FencingApplication $fencingApplication)
    {
        return $this->doStore($request, $fencingApplication);
    }

    private function doStore(Request $request, PermitApplicationContract $application)
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

        $billing = $application->billings()
            ->with('billingItems')
            ->where('status', 'unpaid')
            ->latest()
            ->firstOrFail();

        if ($validated['payment_mode'] === 'cash' && $validated['amount_received'] < (float) $billing->total_amount) {
            return back()->withInput()->with('error', 'Amount received is less than the amount due.');
        }

        $morphType = match ($application->getPermitTypeCode()) {
            'OP' => 'op',
            'DP' => 'dp',
            'SGP' => 'sgp',
            'FP' => 'fp',
            default => 'bp',
        };

        $existingCollection = Collection::where('applicationable_type', $morphType)
            ->where('applicationable_id', $application->id)
            ->where('status', 'active')
            ->first();

        if ($existingCollection) {
            return back()->with('error', 'Payment already exists for this application.');
        }

        DB::transaction(function () use ($validated, $application, $billing, $morphType) {
            $collection = Collection::create([
                'applicationable_type' => $morphType,
                'applicationable_id' => $application->id,
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

        $collection = Collection::where('applicationable_type', $morphType)
            ->where('applicationable_id', $application->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($application->client_user_id) {
            $application->clientUser->notify(new PaymentPostedNotification($application, $collection));
        }

        return redirect()->route('collections.index')->with('success', 'Payment recorded successfully. OR Number: ' . $collection->or_number);
    }

    public function receipt(Collection $collection)
    {
        $collection->load('applicationable', 'collectionDetails', 'collectedBy');

        $application = $collection->applicationable;

        $settings = \App\Models\Setting::general();
        $sealImage = \App\Models\Setting::imageDataUri($settings, 'general.logo');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.official-receipt', compact('collection', 'application', 'settings', 'sealImage'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("or_{$collection->or_number}.pdf");
    }

    public function voidForm(Request $request)
    {
        $collection = null;

        if ($request->filled('or_number')) {
            $collection = Collection::with('applicationable')
                ->where('or_number', $request->or_number)
                ->where('status', 'active')
                ->first();
        }

        return view('collections.void', compact('collection'));
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

            $application = $collection->applicationable;
            $application->update([
                'status' => 'billed',
                'paid_at' => null,
            ]);

            activity()->causedBy(Auth::user())->performedOn($collection)->log('Receipt voided');
        });

        return back()->with('success', "Official Receipt {$collection->or_number} has been voided.");
    }
}
