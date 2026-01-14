<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context as ModelContext;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\AbstractMethod;
use ParadoxLabs\TokenBase\Model\Card;
use ParadoxLabs\TokenBase\Model\Card\Context as CardContext;
use ParadoxLabs\TokenBase\Model\Method\Factory as MethodFactory;
use ParadoxLabs\TokenBase\Model\ResourceModel\Card as CardResource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Card model
 */
class CardTest extends TestCase
{
    private Card $card;
    private ModelContext|MockObject $context;
    private Registry|MockObject $registry;
    private ExtensionAttributesFactory|MockObject $extensionFactory;
    private AttributeValueFactory|MockObject $customAttributeFactory;
    private CardContext|MockObject $cardContext;
    private Data|MockObject $helper;
    private MethodFactory|MockObject $methodFactory;
    private OrderCollectionFactory|MockObject $orderCollectionFactory;
    private TimezoneInterface|MockObject $dateProcessor;
    private AbstractMethod|MockObject $method;
    private CardResource|MockObject $resource;

    protected function setUp(): void
    {
        // Initialize ObjectManager with mocks to prevent "ObjectManager isn't initialized" errors
        $this->resource = $this->createMock(CardResource::class);
        $this->resource->method('getIdFieldName')->willReturn('id');

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')
            ->willReturnCallback(function ($className) {
                if ($className === CardResource::class) {
                    return $this->resource;
                }

                return $this->createMock($className);
            });
        ObjectManager::setInstance($objectManager);

        $this->context = $this->createMock(ModelContext::class);
        $this->registry = $this->createMock(Registry::class);
        $this->extensionFactory = $this->createMock(ExtensionAttributesFactory::class);
        $this->customAttributeFactory = $this->createMock(AttributeValueFactory::class);
        $this->cardContext = $this->createMock(CardContext::class);
        $this->helper = $this->createMock(Data::class);
        $this->methodFactory = $this->createMock(MethodFactory::class);
        $this->orderCollectionFactory = $this->createMock(OrderCollectionFactory::class);
        $this->dateProcessor = $this->createMock(TimezoneInterface::class);
        $this->method = $this->createMock(AbstractMethod::class);

        $customerFactory = $this->createMock(CustomerInterfaceFactory::class);
        $customerRepository = $this->createMock(CustomerRepositoryInterface::class);

        $this->cardContext->method('getHelper')->willReturn($this->helper);
        $this->cardContext->method('getMethodFactory')->willReturn($this->methodFactory);
        $this->cardContext->method('getOrderCollectionFactory')->willReturn($this->orderCollectionFactory);
        $this->cardContext->method('getDateProcessor')->willReturn($this->dateProcessor);
        $this->cardContext->method('getCustomerFactory')->willReturn($customerFactory);
        $this->cardContext->method('getCustomerRepository')->willReturn($customerRepository);

        $customer = $this->createMock(CustomerInterface::class);
        $customerFactory->method('create')->willReturn($customer);

        $eventManager = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $this->context->method('getEventDispatcher')->willReturn($eventManager);

        $this->card = new Card(
            $this->context,
            $this->registry,
            $this->extensionFactory,
            $this->customAttributeFactory,
            $this->cardContext,
        );
    }

