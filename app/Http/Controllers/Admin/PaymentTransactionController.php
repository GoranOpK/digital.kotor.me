<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FilterPaymentTransactionsRequest;
use App\Models\PaymentTransaction;
use App\Models\PaymentType;
use App\Services\Payments\FakePaymentGatewayUnavailableException;
use App\Services\Payments\GatewayInquiryException;
use App\Services\Payments\GatewayVerificationException;
use App\Services\Payments\PaymentAdminTimeline;
use App\Services\Payments\PaymentAdminTransactionQuery;
use App\Services\Payments\PaymentGatewayNotConfiguredException;
use App\Services\Payments\PaymentGatewayResolver;
use App\Services\Payments\PaymentGatewayStatusInquiry;
use App\Services\Payments\PaymentStatusInquiryService;
use App\Services\Payments\PaymentTransactionEventType;
use App\Services\Payments\PaymentTransactionSnapshotView;
use App\Services\Payments\PaymentUserTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentTransactionController extends Controller
{
    public function __construct(
        private readonly PaymentAdminTransactionQuery $query,
        private readonly PaymentGatewayResolver $gateways,
        private readonly PaymentStatusInquiryService $inquiry,
        private readonly PaymentAdminTimeline $adminTimeline,
        private readonly PaymentUserTimeline $userTimeline,
    ) {}

    public function index(FilterPaymentTransactionsRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.e-payments.transactions.index', [
            'transactions' => $this->query->paginate($filters),
            'filters' => $filters,
            'types' => PaymentType::query()->orderBy('name')->get(),
        ]);
    }

    public function show(PaymentTransaction $paymentTransaction): View
    {
        $paymentTransaction->load(['user', 'events', 'confirmationDeliveries']);

        return view('admin.e-payments.transactions.show', [
            'transaction' => $paymentTransaction,
            'snapshot' => PaymentTransactionSnapshotView::from($paymentTransaction),
            'timeline' => $this->adminTimeline->forTransaction($paymentTransaction),
            'successfulAtLabel' => $this->userTimeline->successfulAtLabel($paymentTransaction),
            'canInquire' => $this->canInquire($paymentTransaction),
            'providerLabel' => $this->providerLabel($paymentTransaction),
        ]);
    }

    public function checkStatus(Request $request, PaymentTransaction $paymentTransaction): RedirectResponse
    {
        unset($request);

        if ($paymentTransaction->status->isTerminal()) {
            return redirect()
                ->route('admin.e-payments.transactions.show', $paymentTransaction)
                ->with('error', 'Transakcija već ima konačan status. Provjera se ne pokreće.');
        }

        try {
            $gateway = $this->gateways->forTransaction($paymentTransaction);
        } catch (PaymentGatewayNotConfiguredException|FakePaymentGatewayUnavailableException) {
            return redirect()
                ->route('admin.e-payments.transactions.show', $paymentTransaction)
                ->with('error', 'Provajder ne podržava provjeru statusa.');
        }

        if (! $gateway->capabilities()->statusInquiry || ! $gateway instanceof PaymentGatewayStatusInquiry) {
            return redirect()
                ->route('admin.e-payments.transactions.show', $paymentTransaction)
                ->with('error', 'Provajder ne podržava provjeru statusa.');
        }

        try {
            $this->inquiry->checkStatus($paymentTransaction, $gateway);
        } catch (GatewayVerificationException) {
            return redirect()
                ->route('admin.e-payments.transactions.show', $paymentTransaction)
                ->with('error', 'Provjera statusa nije primijenjena zbog neusklađenosti iznosa ili valute.');
        } catch (GatewayInquiryException) {
            return redirect()
                ->route('admin.e-payments.transactions.show', $paymentTransaction)
                ->with('error', $this->messageForOutcome($this->lastInquiryOutcome($paymentTransaction)));
        }

        $paymentTransaction->refresh();
        $outcome = $this->lastInquiryOutcome($paymentTransaction);

        return redirect()
            ->route('admin.e-payments.transactions.show', $paymentTransaction)
            ->with($this->flashLevel($outcome), $this->messageForOutcome($outcome));
    }

    private function canInquire(PaymentTransaction $transaction): bool
    {
        if ($transaction->status !== PaymentStatus::Processing) {
            return false;
        }

        if (! is_string($transaction->provider) || $transaction->provider === '') {
            return false;
        }

        try {
            $gateway = $this->gateways->forTransaction($transaction);
        } catch (PaymentGatewayNotConfiguredException|FakePaymentGatewayUnavailableException) {
            return false;
        }

        return $gateway->capabilities()->statusInquiry && $gateway instanceof PaymentGatewayStatusInquiry;
    }

    private function providerLabel(PaymentTransaction $transaction): string
    {
        $provider = $transaction->provider;

        return is_string($provider) && $provider !== '' ? $provider : 'Nepoznato';
    }

    private function lastInquiryOutcome(PaymentTransaction $transaction): string
    {
        $event = $transaction->events()
            ->where('event_type', PaymentTransactionEventType::GATEWAY_INQUIRY)
            ->orderByDesc('id')
            ->first();

        $payload = is_array($event?->payload) ? $event->payload : [];
        $outcome = $payload['inquiry_outcome'] ?? '';

        return is_string($outcome) && $outcome !== '' ? $outcome : 'technical_error';
    }

    private function messageForOutcome(string $outcome): string
    {
        return match ($outcome) {
            'successful' => 'Status je potvrđen kao Uspješna.',
            'failed' => 'Status je potvrđen kao Neuspješna.',
            'cancelled' => 'Status je potvrđen kao Otkazana.',
            'processing' => 'Provajder i dalje vodi transakciju kao U obradi.',
            'unknown' => 'Provajder nije vratio konačan status.',
            'not_found' => 'Transakcija nije pronađena kod provajdera.',
            'unsupported' => 'Provajder ne podržava provjeru statusa.',
            default => 'Provjera statusa trenutno nije dostupna.',
        };
    }

    private function flashLevel(string $outcome): string
    {
        return in_array($outcome, ['successful', 'failed', 'cancelled', 'processing'], true)
            ? 'success'
            : 'error';
    }
}
