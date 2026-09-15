<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Observer\PaymentMethodAssignDataObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the payment assignData observer's 'save this card' and re-imported-data handling
 */
class PaymentMethodAssignDataObserverTest extends TestCase
{
    private PaymentMethodAssignDataObserver $observer;
    private Data|MockObject $helper;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);

        $this->observer = new PaymentMethodAssignDataObserver(
            $this->helper,
            $this->createMock(CardRepositoryInterface::class)
        );
    }

    /**
     * With save forced by config, a submitted save=0 must not deactivate the card.
     *
     * @return void
     */
    public function testForcedSaveOverridesSubmittedZero(): void
    {
        $payment = $this->createQuotePayment(5);

        $this->observer->execute($this->createObserver($payment, ['save' => 0], '0'));

        $this->assertSame(1, $payment->getAdditionalInformation('save'));
    }

    /**
     * With save optional, the customer's choice must be respected.
     *
     * @return void
     */
    public function testOptionalSaveRespectsSubmittedZero(): void
    {
        $payment = $this->createQuotePayment(5);

        $this->observer->execute($this->createObserver($payment, ['save' => 0], '1'));

        $this->assertSame(0, $payment->getAdditionalInformation('save'));
    }

    /**
     * A submitted save=1 is stored as-is either way.
     *
     * @return void
     */
    public function testSubmittedSaveIsKept(): void
    {
        $payment = $this->createQuotePayment(5);

        $this->observer->execute($this->createObserver($payment, ['save' => 1], '0'));

        $this->assertSame(1, $payment->getAdditionalInformation('save'));
    }

    /**
     * Guests cannot vault, so forced save must not apply to them.
     *
     * @return void
     */
    public function testForcedSaveDoesNotApplyToGuests(): void
    {
        $payment = $this->createQuotePayment(null);

        $this->observer->execute($this->createObserver($payment, ['save' => 0], '0'));

        $this->assertSame(0, $payment->getAdditionalInformation('save'));
    }

    /**
     * Forced save also applies when assigning to an order payment.
     *
     * @return void
     */
    public function testForcedSaveAppliesToOrderPayment(): void
    {
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCustomerId'])
            ->getMock();
        $order->method('getCustomerId')->willReturn(5);

        $payment = $this->getMockBuilder(OrderPayment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrder', 'getExtensionAttributes'])
            ->getMock();
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getExtensionAttributes')->willReturn(null);

        $this->observer->execute($this->createObserver($payment, ['save' => 0], '0'));

        $this->assertSame(1, $payment->getAdditionalInformation('save'));
    }

    /**
     * QuoteManagement::placeOrder() re-imports the payment's own data (GraphQL placeOrder). With no new card
     * input on that re-import, a new card's last4 and bin must survive it.
     *
     * @return void
     */
    public function testReimportedDataKeepsNewCardLast4AndBin(): void
    {
        $payment = $this->createQuotePayment(5);
        $payment->setData('cc_last_4', '1111');
        $payment->setData('cc_type', 'VI');
        $payment->setAdditionalInformation('acceptjs_key', 'COMMON.ACCEPT.INAPP.PAYMENT');
        $payment->setAdditionalInformation('acceptjs_value', 'nonce');
        $payment->setAdditionalInformation('cc_bin', '411111');

        $reimport = $payment->getData();
        unset($reimport['quote']);

        $this->observer->execute($this->createObserver($payment, $reimport, '1', ['can_store_bin' => '1']));

        $this->assertSame('1111', $payment->getData('cc_last_4'));
        $this->assertSame('VI', $payment->getData('cc_type'));
        $this->assertSame('411111', $payment->getAdditionalInformation('cc_bin'));
        $this->assertSame('nonce', $payment->getAdditionalInformation('acceptjs_value'));
        $this->assertNull($payment->getData('tokenbase_id'));
    }

    /**
     * A genuine assignment with neither card number nor card ID still clears a stale new-card last4.
     *
     * @return void
     */
    public function testEmptyAssignmentClearsNewCardLast4(): void
    {
        $payment = $this->createQuotePayment(5);
        $payment->setData('cc_last_4', '1111');
        $payment->setAdditionalInformation('cc_bin', '411111');

        $this->observer->execute($this->createObserver($payment, [], '1', ['can_store_bin' => '1']));

        $this->assertNull($payment->getData('cc_last_4'));
        $this->assertSame('', $payment->getAdditionalInformation('cc_bin'));
    }

    /**
     * Build a quote payment owned by the given customer (null for a guest).
     *
     * @param int|null $customerId
     * @return QuotePayment|MockObject
     */
    private function createQuotePayment(?int $customerId)
    {
        // Left unstubbed: getCustomerId() is a DataObject magic method, which PHPUnit cannot stub.
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $quote->setData('customer_id', $customerId);

        $payment = $this->getMockBuilder(QuotePayment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuote', 'getExtensionAttributes'])
            ->getMock();
        $payment->method('getQuote')->willReturn($quote);
        $payment->method('getExtensionAttributes')->willReturn(null);

        return $payment;
    }

    /**
     * Build the assignData event with the given submitted data and allow_unsaved config.
     *
     * @param object $payment
     * @param array $data
     * @param string $allowUnsaved
     * @param array $config
     * @return Observer
     */
    private function createObserver($payment, array $data, string $allowUnsaved, array $config = []): Observer
    {
        $config['allow_unsaved'] = $allowUnsaved;

        $method = $this->createMock(MethodInterface::class);
        $method->method('getConfigData')
            ->willReturnCallback(
                static function ($field) use ($config) {
                    return $config[$field] ?? null;
                }
            );

        return new Observer(
            [
                'method' => $method,
                'payment_model' => $payment,
                'data' => new DataObject($data),
            ]
        );
    }
}
