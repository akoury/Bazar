<?php

namespace Tests\Unit\Billing;

use Tests\TestCase;
use App\Exceptions\PaymentFailedException;

abstract class PaymentGatewayContractTest extends TestCase
{
    abstract protected function getPaymentGateway();

    abstract protected function getValidTestToken();

    abstract protected function newCharges($callback);

    const TEST_CARD_NUMBER = '4242424242424242';

    /** @test */
    public function charges_with_a_valid_payment_token_are_successful()
    {
        $paymentGateway = $this->getPaymentGateway();

        $newCharges = $this->newCharges(function () use ($paymentGateway) {
            $paymentGateway->charge(2500, $this->getValidTestToken());
        });

        $this->assertCount(1, $newCharges);
        $this->assertEquals(2500, $newCharges->map->amount()->sum());
    }

    /** @test */
    public function can_get_details_about_a_successful_charge()
    {
        $paymentGateway = $this->getPaymentGateway();

        $charge = $paymentGateway->charge(2500, $this->getValidTestToken());

        $this->assertEquals(substr(static::TEST_CARD_NUMBER, -4), $charge->cardLastFour());

        $this->assertEquals(2500, $charge->amount());
    }

    /** @test */
    public function charges_with_an_invalid_payment_token_fail()
    {
        $paymentGateway = $this->getPaymentGateway();

        $newCharges = $this->newCharges(function () use ($paymentGateway) {
            try {
                $paymentGateway->charge(2500, 'invalid-payment-token');
            } catch (PaymentFailedException $e) {
                return;
            }

            $this->fail('Charging with an invalid payment token did not throw a PaymentFailedException.');
        });

        $this->assertCount(0, $newCharges);
    }

    /** @test */
    public function can_fetch_charges_created_during_a_callback()
    {
        $paymentGateway = $this->getPaymentGateway();
        $paymentGateway->charge(2000, $this->getValidTestToken());
        $paymentGateway->charge(3000, $this->getValidTestToken());

        $newCharges = $this->newCharges(function () use ($paymentGateway) {
            $paymentGateway->charge(4000, $this->getValidTestToken());
            $paymentGateway->charge(5000, $this->getValidTestToken());
        });

        $this->assertCount(2, $newCharges);
        $this->assertEquals([5000, 4000], $newCharges->map->amount()->all());
    }
}
