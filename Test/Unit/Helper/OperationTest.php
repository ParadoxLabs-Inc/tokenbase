<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Phrase;
use Monolog\Logger;
use ParadoxLabs\TokenBase\Helper\Operation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for Operation helper
 */
class OperationTest extends TestCase
{
    private Operation $helper;
    private Context|MockObject $context;
    private Logger|MockObject $logger;
    private RemoteAddress|MockObject $remoteAddress;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->logger = $this->createMock(Logger::class);
        $this->remoteAddress = $this->createMock(RemoteAddress::class);

        $this->context->method('getRemoteAddress')
            ->willReturn($this->remoteAddress);
        $this->remoteAddress->method('getRemoteAddress')
            ->willReturn('127.0.0.1');

        $this->helper = new Operation($this->context, $this->logger);
    }

    public function testCleanupArrayRemovesObjects(): void
    {
        $object = new stdClass();
        $object->foo = 'bar';

        $array = [
            'string' => 'value',
            'number' => 123,
            'object' => $object,
            'null' => null,
        ];

        $this->helper->cleanupArray($array);

        $this->assertArrayHasKey('string', $array);
        $this->assertArrayHasKey('number', $array);
        $this->assertArrayNotHasKey('object', $array);
        $this->assertArrayHasKey('null', $array);
    }

    public function testCleanupArrayRemovesNestedObjects(): void
    {
        $object = new stdClass();

        $array = [
            'level1' => [
                'level2' => [
                    'object' => $object,
                    'string' => 'value',
                ],
                'keep' => 'this',
            ],
        ];

        $this->helper->cleanupArray($array);

        $this->assertArrayHasKey('level1', $array);
        $this->assertArrayHasKey('level2', $array['level1']);
        $this->assertArrayNotHasKey('object', $array['level1']['level2']);
        $this->assertSame('value', $array['level1']['level2']['string']);
        $this->assertSame('this', $array['level1']['keep']);
    }

    public function testCleanupArrayHandlesEmptyArray(): void
    {
        $array = [];
        $this->helper->cleanupArray($array);
        $this->assertEmpty($array);
    }

    public function testCleanupArrayHandlesNull(): void
    {
        $array = null;
        $this->helper->cleanupArray($array);
        $this->assertNull($array);
    }

    public function testCleanupArrayPreservesScalars(): void
    {
        $array = [
            'string' => 'value',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
        ];

        $this->helper->cleanupArray($array);

        $this->assertSame('value', $array['string']);
        $this->assertSame(42, $array['int']);
        $this->assertSame(3.14, $array['float']);
        $this->assertTrue($array['bool']);
        $this->assertNull($array['null']);
    }

    public function testGetArrayValueReturnsValueForSimplePath(): void
    {
        $data = ['key' => 'value'];

        $result = $this->helper->getArrayValue($data, 'key');

        $this->assertSame('value', $result);
    }

    public function testGetArrayValueReturnsValueForNestedPath(): void
    {
        $data = [
            'level1' => [
                'level2' => [
                    'level3' => 'deep value',
                ],
            ],
        ];

        $result = $this->helper->getArrayValue($data, 'level1/level2/level3');

        $this->assertSame('deep value', $result);
    }

    public function testGetArrayValueReturnsDefaultForMissingKey(): void
    {
        $data = ['existing' => 'value'];

        $result = $this->helper->getArrayValue($data, 'nonexistent', 'default');

        $this->assertSame('default', $result);
    }

    public function testGetArrayValueReturnsDefaultForMissingNestedKey(): void
    {
        $data = [
            'level1' => [
                'level2' => 'value',
            ],
        ];

        $result = $this->helper->getArrayValue($data, 'level1/level2/level3', 'default');

        $this->assertSame('default', $result);
    }

    public function testGetArrayValueReturnsEmptyStringAsDefaultDefault(): void
    {
        $data = [];

        $result = $this->helper->getArrayValue($data, 'missing');

        $this->assertSame('', $result);
    }

    public function testGetArrayValueReturnsArrayForIntermediatePath(): void
    {
        $data = [
            'level1' => [
                'level2' => ['a', 'b', 'c'],
            ],
        ];

        $result = $this->helper->getArrayValue($data, 'level1/level2');

        $this->assertSame(['a', 'b', 'c'], $result);
    }

    public function testGetArrayValueHandlesNumericKeys(): void
    {
        $data = [
            'items' => [
                0 => 'first',
                1 => 'second',
            ],
        ];

        $result = $this->helper->getArrayValue($data, 'items/0');

        $this->assertSame('first', $result);
    }

    public function testGetArrayValueHandlesEmptyPath(): void
    {
        $data = ['key' => 'value'];

        // Empty path should return default since '' is not a key
        $result = $this->helper->getArrayValue($data, '', 'default');

        $this->assertSame('default', $result);
    }

    public function testLogWritesInfoMessage(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('TestCode'));

        $this->helper->log('TestCode', 'Test message');
    }

    public function testLogWritesDebugMessage(): void
    {
        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('TestCode'));

        $this->helper->log('TestCode', 'Test message', true);
    }

    public function testLogHandlesPhrase(): void
    {
        $phrase = new Phrase('Translated message');

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Translated message'));

        $this->helper->log('TestCode', $phrase);
    }

    public function testLogHandlesDataObject(): void
    {
        $dataObject = new DataObject(['key' => 'value']);

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('key'));

        $this->helper->log('TestCode', $dataObject);
    }

    public function testLogHandlesArray(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('test_key'));

        $this->helper->log('TestCode', ['test_key' => 'test_value']);
    }

    public function testLogIncludesRemoteAddress(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('127.0.0.1'));

        $this->helper->log('TestCode', 'message');
    }

    public function testLogReturnsThis(): void
    {
        $result = $this->helper->log('TestCode', 'message');

        $this->assertSame($this->helper, $result);
    }

    public function testLogHandlesStdClassObject(): void
    {
        $object = new stdClass();
        $object->property = 'value';

        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('property'));

        $this->helper->log('TestCode', $object);
    }
}
