<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model\Gateway;

use ParadoxLabs\TokenBase\Model\Gateway\Response;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gateway Response model
 */
class ResponseTest extends TestCase
{
    private Response $response;

    protected function setUp(): void
    {
        $this->response = new Response();
    }

    public function testSetAndGetIsFraud(): void
    {
        $this->response->setIsFraud(true);
        $this->assertTrue($this->response->getIsFraud());

        $this->response->setIsFraud(false);
        $this->assertFalse($this->response->getIsFraud());
    }

    public function testGetIsFraudReturnsFalseByDefault(): void
    {
        $this->assertFalse($this->response->getIsFraud());
    }

    public function testSetAndGetIsError(): void
    {
        $this->response->setIsError(true);
        $this->assertTrue($this->response->getIsError());

        $this->response->setIsError(false);
        $this->assertFalse($this->response->getIsError());
    }

    public function testGetIsErrorReturnsFalseByDefault(): void
    {
        $this->assertFalse($this->response->getIsError());
    }

    public function testGetResponseCode(): void
    {
        $this->response->setData('response_code', '1');
        $this->assertSame('1', $this->response->getResponseCode());
    }

    public function testGetResponseReasonCode(): void
    {
        $this->response->setData('response_reason_code', 'APPROVED');
        $this->assertSame('APPROVED', $this->response->getResponseReasonCode());
    }

    public function testGetTransactionType(): void
    {
        $this->response->setData('transaction_type', 'AUTH_CAPTURE');
        $this->assertSame('AUTH_CAPTURE', $this->response->getTransactionType());
    }

    public function testGetTransactionId(): void
    {
        $this->response->setData('transaction_id', '12345');
        $this->assertSame('12345', $this->response->getTransactionId());
    }

    public function testGetAuthCode(): void
    {
        $this->response->setData('auth_code', 'ABC123');
        $this->assertSame('ABC123', $this->response->getAuthCode());
    }

    public function testGetMethod(): void
    {
        $this->response->setData('method', 'CC');
        $this->assertSame('CC', $this->response->getMethod());
    }

    public function testGetResponseReasonText(): void
    {
        $this->response->setData('response_reason_text', 'This transaction has been approved.');
        $this->assertSame('This transaction has been approved.', $this->response->getResponseReasonText());
    }

    public function testGetDataFlattensNestedArrays(): void
    {
        $this->response->setData([
            'transaction_id' => '12345',
            'avs' => [
                'code' => 'Y',
                'message' => 'Match',
            ],
        ]);

        $data = $this->response->getData();

        $this->assertSame('12345', $data['transaction_id']);
        $this->assertSame('Y', $data['avs.code']);
        $this->assertSame('Match', $data['avs.message']);
        $this->assertArrayNotHasKey('avs', $data);
    }

    public function testGetDataWithKeyDoesNotFlatten(): void
    {
        $this->response->setData([
            'nested' => [
                'key' => 'value',
            ],
        ]);

        $result = $this->response->getData('nested');

        $this->assertIsArray($result);
        $this->assertSame('value', $result['key']);
    }

    public function testFlattenArrayConvertsBooleans(): void
    {
        $this->response->setData([
            'is_approved' => true,
            'is_declined' => false,
        ]);

        $data = $this->response->getData();

        $this->assertSame('1', $data['is_approved']);
        $this->assertSame('0', $data['is_declined']);
    }

    public function testFlattenArrayHandlesDeeplyNestedArrays(): void
    {
        $this->response->setData([
            'level1' => [
                'level2' => [
                    'level3' => 'deep_value',
                ],
            ],
        ]);

        $data = $this->response->getData();

        $this->assertSame('deep_value', $data['level1.level2.level3']);
    }

    public function testFlattenArrayPreservesScalars(): void
    {
        $this->response->setData([
            'string' => 'text',
            'int' => 42,
            'float' => 3.14,
            'null' => null,
        ]);

        $data = $this->response->getData();

        $this->assertSame('text', $data['string']);
        $this->assertSame(42, $data['int']);
        $this->assertSame(3.14, $data['float']);
        $this->assertNull($data['null']);
    }

    public function testFlattenArrayHandlesEmptyArray(): void
    {
        $this->response->setData([]);

        $data = $this->response->getData();

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    public function testFlattenArrayHandlesMixedNestedAndFlat(): void
    {
        $this->response->setData([
            'flat_key' => 'flat_value',
            'nested' => [
                'inner_key' => 'inner_value',
            ],
            'another_flat' => 123,
        ]);

        $data = $this->response->getData();

        $this->assertSame('flat_value', $data['flat_key']);
        $this->assertSame('inner_value', $data['nested.inner_key']);
        $this->assertSame(123, $data['another_flat']);
    }

    public function testSetIsFraudReturnsThis(): void
    {
        $result = $this->response->setIsFraud(true);
        $this->assertSame($this->response, $result);
    }

    public function testSetIsErrorReturnsThis(): void
    {
        $result = $this->response->setIsError(true);
        $this->assertSame($this->response, $result);
    }

    public function testResponseCanBeConstructedWithData(): void
    {
        $response = new Response([
            'transaction_id' => '999',
            'response_code' => '1',
        ]);

        $this->assertSame('999', $response->getTransactionId());
        $this->assertSame('1', $response->getResponseCode());
    }

    public function testFlattenArrayHandlesNumericKeys(): void
    {
        $this->response->setData([
            'items' => [
                0 => 'first',
                1 => 'second',
            ],
        ]);

        $data = $this->response->getData();

        $this->assertSame('first', $data['items.0']);
        $this->assertSame('second', $data['items.1']);
    }
}
