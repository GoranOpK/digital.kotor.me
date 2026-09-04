<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\PaymentTransaction;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentGatewayResolver;
use App\Services\Payments\PaymentResultProcessor;
use App\Services\Payments\PaymentResultRejectedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FakePaymentGatewayController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayResolver $resolver,
        private readonly FakePaymentGateway $gateway,
        private readonly PaymentResultProcessor $processor,
    ) {}

    public function show(Request $request, PaymentTransaction $paymentTransaction): View|RedirectResponse
    {
        $this->guardSimulator($request, $paymentTransaction);

        if ($paymentTransaction->status->isTerminal()) {
            return redirect()->route('payments.result', $paymentTransaction);
        }

        return view('payments.fake-gateway', [
            'transaction' => $paymentTransaction,
            'successUrl' => URL::signedRoute('payments.fake.simulate', [
                'payment_transaction' => $paymentTransaction->uuid,
                'outcome' => PaymentStatus::Successful->value,
            ]),
            'failedUrl' => URL::signedRoute('payments.fake.simulate', [
                'payment_transaction' => $paymentTransaction->uuid,
                'outcome' => PaymentStatus::Failed->value,
            ]),
            'cancelledUrl' => URL::signedRoute('payments.fake.simulate', [
                'payment_transaction' => $paymentTransaction->uuid,
                'outcome' => PaymentStatus::Cancelled->value,
            ]),
        ]);
    }

    public function simulate(Request $request, PaymentTransaction $paymentTransaction, string $outcome): RedirectResponse
    {
        $this->guardSimulator($request, $paymentTransaction);

        $status = PaymentStatus::tryFrom($outcome);
        if ($status === null || ! $status->isTerminal()) {
            abort(404);
        }

        try {
            $this->processor->apply(
                $paymentTransaction,
                $this->gateway->verify($paymentTransaction, $status)
            );
        } catch (PaymentResultRejectedException) {
            abort(403);
        }

        return redirect()->route('payments.result', $paymentTransaction);
    }

    private function guardSimulator(Request $request, PaymentTransaction $paymentTransaction): void
    {
        if (! $this->resolver->fakeIsAllowed()) {
            throw new NotFoundHttpException;
        }

        abort_unless((int) $paymentTransaction->user_id === (int) $request->user()?->id, 404);
    }
}
