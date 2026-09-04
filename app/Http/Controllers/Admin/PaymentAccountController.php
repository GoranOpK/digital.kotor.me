<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentAccountRequest;
use App\Http\Requests\Admin\UpdatePaymentAccountRequest;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use App\Services\Payments\PaymentCatalogAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentAccountController extends Controller
{
    public function __construct(
        private readonly PaymentCatalogAuditService $audits,
    ) {}

    public function index(PaymentType $paymentType): View
    {
        $accounts = $paymentType->accounts()->orderBy('account_number')->paginate(20);

        return view('admin.e-payments.accounts.index', [
            'type' => $paymentType,
            'accounts' => $accounts,
        ]);
    }

    public function create(PaymentType $paymentType): View
    {
        return view('admin.e-payments.accounts.create', ['type' => $paymentType]);
    }

    public function store(StorePaymentAccountRequest $request, PaymentType $paymentType): RedirectResponse
    {
        DB::transaction(function () use ($request, $paymentType) {
            $account = $paymentType->accounts()->create([
                'account_number' => trim($request->validated('account_number')),
                'name' => $request->validated('name'),
                'is_active' => false,
            ]);
            $this->audits->accountCreated($request->user(), $account);
        });

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.index', $paymentType)
            ->with('success', 'Račun je sačuvan kao neaktivan. Broj računa se više ne može mijenjati.');
    }

    public function edit(PaymentType $paymentType, PaymentAccount $paymentAccount): View
    {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);

        return view('admin.e-payments.accounts.edit', [
            'type' => $paymentType,
            'account' => $paymentAccount,
        ]);
    }

    public function update(
        UpdatePaymentAccountRequest $request,
        PaymentType $paymentType,
        PaymentAccount $paymentAccount
    ): RedirectResponse {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);

        DB::transaction(function () use ($request, $paymentAccount) {
            $from = [
                'name' => (string) ($paymentAccount->name ?? ''),
                'is_active' => (bool) $paymentAccount->is_active,
            ];
            $paymentAccount->update([
                'name' => $request->validated('name'),
            ]);
            $this->audits->accountUpdated($request->user(), $paymentAccount->fresh() ?? $paymentAccount, $from);
        });

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.index', $paymentType)
            ->with('success', 'Račun je ažuriran. Broj računa nije mijenjan.');
    }

    public function activate(Request $request, PaymentType $paymentType, PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);

        $reason = $paymentAccount->activationBlockReason();
        if ($reason !== null) {
            return redirect()
                ->route('admin.e-payments.payment-types.accounts.index', $paymentType)
                ->with('error', $reason);
        }

        DB::transaction(function () use ($request, $paymentAccount) {
            $fromActive = (bool) $paymentAccount->is_active;
            $paymentAccount->update(['is_active' => true]);
            $this->audits->accountActivated($request->user(), $paymentAccount->fresh() ?? $paymentAccount, $fromActive);
        });

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.index', $paymentType)
            ->with('success', 'Račun je aktiviran (lokalni katalog; nije production-ready).');
    }

    public function deactivate(Request $request, PaymentType $paymentType, PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);

        DB::transaction(function () use ($request, $paymentType, $paymentAccount) {
            $fromAccountActive = (bool) $paymentAccount->is_active;
            $fromTypeActive = (bool) $paymentType->is_active;
            $paymentAccount->update(['is_active' => false]);
            $this->audits->accountDeactivated($request->user(), $paymentAccount->fresh() ?? $paymentAccount, $fromAccountActive);

            $paymentType->refresh();
            if ($fromTypeActive && $paymentType->activationBlockReason() !== null) {
                $paymentType->update(['is_active' => false]);
                $this->audits->typeDeactivated($request->user(), $paymentType->fresh() ?? $paymentType, $fromTypeActive);
            }
        });

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.index', $paymentType)
            ->with('success', 'Račun je deaktiviran. Red nije obrisan.');
    }

    private function assertAccountBelongsToType(PaymentType $paymentType, PaymentAccount $paymentAccount): void
    {
        abort_unless($paymentAccount->payment_type_id === $paymentType->id, 404);
    }
}