    protected function tearDown(): void
    {
        // Reset ObjectManager to avoid affecting other tests
        $reflection = new \ReflectionClass(ObjectManager::class);
        $property = $reflection->getProperty('_instance');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testHasOwnerReturnsTrueForMatchingCustomer(): void
    {
        $this->card->setData('customer_id', 123);

        $this->assertTrue($this->card->hasOwner(123));
    }

    public function testHasOwnerReturnsFalseForDifferentCustomer(): void
    {
        $this->card->setData('customer_id', 123);

        $this->assertFalse($this->card->hasOwner(456));
    }

    public function testHasOwnerReturnsFalseForGuestCustomer(): void
    {
        $this->card->setData('customer_id', 123);

        $this->assertFalse($this->card->hasOwner(0));
        $this->assertFalse($this->card->hasOwner(-1));
    }

    public function testHasOwnerReturnsFalseForNullCustomerId(): void
    {
        $this->card->setData('customer_id', null);

        $this->assertFalse($this->card->hasOwner(123));
    }

    public function testGetAddressReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->card->getAddress());
    }

    public function testGetAddressDecodesJsonAndReturnsFull(): void
    {
        $addressData = [
            'street' => '123 Main St',
            'city' => 'Anytown',
            'region' => 'CA',
            'postcode' => '12345',
        ];

        $this->card->setData('address', json_encode($addressData));

        $result = $this->card->getAddress();

        $this->assertIsArray($result);
        $this->assertSame('123 Main St', $result['street']);
        $this->assertSame('Anytown', $result['city']);
    }

    public function testGetAddressReturnsSpecificKey(): void
    {
        $addressData = [
            'street' => '123 Main St',
            'city' => 'Anytown',
        ];

        $this->card->setData('address', json_encode($addressData));

        $this->assertSame('Anytown', $this->card->getAddress('city'));
    }

    public function testGetAddressReturnsNullForMissingKey(): void
    {
        $addressData = ['street' => '123 Main St'];

        $this->card->setData('address', json_encode($addressData));

        $this->assertNull($this->card->getAddress('nonexistent'));
    }

    public function testGetAdditionalReturnsNullWhenNotSet(): void
    {
        $this->assertNull($this->card->getAdditional());
    }

    public function testGetAdditionalDecodesJsonAndReturnsFull(): void
    {
        $additionalData = [
            'cc_type' => 'VI',
            'cc_last4' => '1111',
        ];

        $this->card->setData('additional', json_encode($additionalData));

        $result = $this->card->getAdditional();

        $this->assertIsArray($result);
        $this->assertSame('VI', $result['cc_type']);
        $this->assertSame('1111', $result['cc_last4']);
    }

    public function testGetAdditionalReturnsSpecificKey(): void
    {
        $additionalData = [
            'cc_type' => 'VI',
            'cc_last4' => '1111',
        ];

        $this->card->setData('additional', json_encode($additionalData));

        $this->assertSame('1111', $this->card->getAdditional('cc_last4'));
    }

    public function testSetAdditionalKeyValue(): void
    {
        $this->card->setAdditional('cc_type', 'MC');

        $this->assertSame('MC', $this->card->getAdditional('cc_type'));
    }

    public function testSetAdditionalArray(): void
    {
        $this->card->setAdditional([
            'cc_type' => 'AE',
            'cc_last4' => '0005',
        ]);

        $this->assertSame('AE', $this->card->getAdditional('cc_type'));
        $this->assertSame('0005', $this->card->getAdditional('cc_last4'));
    }

    public function testUpdateLastUse(): void
    {
        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturn('2025-01-14 12:00:00');

        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->card->updateLastUse();

        $this->assertSame('2025-01-14 12:00:00', $this->card->getData('last_use'));
    }

    public function testUpdateLastUseReturnsThis(): void
    {
        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')->willReturn('2025-01-14 12:00:00');
        $this->dateProcessor->method('date')->willReturn($dateMock);

        $result = $this->card->updateLastUse();

        $this->assertSame($this->card, $result);
    }

    public function testQueueDeletionSetsInactive(): void
    {
        $this->card->setData('active', 1);

        $this->card->queueDeletion();

        $this->assertSame(0, $this->card->getData('active'));
    }

    public function testQueueDeletionReturnsThis(): void
    {
        $result = $this->card->queueDeletion();

        $this->assertSame($this->card, $result);
    }

    public function testGetHashGeneratesIfEmpty(): void
    {
        $this->card->setData('customer_id', 123);
        $this->card->setData('customer_email', 'test@example.com');
        $this->card->setData('method', 'test_method');

        $hash = $this->card->getHash();

        $this->assertNotEmpty($hash);
        $this->assertSame(40, strlen($hash)); // SHA1 produces 40 char hex string
    }

    public function testGetHashReturnsSameValueOnMultipleCalls(): void
    {
        $this->card->setData('customer_id', 123);

        $hash1 = $this->card->getHash();
        $hash2 = $this->card->getHash();

        $this->assertSame($hash1, $hash2);
    }

    public function testGetHashReturnsExistingIfSet(): void
    {
        $this->card->setData('hash', 'existinghash123');

        $this->assertSame('existinghash123', $this->card->getHash());
    }

    public function testSetActiveNormalizesToInt(): void
    {
        $this->card->setActive(true);
        $this->assertSame(1, $this->card->getData('active'));

        $this->card->setActive(false);
        $this->assertSame(0, $this->card->getData('active'));
    }

    public function testGetPublicHashReturnsHash(): void
    {
        $this->card->setData('hash', 'testhash');

        $this->assertSame('testhash', $this->card->getPublicHash());
    }

    public function testGetterSetterForCustomerId(): void
    {
        $this->card->setCustomerId(456);

        $this->assertSame(456, $this->card->getCustomerId());
    }

    public function testGetterSetterForCustomerEmail(): void
    {
        $this->card->setCustomerEmail('user@test.com');

        $this->assertSame('user@test.com', $this->card->getCustomerEmail());
    }

    public function testGetterSetterForMethod(): void
    {
        $this->card->setMethod('authnetcim');

        $this->assertSame('authnetcim', $this->card->getMethod());
    }

    public function testGetterSetterForProfileId(): void
    {
        $this->card->setProfileId('profile123');

        $this->assertSame('profile123', $this->card->getProfileId());
    }

    public function testGetterSetterForPaymentId(): void
    {
        $this->card->setPaymentId('payment456');

        $this->assertSame('payment456', $this->card->getPaymentId());
    }

    public function testGetterSetterForExpires(): void
    {
        $this->card->setExpires('2030-12-31 23:59:59');

        $this->assertSame('2030-12-31 23:59:59', $this->card->getExpires());
    }

    public function testGetterSetterForCustomerIp(): void
    {
        $this->card->setCustomerIp('192.168.1.1');

        $this->assertSame('192.168.1.1', $this->card->getCustomerIp());
    }

    public function testProtectedAdditionalKeysConstant(): void
    {
        $expected = [
            'acceptjs_key',
            'acceptjs_value',
            'cc_cid',
            'cc_number',
            'token',
        ];

        $this->assertSame($expected, Card::PROTECTED_ADDITIONAL_KEYS);
    }
}
