<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentTypeRequest;
use App\Http\Requests\Admin\UpdatePaymentTypeRequest;
use App\Models\PaymentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentTypeController extends Controller
{
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
        $type = PaymentType::query()->create([
            'code' => $request->validated('code'),
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'is_active' => false,
        ]);

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
        $paymentType->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        return redirect()
            ->route('admin.e-payments.payment-types.index')
            ->with('success', 'Vrsta plaćanja je ažurirana.');
    }

    public function activate(PaymentType $paymentType): RedirectResponse
    {
        $reason = $paymentType->activationBlockReason();
        if ($reason !== null) {
            return redirect()
                ->route('admin.e-payments.payment-types.index')
                ->with('error', $reason);
        }

        $paymentType->update(['is_active' => true]);

        return redirect()
            ->route('admin.e-payments.payment-types.index')
            ->with('success', 'Vrsta plaćanja je aktivirana (lokalni katalog; nije production-ready).');
    }

    public function deactivate(PaymentType $paymentType): RedirectResponse
    {
        $paymentType->update(['is_active' => false]);

        return redirect()
            ->route('admin.e-payments.payment-types.index')
            ->with('success', 'Vrsta plaćanja je deaktivirana. Računi nisu obrisani.');
    }
}
