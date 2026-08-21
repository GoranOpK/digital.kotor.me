<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Payments\EpModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EpModuleSettingsController extends Controller
{
    public function __construct(
        private readonly EpModuleSettings $settings
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
        $this->settings->setNewPaymentsEnabled($enabled);

        return redirect()
            ->route('admin.e-payments.settings.edit')
            ->with('success', $enabled
                ? 'Nova plaćanja su omogućena.'
                : 'Nova plaćanja su onemogućena.');
    }
}
