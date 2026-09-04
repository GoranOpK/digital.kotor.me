<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResidentialDeclarationRequest;
use App\Services\Payments\EpModuleSettings;
use App\Support\ResidentialStatusDeclaration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentResidentialDeclarationController extends Controller
{
    public function __construct(
        private readonly EpModuleSettings $module
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->module->newPaymentsEnabled()) {
            return redirect()->route('payments.index');
        }

        if (! ResidentialStatusDeclaration::isApplicable($request->user())) {
            return redirect()->to($this->intended($request));
        }

        return view('payments.declare-residential');
    }

    public function store(StoreResidentialDeclarationRequest $request): RedirectResponse
    {
        if (! $this->module->newPaymentsEnabled()) {
            return redirect()->route('payments.index');
        }

        $user = $request->user();
        if (! ResidentialStatusDeclaration::isApplicable($user)) {
            return redirect()->to($this->intended($request));
        }

        $user->residential_status = $request->validated('residential_status');
        $user->save();

        $intended = $this->intended($request);
        $request->session()->forget('ep_declaration_intended');

        return redirect()->to($intended);
    }

    private function intended(Request $request): string
    {
        $intended = $request->session()->get('ep_declaration_intended', '/payments');

        if (! is_string($intended) || ! str_starts_with($intended, '/payments')) {
            return '/payments';
        }

        return $intended;
    }
}
