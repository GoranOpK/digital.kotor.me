<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentAccountAvailabilityRequest;
use App\Models\PaymentAccount;
use App\Models\PaymentAccountAvailability;
use App\Models\PaymentType;
use App\Services\Payments\PaymentCatalogAuditService;
use App\Support\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentAccountAvailabilityController extends Controller
{
    public function __construct(
        private readonly PaymentCatalogAuditService $audits,
    ) {}
    public function index(PaymentType $paymentType, PaymentAccount $paymentAccount): View
    {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);

        $rules = $paymentAccount->availabilities()->orderBy('user_type')->orderBy('residential_status')->get();

        return view('admin.e-payments.account-availabilities.index', [
            'type' => $paymentType,
            'account' => $paymentAccount,
            'rules' => $rules,
        ]);
    }

    public function create(PaymentType $paymentType, PaymentAccount $paymentAccount): View
    {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);

        return view('admin.e-payments.account-availabilities.create', [
            'type' => $paymentType,
            'account' => $paymentAccount,
            'userTypes' => $this->canonicalUserTypeOptions(),
            'naturalPersonTypes' => UserType::naturalPersonStorageValues(),
        ]);
    }

    public function store(
        StorePaymentAccountAvailabilityRequest $request,
        PaymentType $paymentType,
        PaymentAccount $paymentAccount
    ): RedirectResponse {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);

        DB::transaction(function () use ($request, $paymentAccount) {
            $rule = $paymentAccount->availabilities()->create([
                'user_type' => $request->validated('user_type'),
                'residential_status' => $request->validated('residential_status'),
                'is_active' => true,
            ]);
            $this->audits->accountAvailabilityAdded($request->user(), $rule);
        });

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.availabilities.index', [$paymentType, $paymentAccount])
            ->with('success', 'Pravilo dostupnosti računa je sačuvano. Nije 17/41 mapiranje.');
    }

    public function activate(
        Request $request,
        PaymentType $paymentType,
        PaymentAccount $paymentAccount,
        PaymentAccountAvailability $paymentAccountAvailability
    ): RedirectResponse {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);
        $this->assertRuleBelongsToAccount($paymentAccount, $paymentAccountAvailability);

        DB::transaction(function () use ($request, $paymentAccountAvailability) {
            $fromActive = (bool) $paymentAccountAvailability->is_active;
            $paymentAccountAvailability->update(['is_active' => true]);
            $this->audits->accountAvailabilityActivated(
                $request->user(),
                $paymentAccountAvailability->fresh() ?? $paymentAccountAvailability,
                $fromActive
            );
        });

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.availabilities.index', [$paymentType, $paymentAccount])
            ->with('success', 'Pravilo dostupnosti je aktivirano.');
    }

    public function deactivate(
        Request $request,
        PaymentType $paymentType,
        PaymentAccount $paymentAccount,
        PaymentAccountAvailability $paymentAccountAvailability
    ): RedirectResponse {
        $this->assertAccountBelongsToType($paymentType, $paymentAccount);
        $this->assertRuleBelongsToAccount($paymentAccount, $paymentAccountAvailability);

        DB::transaction(function () use ($request, $paymentAccountAvailability) {
            $fromActive = (bool) $paymentAccountAvailability->is_active;
            $paymentAccountAvailability->update(['is_active' => false]);
            $this->audits->accountAvailabilityDeactivated(
                $request->user(),
                $paymentAccountAvailability->fresh() ?? $paymentAccountAvailability,
                $fromActive
            );
        });

        return redirect()
            ->route('admin.e-payments.payment-types.accounts.availabilities.index', [$paymentType, $paymentAccount])
            ->with('success', 'Pravilo dostupnosti je deaktivirano. Red nije obrisan.');
    }

    private function assertAccountBelongsToType(PaymentType $paymentType, PaymentAccount $paymentAccount): void
    {
        abort_unless($paymentAccount->payment_type_id === $paymentType->id, 404);
    }

    private function assertRuleBelongsToAccount(PaymentAccount $paymentAccount, PaymentAccountAvailability $rule): void
    {
        abort_unless($rule->payment_account_id === $paymentAccount->id, 404);
    }

    /**
     * @return array<string, string>
     */
    private function canonicalUserTypeOptions(): array
    {
        $options = [];
        foreach (UserType::canonicalStorageValues() as $value) {
            $options[$value] = UserType::displayLabel($value);
        }

        return $options;
    }
}
