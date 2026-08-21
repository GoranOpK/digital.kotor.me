<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentAccountRequest;
use App\Http\Requests\Admin\UpdatePaymentAccountRequest;
use App\Models\PaymentAccount;
use App\Models\PaymentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentAccountController extends Controller
{
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
        $paymentType->accounts()->create([
            'account_number' => trim($request->validated('account_number')),
            'name' => $request->validated('name'),
            'is_active' => false,
        ]);

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

        $paymentAccount->update([
            'name' => $request->validated('name'),
        ]);

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.index', $paymentType)
            ->with('success', 'Račun je ažuriran. Broj računa nije mijenjan.');
    }

    public function activate(PaymentType $paymentType, PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);

        $reason = $paymentAccount->activationBlockReason();
        if ($reason !== null) {
            return redirect()
                ->route('admin.e-payments.payment-types.accounts.index', $paymentType)
                ->with('error', $reason);
        }

        $paymentAccount->update(['is_active' => true]);

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.index', $paymentType)
            ->with('success', 'Račun je aktiviran (lokalni katalog; nije production-ready).');
    }

    public function deactivate(PaymentType $paymentType, PaymentAccount $paymentAccount): RedirectResponse
    {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);

        $paymentAccount->update(['is_active' => false]);

        if ($paymentType->is_active && $paymentType->activationBlockReason() !== null) {
            $paymentType->update(['is_active' => false]);
        }

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.index', $paymentType)
            ->with('success', 'Račun je deaktiviran. Red nije obrisan.');
    }

    private function assertAccountBelongsToType(PaymentType $paymentType, PaymentAccount $paymentAccount): void
    {
        abort_unless($paymentAccount->payment_type_id === $paymentType->id, 404);
    }
}
