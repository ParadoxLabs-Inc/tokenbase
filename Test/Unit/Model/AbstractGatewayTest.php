<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model;

use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\HTTP\ClientInterfaceFactory;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Model\InfoInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\AbstractGateway;
use ParadoxLabs\TokenBase\Model\Gateway\Response;
use ParadoxLabs\TokenBase\Model\Gateway\ResponseFactory;
use ParadoxLabs\TokenBase\Model\Gateway\Xml;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AbstractGateway
 */
class AbstractGatewayTest extends TestCase
{
    private TestableGateway $gateway;
    private Data|MockObject $helper;
    private Xml|MockObject $xml;
    private ResponseFactory|MockObject $responseFactory;
    private ClientInterfaceFactory|MockObject $communicatorFactory;

    protected function setUp(): void
    {
        $this->helper = $this->createMock(Data::class);
        $this->xml = $this->createMock(Xml::class);
        $this->responseFactory = $this->createMock(ResponseFactory::class);
        $this->communicatorFactory = $this->createMock(ClientInterfaceFactory::class);

        $this->gateway = new TestableGateway(
            $this->helper,
            $this->xml,
            $this->responseFactory,
            $this->communicatorFactory,
        );
    }

    public function testInitSetsCredentials(): void
    {
        $this->gateway->init([
            'login' => 'testlogin',
            'password' => 'testpass',
            'secret_key' => 'secretkey',
            'test_mode' => true,
        ]);

        $this->assertTrue($this->gateway->isInitialized());
        $this->assertSame('testlogin', $this->gateway->getParameter('login'));
        $this->assertSame('testpass', $this->gateway->getParameter('password'));
    }

    public function testInitSetsTestEndpoint(): void
    {
        $this->gateway->init([
            'login' => 'test',
            'password' => 'test',
            'test_mode' => true,
        ]);

        $this->assertSame('https://test.example.com', $this->gateway->getPublicEndpoint());
    }

    public function testInitSetsLiveEndpoint(): void
    {
        $this->gateway->init([
            'login' => 'test',
            'password' => 'test',
            'test_mode' => false,
        ]);

        $this->assertSame('https://live.example.com', $this->gateway->getPublicEndpoint());
    }

    public function testInitUsesCustomEndpoint(): void
    {
        $this->gateway->init([
            'login' => 'test',
            'password' => 'test',
            'endpoint' => 'https://custom.example.com',
        ]);

        $this->assertSame('https://custom.example.com', $this->gateway->getPublicEndpoint());
    }

    public function testResetClearsState(): void
    {
        $this->gateway->init([
            'login' => 'test',
            'password' => 'test',
            'test_mode' => true,
        ]);

        $this->assertTrue($this->gateway->isInitialized());

        $this->gateway->reset();

        $this->assertFalse($this->gateway->isInitialized());
        $this->assertSame('', $this->gateway->getParameter('login'));
    }

    public function testClearParametersResetsToDefaults(): void
    {
        $this->gateway->init([
            'login' => 'defaultlogin',
            'password' => 'defaultpass',
        ]);

        $this->gateway->setParameter('custom_field', 'custom_value');
        $this->assertSame('custom_value', $this->gateway->getParameter('custom_field'));

        $this->gateway->clearParameters();

        $this->assertSame('defaultlogin', $this->gateway->getParameter('login'));
        $this->assertSame('', $this->gateway->getParameter('custom_field'));
    }

    public function testSetParameterThrowsForUnknownField(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Unknown parameter');

        $this->gateway->setParameter('unknown_field', 'value');
    }

    public function testSetParameterAppliesMaxLength(): void
    {
        // max_length_field has maxLength of 10
        $this->gateway->setParameter('max_length_field', 'This is a very long string that exceeds the limit');

        $this->assertSame('This is a ', $this->gateway->getParameter('max_length_field'));
    }

    public function testSetParameterAppliesNoSymbols(): void
    {
        // no_symbols_field has noSymbols = true
        $this->gateway->setParameter('no_symbols_field', 'Test <script>alert()</script> value!');

        $result = $this->gateway->getParameter('no_symbols_field');

        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
        $this->assertStringNotContainsString('!', $result);
    }

    public function testSetParameterAppliesCharMask(): void
    {
        // char_mask_field allows only digits
        $this->gateway->setParameter('char_mask_field', 'abc123def456');

        $this->assertSame('123456', $this->gateway->getParameter('char_mask_field'));
    }

    public function testSetParameterValidatesEnum(): void
    {
        // enum_field allows only 'value1', 'value2', 'value3'
        $this->gateway->setParameter('enum_field', 'value2');

        $this->assertSame('value2', $this->gateway->getParameter('enum_field'));
    }

    public function testSetParameterThrowsForInvalidEnum(): void
    {
        $this->expectException(CommandException::class);
        $this->expectExceptionMessage('Invalid value');

        $this->gateway->setParameter('enum_field', 'invalid_value');
    }

    public function testSetParameterHandlesBoolean(): void
    {
        $this->gateway->setParameter('boolean_field', true);
        $this->assertTrue($this->gateway->getParameter('boolean_field'));

        $this->gateway->setParameter('boolean_field', false);
        // false is not empty, so it should be set
        $this->assertFalse($this->gateway->getParameter('boolean_field'));
    }

    public function testSetParameterRemovesOnNull(): void
    {
        $this->gateway->setParameter('custom_field', 'value');
        $this->assertSame('value', $this->gateway->getParameter('custom_field'));

        $this->gateway->setParameter('custom_field', null);
        $this->assertSame('', $this->gateway->getParameter('custom_field'));
    }

