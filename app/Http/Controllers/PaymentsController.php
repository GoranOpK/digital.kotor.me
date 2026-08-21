<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentAccountSelectionRequest;
use App\Http\Requests\StorePaymentAmountRequest;
use App\Models\PaymentTransaction;
use App\Models\PaymentType;
use App\Services\Payments\EpModuleSettings;
use App\Services\Payments\FakePaymentGatewayUnavailableException;
use App\Services\Payments\PaymentAvailabilityService;
use App\Services\Payments\PaymentConfirmationAssembler;
use App\Services\Payments\PaymentConfirmationDeliveryService;
use App\Services\Payments\PaymentConfirmationPdfRenderer;
use App\Services\Payments\PaymentDraftService;
use App\Services\Payments\PaymentGatewayNotConfiguredException;
use App\Services\Payments\PaymentResultRejectedException;
use App\Services\Payments\PaymentStartService;
use App\Support\ResidentialStatusDeclaration;
use App\Support\UserType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use LogicException;

class PaymentsController extends Controller
{
    public function __construct(
        private readonly PaymentAvailabilityService $availability,
        private readonly PaymentDraftService $drafts,
        private readonly EpModuleSettings $module,
        private readonly PaymentStartService $starts,
        private readonly PaymentConfirmationAssembler $confirmations,
        private readonly PaymentConfirmationPdfRenderer $confirmationPdf,
        private readonly PaymentConfirmationDeliveryService $confirmationDeliveries,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->module->newPaymentsEnabled()) {
            return view('payments.disabled');
        }

        if ($redirect = $this->declarationRedirect($request)) {
            return $redirect;
        }

        $types = $this->availability->usableTypesFor($request->user());

