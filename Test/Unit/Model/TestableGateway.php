<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Model;

use Magento\Payment\Model\InfoInterface;
use ParadoxLabs\TokenBase\Model\AbstractGateway;
use ParadoxLabs\TokenBase\Model\Gateway\Response;

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
