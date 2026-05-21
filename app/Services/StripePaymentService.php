<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Exception\CardException;
use Stripe\Exception\ApiErrorException;
use Exception;

class StripePaymentService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Charge a specific amount in cents using a Stripe payment method.
     *
     * @param int $amountCents
     * @param string $paymentMethodId
     * @param string $description
     * @return \Stripe\PaymentIntent
     * @throws Exception
     */
    public function charge(int $amountCents, string $paymentMethodId, string $description)
    {
        try {
            return $this->stripe->paymentIntents->create([
                'amount'               => $amountCents,
                'currency'             => 'mxn',
                'payment_method'       => $paymentMethodId,
                'confirm'              => true,
                'automatic_payment_methods' => [
                    'enabled'          => true,
                    'allow_redirects'  => 'never',
                ],
                'description'          => $description,
            ]);
        } catch (CardException $e) {
            throw new Exception('Tu tarjeta fue declinada: ' . $e->getError()->message);
        } catch (ApiErrorException $e) {
            throw new Exception('Error al procesar el pago con Stripe. Por favor intenta de nuevo.');
        }
    }
}
