<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentTypeRequest;
use App\Http\Requests\Admin\UpdatePaymentTypeRequest;
use App\Models\PaymentType;
use App\Services\Payments\PaymentCatalogAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentTypeController extends Controller
{
    public function __construct(
        private readonly PaymentCatalogAuditService $audits,
    ) {}

    public function index(): View
    {
        $types = PaymentType::query()
            ->withCount(['accounts', 'activeAccounts', 'availabilities'])
            ->orderBy('name')
            ->paginate(20);

        return view('admin.e-payments.payment-types.index', compact('types'));
    }

    public function create(): View
    {
        return view('admin.e-payments.payment-types.create');
    }

    public function store(StorePaymentTypeRequest $request): RedirectResponse
    {
        $type = DB::transaction(function () use ($request) {
            $type = PaymentType::query()->create([
                'code' => $request->validated('code'),
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'is_active' => false,
            ]);
            $this->audits->typeCreated($request->user(), $type);

            return $type;
        });

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.index', $type)
            ->with('success', 'Vrsta plaćanja je sačuvana kao neaktivna. Dodajte račun prije aktivacije.');
    }

    public function edit(PaymentType $paymentType): View
    {
        return view('admin.e-payments.payment-types.edit', ['type' => $paymentType]);
    }

    public function update(UpdatePaymentTypeRequest $request, PaymentType $paymentType): RedirectResponse
    {
        DB::transaction(function () use ($request, $paymentType) {
            $from = [
                'name' => (string) $paymentType->name,
                'description' => (string) ($paymentType->description ?? ''),
                'is_active' => (bool) $paymentType->is_active,
            ];
            $paymentType->update([
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
            ]);
            $this->audits->typeUpdated($request->user(), $paymentType->fresh() ?? $paymentType, $from);
        });

        return redirect()
            ->route('admin.e-payments.payment-types.index')
            ->with('success', 'Vrsta plaćanja je ažurirana.');
    }

    public function activate(Request $request, PaymentType $paymentType): RedirectResponse
    {
        $reason = $paymentType->activationBlockReason();
        if ($reason !== null) {
            return redirect()
                ->route('admin.e-payments.payment-types.index')
                ->with('error', $reason);
        }

        DB::transaction(function () use ($request, $paymentType) {
            $fromActive = (bool) $paymentType->is_active;
            $paymentType->update(['is_active' => true]);
            $this->audits->typeActivated($request->user(), $paymentType->fresh() ?? $paymentType, $fromActive);
        });

        return redirect()
            ->route('admin.e-payments.payment-types.index')
            ->with('success', 'Vrsta plaćanja je aktivirana (lokalni katalog; nije production-ready).');
    }

    public function deactivate(Request $request, PaymentType $paymentType): RedirectResponse
    {
        DB::transaction(function () use ($request, $paymentType) {
            $fromActive = (bool) $paymentType->is_active;
            $paymentType->update(['is_active' => false]);
            $this->audits->typeDeactivated($request->user(), $paymentType->fresh() ?? $paymentType, $fromActive);
        });

        return redirect()
            ->route('admin.e-payments.payment-types.index')
            ->with('success', 'Vrsta plaćanja je deaktivirana. Računi nisu obrisani.');
    }
}