        return view('payments.index', [
            'types' => $types,
        ]);
    }

    /**
     * Legacy stub POST. Must not create a transaction or start a gateway.
     */
    public function pay(): RedirectResponse
    {
        return redirect()->route('payments.index');
    }

    public function start(Request $request, PaymentType $paymentType): View|RedirectResponse
    {
        if ($blocked = $this->guardNewPayment($request)) {
            return $blocked;
        }

        $accounts = $this->availability->usableAccountsFor($request->user(), $paymentType);

        if ($accounts->isEmpty()) {
            $this->drafts->clear($request);

            return redirect()
                ->route('payments.index')
                ->with('error', 'Ova vrsta plaćanja trenutno nije dostupna za vaš profil.');
        }

        if ($accounts->count() === 1) {
            $account = $accounts->first();
            $this->drafts->put($request, [
                'payment_type_id' => $paymentType->id,
                'payment_account_id' => $account->id,
                'amount' => null,
            ]);

            return redirect()->route('payments.amount.edit');
        }

        return view('payments.select-account', [
            'type' => $paymentType,
            'accounts' => $accounts,
        ]);
    }

    public function storeAccount(
        StorePaymentAccountSelectionRequest $request,
        PaymentType $paymentType
    ): RedirectResponse {
        if ($blocked = $this->guardNewPayment($request)) {
            return $blocked;
        }

        $accountId = (int) $request->validated('payment_account_id');
        $account = $this->drafts->resolveUsableAccount($request->user(), $paymentType->id, $accountId);

        if ($account === null) {
            return redirect()
                ->route('payments.start', $paymentType)
                ->with('error', 'Izabrani račun nije dostupan.');
        }

        $this->drafts->put($request, [
            'payment_type_id' => $paymentType->id,
            'payment_account_id' => $account->id,
            'amount' => null,
        ]);

        return redirect()->route('payments.amount.edit');
    }

    public function editAmount(Request $request): View|RedirectResponse
    {
        if ($blocked = $this->guardNewPayment($request)) {
            return $blocked;
        }

        $resolved = $this->resolvedDraft($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        return view('payments.amount', [
            'type' => $resolved['type'],
            'account' => $resolved['account'],
            'amount' => $resolved['draft']['amount'] ?? null,
        ]);
    }

    public function storeAmount(StorePaymentAmountRequest $request): RedirectResponse
    {
        if ($blocked = $this->guardNewPayment($request)) {
            return $blocked;
        }

        $resolved = $this->resolvedDraft($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        $this->drafts->put($request, [
            'payment_type_id' => $resolved['type']->id,
            'payment_account_id' => $resolved['account']->id,
            'amount' => $this->normalizeAmount((string) $request->validated('amount')),
        ]);

        return redirect()->route('payments.preview');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        if ($blocked = $this->guardNewPayment($request)) {
            return $blocked;
        }

        $resolved = $this->resolvedDraft($request);
        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        $amount = $resolved['draft']['amount'] ?? null;
        if (! is_string($amount) || $amount === '') {
            return redirect()->route('payments.amount.edit');
        }

        $user = $request->user();

        $this->drafts->ensureMerchantTransactionId($request);

        return view('payments.preview', [
            'type' => $resolved['type'],
            'account' => $resolved['account'],
            'amount' => $amount,
            'currency' => 'EUR',
            'payer' => $this->drafts->payerLabel($user),
            'userTypeLabel' => UserType::displayLabel($user->user_type),
        ]);
    }

    public function launch(Request $request): RedirectResponse
    {
        if ($blocked = $this->guardNewPayment($request)) {
            return $blocked instanceof RedirectResponse
                ? $blocked
                : redirect()->route('payments.index');
        }

        try {
            $transaction = $this->starts->launch($request);
        } catch (PaymentResultRejectedException) {
            return redirect()
                ->route('payments.index')
                ->with('error', 'Plaćanje nije moglo biti pokrenuto. Počnite ponovo.');
        } catch (PaymentGatewayNotConfiguredException|FakePaymentGatewayUnavailableException) {
            return redirect()
                ->route('payments.index')
                ->with('error', 'Plaćanje trenutno nije dostupno. Pokušajte kasnije.');
        }

        return redirect()->to($this->starts->redirectAfterStart($transaction));
    }

    public function result(Request $request, PaymentTransaction $paymentTransaction): View
    {
        abort_unless((int) $paymentTransaction->user_id === (int) $request->user()->id, 404);

        $snapshot = is_array($paymentTransaction->snapshot) ? $paymentTransaction->snapshot : [];

        return view('payments.result', [
            'transaction' => $paymentTransaction,
            'snapshot' => $snapshot,
            'confirmationEmailSent' => $this->confirmationDeliveries->emailWasSent($paymentTransaction),
        ]);
    }

    public function downloadConfirmation(Request $request, PaymentTransaction $paymentTransaction): Response
    {
        abort_unless((int) $paymentTransaction->user_id === (int) $request->user()->id, 404);

        try {
            $confirmation = $this->confirmations->fromSuccessfulTransaction($paymentTransaction);
            $binary = $this->confirmationPdf->render($confirmation);
        } catch (LogicException) {
            abort(404);
        }

        return response($binary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$confirmation->pdfFilename.'"',
        ]);
    }

    public function abandon(Request $request): RedirectResponse
    {
        $this->drafts->clear($request);

        return redirect()
            ->route('payments.index')
            ->with('success', 'Plaćanje je otkazano prije pokretanja. Transakcija nije kreirana.');
    }

    private function guardNewPayment(Request $request): View|RedirectResponse|null
    {
        if (! $this->module->newPaymentsEnabled()) {
            $this->drafts->clear($request);

            return redirect()->route('payments.index');
        }

        return $this->declarationRedirect($request);
    }

    private function declarationRedirect(Request $request): ?RedirectResponse
    {
        if (! ResidentialStatusDeclaration::isApplicable($request->user())) {
            return null;
        }

        $request->session()->put('ep_declaration_intended', $this->safePaymentsPath($request));

        return redirect()->route('payments.declaration.create');
    }

    /**
     * @return array{draft: array<string, mixed>, type: PaymentType, account: \App\Models\PaymentAccount}|RedirectResponse
     */
    private function resolvedDraft(Request $request): array|RedirectResponse
    {
        $draft = $this->drafts->get($request);
        if ($draft === null) {
            return redirect()
                ->route('payments.index')
                ->with('error', 'Sesija plaćanja je istekla. Počnite ponovo.');
        }

        $account = $this->drafts->resolveUsableAccount(
            $request->user(),
            (int) ($draft['payment_type_id'] ?? 0),
            (int) ($draft['payment_account_id'] ?? 0)
        );

        if ($account === null) {
            $this->drafts->clear($request);

            return redirect()
                ->route('payments.index')
                ->with('error', 'Odabir više nije važeći. Katalog ili dostupnost su se izmijenili.');
        }

        return [
            'draft' => $draft,
            'type' => $account->paymentType,
            'account' => $account,
        ];
    }

    private function safePaymentsPath(Request $request): string
    {
        $path = '/'.$request->path();
        if ($request->getQueryString()) {
            $path .= '?'.$request->getQueryString();
        }

        return str_starts_with($path, '/payments') ? $path : '/payments';
    }

    private function normalizeAmount(string $amount): string
    {
        if (! str_contains($amount, '.')) {
            return $amount.'.00';
        }

        [$whole, $fraction] = explode('.', $amount, 2);

        return $whole.'.'.str_pad($fraction, 2, '0');
    }
}