    public function testSetParameterIgnoresEmptyValues(): void
    {
        $this->gateway->setParameter('custom_field', '');

        $this->assertSame('', $this->gateway->getParameter('custom_field'));
    }

    public function testGetParametersReturnsAllParams(): void
    {
        $this->gateway->init([
            'login' => 'test',
            'password' => 'pass',
        ]);

        $params = $this->gateway->getParameters();

        $this->assertIsArray($params);
        $this->assertSame('test', $params['login']);
        $this->assertSame('pass', $params['password']);
    }

    public function testGetParameterReturnsDefault(): void
    {
        $result = $this->gateway->getParameter('nonexistent', 'default_value');

        $this->assertSame('default_value', $result);
    }

    public function testHasParameterReturnsTrueForExisting(): void
    {
        $this->gateway->setParameter('custom_field', 'value');

        $this->assertTrue($this->gateway->hasParameter('custom_field'));
    }

    public function testHasParameterReturnsFalseForMissing(): void
    {
        $this->assertFalse($this->gateway->hasParameter('nonexistent'));
    }

    public function testHasParameterReturnsFalseForEmpty(): void
    {
        $this->gateway->init([
            'login' => '',
            'password' => 'pass',
        ]);

        $this->assertFalse($this->gateway->hasParameter('login'));
    }

    public function testFormatAmountReturnsFormattedString(): void
    {
        $this->assertSame('10.00', AbstractGateway::formatAmount(10));
        $this->assertSame('10.50', AbstractGateway::formatAmount(10.5));
        $this->assertSame('10.99', AbstractGateway::formatAmount(10.99));
        $this->assertSame('10.10', AbstractGateway::formatAmount(10.1));
        $this->assertSame('0.00', AbstractGateway::formatAmount(0));
    }

    public function testFormatAmountRoundsToTwoDecimals(): void
    {
        $this->assertSame('10.12', AbstractGateway::formatAmount(10.123));
        $this->assertSame('10.13', AbstractGateway::formatAmount(10.126));
    }

    public function testSetAndGetCard(): void
    {
        $card = $this->createMock(CardInterface::class);

        $this->gateway->setCard($card);

        $this->assertSame($card, $this->gateway->getCard());
    }

    public function testSetAndGetHaveAuthorized(): void
    {
        $this->assertFalse($this->gateway->getHaveAuthorized());

        $this->gateway->setHaveAuthorized(true);
        $this->assertTrue($this->gateway->getHaveAuthorized());

        $this->gateway->setHaveAuthorized(false);
        $this->assertFalse($this->gateway->getHaveAuthorized());
    }

    public function testSetLineItems(): void
    {
        $items = [
            ['name' => 'Item 1', 'qty' => 1],
            ['name' => 'Item 2', 'qty' => 2],
        ];

        $result = $this->gateway->setLineItems($items);

        $this->assertSame($this->gateway, $result);
    }

    public function testLogLogs(): void
    {
        $this->helper->expects($this->once())
            ->method('log');

        $this->gateway->logLogs();
    }

    public function testNoSymbolsConvertsAccentedCharacters(): void
    {
        // noSymbols should convert accented chars to simple ASCII
        $this->gateway->setParameter('no_symbols_field', 'café résumé');

        $result = $this->gateway->getParameter('no_symbols_field');

        // Accented characters should be converted
        $this->assertStringContainsString('caf', $result);
    }

    public function testSetParameterReturnsThis(): void
    {
        $result = $this->gateway->setParameter('custom_field', 'value');

        $this->assertSame($this->gateway, $result);
    }

    public function testInitReturnsThis(): void
    {
        $result = $this->gateway->init([
            'login' => 'test',
            'password' => 'test',
        ]);

        $this->assertSame($this->gateway, $result);
    }

    public function testResetReturnsThis(): void
    {
        $result = $this->gateway->reset();

        $this->assertSame($this->gateway, $result);
    }

    public function testClearParametersReturnsThis(): void
    {
        $result = $this->gateway->clearParameters();

        $this->assertSame($this->gateway, $result);
    }
}

/**
 * Concrete implementation of AbstractGateway for testing
 */
class TestableGateway extends AbstractGateway
{
    protected $endpointLive = 'https://live.example.com';
    protected $endpointTest = 'https://test.example.com';
    protected $code = 'test_gateway';

    protected $fields = [
        'login' => [],
        'password' => [],
        'custom_field' => [],
        'boolean_field' => [],
        'max_length_field' => ['maxLength' => 10],
        'no_symbols_field' => ['noSymbols' => true],
        'char_mask_field' => ['charMask' => '0-9'],
        'enum_field' => ['enum' => ['value1', 'value2', 'value3']],
        'auth_code' => [],
        'transaction_id' => [],
    ];

    public function getPublicEndpoint(): string
    {
        return $this->endpoint ?? '';
    }

    public function authorize(InfoInterface $payment, $amount): Response
    {
        return $this->responseFactory->create();
    }

    public function capture(InfoInterface $payment, $amount, $transactionId = null): Response
    {
        return $this->responseFactory->create();
    }

    public function refund(InfoInterface $payment, $amount, $transactionId = null): Response
    {
        return $this->responseFactory->create();
    }

    public function void(InfoInterface $payment, $transactionId = null): Response
    {
        return $this->responseFactory->create();
    }

    public function fraudUpdate(InfoInterface $payment, $transactionId): Response
    {
        return $this->responseFactory->create();
    }
}
