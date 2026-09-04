<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePaymentTypeAvailabilityRequest;
use App\Models\PaymentType;
use App\Models\PaymentTypeAvailability;
use App\Services\Payments\PaymentCatalogAuditService;
use App\Support\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentTypeAvailabilityController extends Controller
{
    public function __construct(
        private readonly PaymentCatalogAuditService $audits,
    ) {}
    public function index(PaymentType $paymentType): View
    {
        $rules = $paymentType->availabilities()->orderBy('user_type')->orderBy('residential_status')->get();

        return view('admin.e-payments.type-availabilities.index', [
            'type' => $paymentType,
            'rules' => $rules,
        ]);
    }

    public function create(PaymentType $paymentType): View
    {
        return view('admin.e-payments.type-availabilities.create', [
            'type' => $paymentType,
            'userTypes' => $this->canonicalUserTypeOptions(),
            'naturalPersonTypes' => UserType::naturalPersonStorageValues(),
        ]);
    }

    public function store(StorePaymentTypeAvailabilityRequest $request, PaymentType $paymentType): RedirectResponse
    {
        DB::transaction(function () use ($request, $paymentType) {
            $rule = $paymentType->availabilities()->create([
                'user_type' => $request->validated('user_type'),
                'residential_status' => $request->validated('residential_status'),
                'is_active' => true,
            ]);
            $this->audits->typeAvailabilityAdded($request->user(), $rule);
        });

        return redirect()
            ->route('admin.e-payments.payment-types.availabilities.index', $paymentType)
            ->with('success', 'Pravilo dostupnosti vrste je sačuvano. Nije 17/41 mapiranje.');
    }

    public function activate(Request $request, PaymentType $paymentType, PaymentTypeAvailability $paymentTypeAvailability): RedirectResponse
    {
        $this->assertRuleBelongsToType($paymentType, $paymentTypeAvailability);

        DB::transaction(function () use ($request, $paymentTypeAvailability) {
            $fromActive = (bool) $paymentTypeAvailability->is_active;
            $paymentTypeAvailability->update(['is_active' => true]);
            $this->audits->typeAvailabilityActivated(
                $request->user(),
                $paymentTypeAvailability->fresh() ?? $paymentTypeAvailability,
                $fromActive
            );
        });

        return redirect()
            ->route('admin.e-payments.payment-types.availabilities.index', $paymentType)
            ->with('success', 'Pravilo dostupnosti je aktivirano.');
    }

    public function deactivate(Request $request, PaymentType $paymentType, PaymentTypeAvailability $paymentTypeAvailability): RedirectResponse
    {
        $this->assertRuleBelongsToType($paymentType, $paymentTypeAvailability);

        DB::transaction(function () use ($request, $paymentTypeAvailability) {
            $fromActive = (bool) $paymentTypeAvailability->is_active;
            $paymentTypeAvailability->update(['is_active' => false]);
            $this->audits->typeAvailabilityDeactivated(
                $request->user(),
                $paymentTypeAvailability->fresh() ?? $paymentTypeAvailability,
                $fromActive
            );
        });

        return redirect()
            ->route('admin.e-payments.payment-types.availabilities.index', $paymentType)
            ->with('success', 'Pravilo dostupnosti je deaktivirano. Red nije obrisan.');
    }

    private function assertRuleBelongsToType(PaymentType $paymentType, PaymentTypeAvailability $rule): void
    {
        abort_unless($rule->payment_type_id === $paymentType->id, 404);
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
