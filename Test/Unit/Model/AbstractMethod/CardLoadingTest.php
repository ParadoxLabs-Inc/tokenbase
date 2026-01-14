<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model\AbstractMethod;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory;
use ParadoxLabs\TokenBase\Helper\Address;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\AbstractGateway;
use ParadoxLabs\TokenBase\Model\AbstractMethod;
use ParadoxLabs\TokenBase\Model\Card;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AbstractMethod card loading methods
 */
class CardLoadingTest extends TestCase
{
    private AbstractMethod|MockObject $method;
    private Repository|MockObject $transactionRepository;
    private Data|MockObject $helper;
    private AbstractGateway|MockObject $gateway;
    private CardInterfaceFactory|MockObject $cardFactory;
    private CardRepositoryInterface|MockObject $cardRepository;
    private Address|MockObject $addressHelper;
    private ConfigInterface|MockObject $config;
    private Registry|MockObject $registry;

    protected function setUp(): void
    {
        $this->transactionRepository = $this->createMock(Repository::class);
        $this->helper = $this->createMock(Data::class);
        $this->gateway = $this->createMock(AbstractGateway::class);
        $this->cardFactory = $this->createMock(CardInterfaceFactory::class);
        $this->cardRepository = $this->createMock(CardRepositoryInterface::class);
        $this->addressHelper = $this->createMock(Address::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->registry = $this->createMock(Registry::class);

        $this->helper->method('getCurrentStoreId')->willReturn(1);

        // Create partial mock to test protected methods
        $this->method = $this->getMockBuilder(AbstractMethod::class)
            ->setConstructorArgs([
                $this->transactionRepository,
                $this->helper,
                $this->gateway,
                $this->cardFactory,
                $this->cardRepository,
                $this->addressHelper,
                $this->config,
                $this->registry,
                'testmethod',
            ])
            ->onlyMethods(['getInfoInstance', 'getCustomer', 'gateway'])
            ->getMock();

        $this->method->method('gateway')->willReturn($this->gateway);
    }

    public function testLoadAndSetCardLoadsValidCard(): void
    {
        $card = $this->createCardMock(123, 456, 'testmethod', 'hash123');

        $this->cardRepository->method('getById')
            ->with(123)
            ->willReturn($card);

        $payment = $this->createOrderPaymentMock(456);
        $this->method->method('getInfoInstance')->willReturn($payment);

        $result = $this->method->loadAndSetCard(123);

        $this->assertSame($card, $result);
    }

    public function testLoadAndSetCardLoadsByHash(): void
    {
        $card = $this->createCardMock(123, 456, 'testmethod', 'hash123');

        $this->cardRepository->method('getById')
            ->with('hash123')
            ->willReturn($card);

        $payment = $this->createOrderPaymentMock(456);
        $this->method->method('getInfoInstance')->willReturn($payment);

        $result = $this->method->loadAndSetCard('hash123', true);

        $this->assertSame($card, $result);
    }

    public function testLoadAndSetCardThrowsForWrongCustomer(): void
    {
        $card = $this->createCardMock(123, 789, 'testmethod', 'hash123');

        $this->cardRepository->method('getById')
            ->with(123)
            ->willReturn($card);

        $payment = $this->createOrderPaymentMock(456);
        $this->method->method('getInfoInstance')->willReturn($payment);

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Unable to load payment data');

        $this->method->loadAndSetCard(123);
    }

    public function testLoadAndSetCardThrowsForWrongMethod(): void
    {
        $card = $this->createCardMock(123, 456, 'othermethod', 'hash123');

        $this->cardRepository->method('getById')
            ->with(123)
            ->willReturn($card);

        $payment = $this->createOrderPaymentMock(456);
        $this->method->method('getInfoInstance')->willReturn($payment);

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Unable to load payment data');

        $this->method->loadAndSetCard(123);
    }

    public function testLoadAndSetCardThrowsForInvalidHash(): void
    {
        $card = $this->createCardMock(123, 456, 'testmethod', 'differenthash');

        $this->cardRepository->method('getById')
            ->with('hash123')
            ->willReturn($card);

        $payment = $this->createOrderPaymentMock(456);
        $this->method->method('getInfoInstance')->willReturn($payment);

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Unable to load payment data');

        $this->method->loadAndSetCard('hash123', true);
    }

    public function testLoadAndSetCardThrowsWhenCardNotFound(): void
    {
        $this->cardRepository->method('getById')
            ->willThrowException(new NoSuchEntityException(__('Card not found')));

        $payment = $this->createOrderPaymentMock(456);
        $this->method->method('getInfoInstance')->willReturn($payment);

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Unable to load payment data');

        $this->method->loadAndSetCard(999);
    }

    public function testLoadAndSetCardAllowsGuestCard(): void
    {
        // Card with empty customer_id
        $card = $this->createCardMock(123, null, 'testmethod', 'hash123');

        $this->cardRepository->method('getById')
            ->with(123)
            ->willReturn($card);

        $payment = $this->createOrderPaymentMock(456);
        $this->method->method('getInfoInstance')->willReturn($payment);

        $result = $this->method->loadAndSetCard(123);

        $this->assertSame($card, $result);
    }

    public function testLoadOrCreateCardReturnsExistingCard(): void
    {
        $card = $this->createCardMock(123, 456, 'testmethod', 'hash123');

        // Set card via reflection
        $reflection = new \ReflectionClass($this->method);
        $property = $reflection->getProperty('card');
        $property->setAccessible(true);
        $property->setValue($this->method, $card);

        $payment = $this->createOrderPaymentMock(456);
        $this->method->method('getInfoInstance')->willReturn($payment);

        // Access protected method
        $loadOrCreateMethod = $reflection->getMethod('loadOrCreateCard');
        $loadOrCreateMethod->setAccessible(true);

        $result = $loadOrCreateMethod->invoke($this->method, $payment);

        $this->assertSame($card, $result);
    }

    public function testLoadOrCreateCardLoadsFromTokenbaseCard(): void
    {
        $card = $this->createCardMock(123, 456, 'testmethod', 'hash123');

        $payment = $this->createOrderPaymentMock(456);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_card', null, $card],
                ['tokenbase_id', null, null],
            ]);
        $payment->method('hasData')
            ->willReturnMap([
                ['tokenbase_id', false],
            ]);
        $payment->method('hasAdditionalInformation')
            ->willReturn(false);

