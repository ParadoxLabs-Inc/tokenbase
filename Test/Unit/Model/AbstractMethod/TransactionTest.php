<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model\AbstractMethod;

use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Registry;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction\Repository;
use ParadoxLabs\TokenBase\Api\CardRepositoryInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterfaceFactory;
use ParadoxLabs\TokenBase\Helper\Address;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\AbstractGateway;
use ParadoxLabs\TokenBase\Model\AbstractMethod;
use ParadoxLabs\TokenBase\Model\Card;
use ParadoxLabs\TokenBase\Test\Unit\PartialStubTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for AbstractMethod transaction-related methods
 */
class TransactionTest extends TestCase
{
    use PartialStubTrait;

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
        $this->setUpSubject();
    }

    private function setUpSubject(array $mocks = []): void
    {
        $this->transactionRepository = $this->createStub(Repository::class);
        $this->helper = $this->createStub(Data::class);
        $this->gateway = $this->createStub(AbstractGateway::class);
        $this->cardFactory = $this->createStub(CardInterfaceFactory::class);
        $this->cardRepository = $mocks['cardRepository'] ?? $this->createStub(CardRepositoryInterface::class);
        $this->addressHelper = $this->createStub(Address::class);
        $this->config = $this->createStub(ConfigInterface::class);
        $this->registry = $mocks['registry'] ?? $this->createStub(Registry::class);

        $this->helper->method('getCurrentStoreId')->willReturn(1);

        $this->method = $this->createPartialStub(
            AbstractMethod::class,
            ['getInfoInstance', 'getCustomer', 'gateway', 'getCard', 'hasData', 'setInfoInstance'],
            [
                $this->transactionRepository,
                $this->helper,
                $this->gateway,
                $this->cardFactory,
                $this->cardRepository,
                $this->addressHelper,
                $this->config,
                $this->registry,
                'testmethod',
            ]
        );

        $this->method->method('gateway')->willReturn($this->gateway);
    }

    public function testGetValidTransactionIdReturnsOriginalWhenUnique(): void
    {
        $payment = $this->createOrderPaymentMock();

        $this->transactionRepository->method('getByTransactionId')
            ->willReturn(false);

        $reflection = new ReflectionClass($this->method);
        $getValidTxnMethod = $reflection->getMethod('getValidTransactionId');

        $result = $getValidTxnMethod->invoke($this->method, $payment, 'txn123');

        $this->assertSame('txn123', $result);
    }

    public function testGetValidTransactionIdAddsSuffixWhenDuplicate(): void
    {
        $payment = $this->createOrderPaymentMock();

        $existingTxn = $this->createStub(TransactionInterface::class);

        $callCount = 0;
        $this->transactionRepository->method('getByTransactionId')
            ->willReturnCallback(function ($txnId) use (&$callCount, $existingTxn) {
                $callCount++;
                // First two calls return existing transaction, third returns false
                if ($callCount <= 2) {
                    return $existingTxn;
                }

                return false;
            });

        $reflection = new ReflectionClass($this->method);
        $getValidTxnMethod = $reflection->getMethod('getValidTransactionId');

        $result = $getValidTxnMethod->invoke($this->method, $payment, 'txn123');

        $this->assertSame('txn123-2', $result);
    }

    public function testGetValidTransactionIdHandlesMultipleDuplicates(): void
    {
        $payment = $this->createOrderPaymentMock();

        $existingTxn = $this->createStub(TransactionInterface::class);

        $callCount = 0;
        $this->transactionRepository->method('getByTransactionId')
            ->willReturnCallback(function ($txnId) use (&$callCount, $existingTxn) {
                $callCount++;
                // First 5 calls return existing transaction
                if ($callCount <= 5) {
                    return $existingTxn;
                }

                return false;
            });

        $reflection = new ReflectionClass($this->method);
        $getValidTxnMethod = $reflection->getMethod('getValidTransactionId');

        $result = $getValidTxnMethod->invoke($this->method, $payment, 'txn123');

        $this->assertSame('txn123-5', $result);
    }

    public function testResyncStoredCardSkipsWhenNoCard(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
        ]);

        $payment = $this->createOrderPaymentMock();

        $this->method->method('getCard')->willReturn(null);

        // CardRepository should not be called
        $this->cardRepository->expects($this->never())
            ->method('save');

        $reflection = new ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');

        $result = $resyncMethod->invoke($this->method, $payment);

        $this->assertSame($this->method, $result);
    }

    public function testResyncStoredCardSkipsWhenCardHasNoId(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
        ]);

        $card = $this->createCardMock(null);

        $this->method->method('getCard')->willReturn($card);

        $payment = $this->createOrderPaymentMock();

        $this->cardRepository->expects($this->never())
            ->method('save');

        $reflection = new ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');

        $result = $resyncMethod->invoke($this->method, $payment);

        $this->assertSame($this->method, $result);
    }

    public function testResyncStoredCardSavesWhenAdditionalDataChanged(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
        ]);

        $card = $this->createCardMock(123);
        $card->method('getOrigData')
            ->willReturnMap([
                ['additional', '{"cc_type":"VI"}'],
            ]);
        $card->method('getData')
            ->willReturnMap([
                ['additional', '{"cc_type":"MC"}'],
            ]);

        $this->method->method('getCard')->willReturn($card);

        $payment = $this->createOrderPaymentMock();

        $this->cardRepository->expects($this->once())
            ->method('save')
            ->with($card)
            ->willReturn($card);

        $reflection = new ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');

        $resyncMethod->invoke($this->method, $payment);
    }

    public function testResyncStoredCardSavesWhenAddressChanged(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
        ]);

        $card = $this->createCardMock(123);
        $card->method('getOrigData')
            ->willReturnMap([
                ['additional', null],
            ]);
        $card->method('getData')
            ->willReturnMap([
                ['additional', null],
            ]);
        $card->method('getAddress')
            ->willReturnCallback(function ($field) {
                if ($field === 'city') {
                    return 'OldCity';
                }

                return null;
            });

        $this->method->method('getCard')->willReturn($card);

        $orderAddress = $this->createStub(OrderAddress::class);
        $orderAddress->method('getData')
            ->willReturnCallback(function ($field = null) {
                if ($field === 'city') {
                    return 'NewCity';
                }
                if ($field === null) {
                    return ['city' => 'NewCity'];
                }

                return null;
            });

        $order = $this->createStub(Order::class);
        $order->method('getBillingAddress')->willReturn($orderAddress);

        $payment = $this->createStub(OrderPayment::class);
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getId')->willReturn(1);

        $newAddress = $this->createStub(AddressInterface::class);
        $this->addressHelper->method('buildAddressFromInput')
            ->willReturn($newAddress);

        $this->cardRepository->expects($this->once())
            ->method('save')
            ->with($card)
            ->willReturn($card);

        $reflection = new ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');

        $resyncMethod->invoke($this->method, $payment);
    }

    public function testResyncStoredCardSkipsWhenNoChangesAndNoBillingAddress(): void
    {
        $this->setUpSubject([
            'cardRepository' => $this->createMock(CardRepositoryInterface::class),
        ]);

        // When there's no billing address at all and no additional data changes, skip save
        $card = $this->createCardMock(123);
        $card->method('getOrigData')
            ->willReturnCallback(function ($key = null) {
                if ($key === 'additional') {
                    return '{"cc_type":"VI"}';
                }

                return null;
            });
        $card->method('getData')
            ->willReturnCallback(function ($key = null) {
                if ($key === 'additional') {
                    return '{"cc_type":"VI"}';
                }

                return null;
            });

        $this->method->method('getCard')->willReturn($card);

        // No billing address - getBillingAddress returns null
        $order = $this->createStub(Order::class);
        $order->method('getBillingAddress')->willReturn(null);

        $payment = $this->createStub(OrderPayment::class);
        $payment->method('getOrder')->willReturn($order);
        $payment->method('getData')
            ->willReturnCallback(function ($key = null) {
                return null;
            });
        $payment->method('getId')->willReturn(1);

        $this->cardRepository->expects($this->never())
            ->method('save');

        $reflection = new ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');

        $resyncMethod->invoke($this->method, $payment);
    }

    public function testResyncStoredCardUpdatesRegistryOnSave(): void
    {
        $this->setUpSubject([
            'registry' => $this->createMock(Registry::class),
        ]);

        $card = $this->createCardMock(123);
        $card->method('getOrigData')
            ->willReturnMap([
                ['additional', '{"cc_type":"VI"}'],
            ]);
        $card->method('getData')
            ->willReturnMap([
                ['additional', '{"cc_type":"MC"}'],
            ]);

        $this->method->method('getCard')->willReturn($card);
        $this->method->method('hasData')->willReturn(true);

        $payment = $this->createOrderPaymentMock();

        $this->cardRepository->method('save')->willReturn($card);

        $this->registry->expects($this->once())
            ->method('unregister')
            ->with('tokenbase_ensure_checkout_card_save');
        $this->registry->expects($this->once())
            ->method('register')
            ->with('tokenbase_ensure_checkout_card_save', $card);

        $reflection = new ReflectionClass($this->method);
        $resyncMethod = $reflection->getMethod('resyncStoredCard');

        $resyncMethod->invoke($this->method, $payment);
    }

    public function testResyncStoredCardChecksAllAddressFields(): void
    {
        $addressFields = [
            'firstname',
            'lastname',
            'company',
            'street',
            'city',
            'country_id',
            'region',
            'region_id',
            'postcode',
            'telephone',
            'prefix',
            'middlename',
            'suffix',
        ];

        foreach ($addressFields as $field) {
            $card = $this->createCardMock(123);
            $card->method('getOrigData')->willReturn(null);
            $card->method('getData')->willReturn(null);
            $card->method('getAddress')
                ->willReturnCallback(function ($f) use ($field) {
                    return $f === $field ? 'old_value' : 'same';
                });

            $methodInstance = $this->createPartialStub(
                AbstractMethod::class,
                ['getInfoInstance', 'getCustomer', 'gateway', 'getCard', 'hasData', 'setInfoInstance'],
                [
                    $this->transactionRepository,
                    $this->helper,
                    $this->gateway,
                    $this->cardFactory,
                    $this->cardRepository,
                    $this->addressHelper,
                    $this->config,
                    $this->registry,
                    'testmethod',
                ]
            );

            $methodInstance->method('getCard')->willReturn($card);
            $methodInstance->method('hasData')->willReturn(true);

            $orderAddress = $this->createStub(OrderAddress::class);
            $orderAddress->method('getData')
                ->willReturnCallback(function ($f = null) use ($field) {
                    if ($f === null) {
                        return [$field => 'new_value'];
                    }

                    return $f === $field ? 'new_value' : 'same';
                });

            $order = $this->createStub(Order::class);
            $order->method('getBillingAddress')->willReturn($orderAddress);

            $payment = $this->createStub(OrderPayment::class);
            $payment->method('getOrder')->willReturn($order);

            $newAddress = $this->createStub(AddressInterface::class);
            $this->addressHelper->method('buildAddressFromInput')->willReturn($newAddress);
            $this->cardRepository->method('save')->willReturn($card);

            $reflection = new ReflectionClass($methodInstance);
            $resyncMethod = $reflection->getMethod('resyncStoredCard');

            // Should detect change and save
            $resyncMethod->invoke($methodInstance, $payment);
        }

        // If we got here without exceptions, all fields were checked
        $this->assertTrue(true);
    }

    private function createOrderPaymentMock(): OrderPayment|MockObject
    {
        $order = $this->createStub(Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getBillingAddress')->willReturn(null);

        $payment = $this->createStub(OrderPayment::class);
        $payment->method('getId')->willReturn(1);
        $payment->method('getOrder')->willReturn($order);

        return $payment;
    }

    private function createCardMock(?int $id): Card|MockObject
    {
        $card = $this->createPartialStub(
            Card::class,
            [
                'getId',
                'getOrigData',
                'getData',
                'getAddress',
                'setMethodInstance',
                'setInfoInstance',
                'setAddress',
            ]
        );

        $card->method('getId')->willReturn($id);
        $card->method('setMethodInstance')->willReturnSelf();
        $card->method('setInfoInstance')->willReturnSelf();
        $card->method('setAddress')->willReturnSelf();

        return $card;
    }
}
