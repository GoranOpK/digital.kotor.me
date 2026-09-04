<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Payments\EpModuleSettings;
use App\Services\Payments\PaymentCatalogAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EpModuleSettingsController extends Controller
{
    public function __construct(
        private readonly EpModuleSettings $settings,
        private readonly PaymentCatalogAuditService $audits,
    ) {}

    public function edit(): View
    {
        return view('admin.e-payments.settings', [
            'newPaymentsEnabled' => $this->settings->newPaymentsEnabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('new_payments_enabled');

        DB::transaction(function () use ($request, $enabled) {
            $was = $this->settings->newPaymentsEnabled();
            $this->settings->setNewPaymentsEnabled($enabled);
            if ($was !== $enabled) {
                $this->audits->moduleToggled($request->user(), $enabled);
            }
        });

        return redirect()
            ->route('admin.e-payments.settings.edit')
            ->with('success', $enabled
                ? 'Nova plaćanja su omogućena.'
                : 'Nova plaćanja su onemogućena.');
    }
}