        $this->method->method('getInfoInstance')->willReturn($payment);

        $reflection = new \ReflectionClass($this->method);
        $loadOrCreateMethod = $reflection->getMethod('loadOrCreateCard');
        $loadOrCreateMethod->setAccessible(true);

        $result = $loadOrCreateMethod->invoke($this->method, $payment);

        $this->assertSame($card, $result);
    }

    public function testLoadOrCreateCardLoadsFromTokenbaseId(): void
    {
        $card = $this->createCardMock(123, 456, 'testmethod', 'hash123');

        $payment = $this->createOrderPaymentMock(456);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_card', null, null],
                ['tokenbase_id', null, 123],
            ]);
        $payment->method('hasData')
            ->willReturnMap([
                ['tokenbase_id', true],
            ]);

        $this->cardRepository->method('getById')
            ->with(123)
            ->willReturn($card);

        $this->method->method('getInfoInstance')->willReturn($payment);

        $reflection = new \ReflectionClass($this->method);
        $loadOrCreateMethod = $reflection->getMethod('loadOrCreateCard');
        $loadOrCreateMethod->setAccessible(true);

        $result = $loadOrCreateMethod->invoke($this->method, $payment);

        $this->assertSame($card, $result);
    }

    public function testPaymentContainsCardReturnsTrueWhenAllFieldsPresent(): void
    {
        $payment = $this->createMock(OrderPayment::class);
        $payment->method('hasData')
            ->willReturnMap([
                ['cc_number', true],
                ['cc_exp_year', true],
                ['cc_exp_month', true],
            ]);

        $reflection = new \ReflectionClass($this->method);
        $method = $reflection->getMethod('paymentContainsCard');
        $method->setAccessible(true);

        $result = $method->invoke($this->method, $payment);

        $this->assertTrue($result);
    }

    public function testPaymentContainsCardReturnsFalseWhenMissingNumber(): void
    {
        $payment = $this->createMock(OrderPayment::class);
        $payment->method('hasData')
            ->willReturnMap([
                ['cc_number', false],
                ['cc_exp_year', true],
                ['cc_exp_month', true],
            ]);

        $reflection = new \ReflectionClass($this->method);
        $method = $reflection->getMethod('paymentContainsCard');
        $method->setAccessible(true);

        $result = $method->invoke($this->method, $payment);

        $this->assertFalse($result);
    }

    public function testPaymentContainsCardReturnsFalseWhenMissingExpYear(): void
    {
        $payment = $this->createMock(OrderPayment::class);
        $payment->method('hasData')
            ->willReturnMap([
                ['cc_number', true],
                ['cc_exp_year', false],
                ['cc_exp_month', true],
            ]);

        $reflection = new \ReflectionClass($this->method);
        $method = $reflection->getMethod('paymentContainsCard');
        $method->setAccessible(true);

        $result = $method->invoke($this->method, $payment);

        $this->assertFalse($result);
    }

    public function testPaymentContainsCardReturnsFalseWhenMissingExpMonth(): void
    {
        $payment = $this->createMock(OrderPayment::class);
        $payment->method('hasData')
            ->willReturnMap([
                ['cc_number', true],
                ['cc_exp_year', true],
                ['cc_exp_month', false],
            ]);

        $reflection = new \ReflectionClass($this->method);
        $method = $reflection->getMethod('paymentContainsCard');
        $method->setAccessible(true);

        $result = $method->invoke($this->method, $payment);

        $this->assertFalse($result);
    }

    public function testLoadOrCreateCardThrowsWhenNoCardData(): void
    {
        $payment = $this->createMock(OrderPayment::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_card', null, null],
                ['tokenbase_id', null, null],
            ]);
        $payment->method('hasData')
            ->willReturnMap([
                ['tokenbase_id', false],
                ['cc_number', false],
                ['cc_exp_year', false],
                ['cc_exp_month', false],
            ]);
        $payment->method('hasAdditionalInformation')
            ->willReturn(false);
        $payment->method('getId')->willReturn(1);

        $this->method->method('getInfoInstance')->willReturn($payment);

        $reflection = new \ReflectionClass($this->method);
        $loadOrCreateMethod = $reflection->getMethod('loadOrCreateCard');
        $loadOrCreateMethod->setAccessible(true);

        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Invalid payment data provided');

        $loadOrCreateMethod->invoke($this->method, $payment);
    }

    private function createCardMock(
        int $id,
        ?int $customerId,
        string $method,
        string $hash,
    ): Card|MockObject {
        $card = $this->getMockBuilder(Card::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getId',
                'getCustomerId',
                'getMethod',
                'getHash',
                'setMethodInstance',
                'getTypeInstance',
                'setInfoInstance',
                'getAdditional',
                'getType',
            ])
            ->getMock();

        $card->method('getId')->willReturn($id);
        $card->method('getCustomerId')->willReturn($customerId);
        $card->method('getMethod')->willReturn($method);
        $card->method('getHash')->willReturn($hash);
        $card->method('setMethodInstance')->willReturnSelf();
        $card->method('getTypeInstance')->willReturn($card);
        $card->method('setInfoInstance')->willReturnSelf();
        $card->method('getAdditional')->willReturn(null);
        $card->method('getType')->willReturn('VI');

        return $card;
    }

    private function createOrderPaymentMock(int $customerId): OrderPayment|MockObject
    {
        $order = $this->createMock(Order::class);
        $order->method('getCustomerId')->willReturn($customerId);

        $payment = $this->createMock(OrderPayment::class);
        $payment->method('getOrder')->willReturn($order);
        $payment->method('setData')->willReturnSelf();
        $payment->method('setAdditionalInformation')->willReturnSelf();

        return $payment;
    }
}
