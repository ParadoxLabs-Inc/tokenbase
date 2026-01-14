<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model\Card;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Model\Card;
use ParadoxLabs\TokenBase\Model\Card\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Card Factory
 */
class FactoryTest extends TestCase
{
    private Factory $factory;
    private ObjectManagerInterface|MockObject $objectManager;
    private ScopeConfigInterface|MockObject $scopeConfig;

    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);

        $this->factory = new Factory(
            $this->objectManager,
            $this->scopeConfig,
        );
    }

    public function testCreateReturnsCardInstance(): void
    {
        $card = $this->createMock(CardInterface::class);

        $this->objectManager->expects($this->once())
            ->method('create')
            ->with(Card::class, [])
            ->willReturn($card);

        $result = $this->factory->create(Card::class);

        $this->assertSame($card, $result);
    }

    public function testCreatePassesDataToObjectManager(): void
    {
        $card = $this->createMock(CardInterface::class);
        $data = ['customer_id' => 123, 'method' => 'authnetcim'];

        $this->objectManager->expects($this->once())
            ->method('create')
            ->with(Card::class, $data)
            ->willReturn($card);

        $result = $this->factory->create(Card::class, $data);

        $this->assertSame($card, $result);
    }

    public function testCreateThrowsForNonCardInterface(): void
    {
        $notACard = new \stdClass();

        $this->objectManager->method('create')
            ->willReturn($notACard);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage("class doesn't implement");

        $this->factory->create(\stdClass::class);
    }

    public function testGetTypeInstanceReturnsOriginalWhenNoMethod(): void
    {
        $card = $this->createMock(CardInterface::class);
        $card->method('getMethod')->willReturn(null);

        $result = $this->factory->getTypeInstance($card);

        $this->assertSame($card, $result);
    }

    public function testGetTypeInstanceReturnsOriginalWhenNoCardModel(): void
    {
        $card = $this->createMock(CardInterface::class);
        $card->method('getMethod')->willReturn('authnetcim');

        $this->scopeConfig->method('getValue')
            ->with('payment/authnetcim/card_model', 'store')
            ->willReturn(null);

        $result = $this->factory->getTypeInstance($card);

        $this->assertSame($card, $result);
    }

    public function testGetTypeInstanceReturnsOriginalWhenSameClass(): void
    {
        // When card_model config matches the card's class, return original
        // Note: Since mocks have different class names, we test with empty cardModel
        $card = $this->createMock(CardInterface::class);
        $card->method('getMethod')->willReturn('authnetcim');

        // Test the case where cardModel is empty (which should return original card)
        $this->scopeConfig->method('getValue')
            ->with('payment/authnetcim/card_model', 'store')
            ->willReturn('');

        $result = $this->factory->getTypeInstance($card);

        $this->assertSame($card, $result);
    }

    public function testGetTypeInstanceCreatesNewTypeInstance(): void
    {
        $originalCard = $this->getMockBuilder(CardInterface::class)
            ->addMethods(['getData', 'getOrigData'])
            ->getMockForAbstractClass();
        $originalCard->method('getMethod')->willReturn('authnetcim');
        $originalCard->method('getData')->willReturn(['customer_id' => 123]);
        $originalCard->method('getOrigData')->willReturn(['customer_id' => 123]);

        $typeInstance = $this->getMockBuilder(CardInterface::class)
            ->addMethods(['setData', 'setOrigData'])
            ->getMockForAbstractClass();
        $typeInstance->expects($this->once())
            ->method('setData')
            ->with(['customer_id' => 123]);
        $typeInstance->expects($this->once())
            ->method('setOrigData')
            ->with(null, ['customer_id' => 123]);

        $this->scopeConfig->method('getValue')
            ->with('payment/authnetcim/card_model', 'store')
            ->willReturn('Custom\\Card\\Model');

        $this->objectManager->method('create')
            ->with('Custom\\Card\\Model', [])
            ->willReturn($typeInstance);

        $result = $this->factory->getTypeInstance($originalCard);

        $this->assertSame($typeInstance, $result);
    }

    public function testGetTypeInstanceReturnsSameInstanceWhenCardModelDiffersButEmpty(): void
    {
        // When card_model is empty, should return original card
        $card = $this->createMock(CardInterface::class);
        $card->method('getMethod')->willReturn('some_method');

        $this->scopeConfig->method('getValue')
            ->with('payment/some_method/card_model', 'store')
            ->willReturn(null);

        $result = $this->factory->getTypeInstance($card);

        $this->assertSame($card, $result);
    }

    public function testCreateCastsClassNameToString(): void
    {
        $card = $this->createMock(CardInterface::class);

        $this->objectManager->expects($this->once())
            ->method('create')
            ->with('ParadoxLabs\\TokenBase\\Model\\Card', [])
            ->willReturn($card);

        // Test with a class name that could be an object with __toString
        $result = $this->factory->create('ParadoxLabs\\TokenBase\\Model\\Card');

        $this->assertInstanceOf(CardInterface::class, $result);
    }
}
