<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Gateway\Validator;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Model\Info;
use ParadoxLabs\TokenBase\Gateway\Validator\CreditCard;
use ParadoxLabs\TokenBase\Gateway\Validator\CreditCard\Types;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for credit card validation
 */
class CreditCardTest extends TestCase
{
    private CreditCard $validator;
    private ResultInterfaceFactory|MockObject $resultFactory;
    private ConfigInterface|MockObject $config;
    private Types|MockObject $ccTypes;
    private TimezoneInterface|MockObject $dateProcessor;
    private ResultInterface|MockObject $result;

    protected function setUp(): void
    {
        $this->resultFactory = $this->createMock(ResultInterfaceFactory::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->ccTypes = $this->createMock(Types::class);
        $this->dateProcessor = $this->createMock(TimezoneInterface::class);
        $this->result = $this->createMock(ResultInterface::class);

        $this->resultFactory->method('create')
            ->willReturn($this->result);

        $this->validator = new CreditCard(
            $this->resultFactory,
            $this->config,
            $this->ccTypes,
            $this->dateProcessor,
        );
    }

    /**
     * @dataProvider validLuhnNumbersProvider
     */
    public function testIsCcNumberMod10ValidReturnsTrueForValidNumbers(string $ccNumber): void
    {
        $this->assertTrue($this->validator->isCcNumberMod10Valid($ccNumber));
    }

    public static function validLuhnNumbersProvider(): array
    {
        return [
            'visa test card' => ['4111111111111111'],
            'mastercard test card' => ['5500000000000004'],
            'amex test card' => ['378282246310005'],
            'discover test card' => ['6011111111111117'],
            'visa with spaces' => ['4111 1111 1111 1111'],
            'visa with dashes' => ['4111-1111-1111-1111'],
        ];
    }

    /**
     * @dataProvider invalidLuhnNumbersProvider
     */
    public function testIsCcNumberMod10ValidReturnsFalseForInvalidNumbers(string $ccNumber): void
    {
        $this->assertFalse($this->validator->isCcNumberMod10Valid($ccNumber));
    }

    public static function invalidLuhnNumbersProvider(): array
    {
        return [
            'invalid checksum' => ['4111111111111112'],
            'sequential numbers' => ['1234567890123456'],
            'all ones' => ['1111111111111111'],
            'short invalid' => ['411111111111'],
        ];
    }

    public function testIsCcNumberMod10ValidHandlesEmptyString(): void
    {
        // Empty string results in sum of 0, which is divisible by 10
        $this->assertTrue($this->validator->isCcNumberMod10Valid(''));
    }

    public function testIsDateExpiredReturnsTrueForPastYear(): void
    {
        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);

        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->assertTrue($this->validator->isDateExpired(2024, 12));
    }

    public function testIsDateExpiredReturnsTrueForPastMonth(): void
    {
        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);

        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->assertTrue($this->validator->isDateExpired(2025, 5));
    }

    public function testIsDateExpiredReturnsFalseForCurrentMonth(): void
    {
        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);

        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->assertFalse($this->validator->isDateExpired(2025, 6));
    }

    public function testIsDateExpiredReturnsFalseForFutureMonth(): void
    {
        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);

        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->assertFalse($this->validator->isDateExpired(2025, 12));
    }

    public function testIsDateExpiredReturnsFalseForFutureYear(): void
    {
        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);

        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->assertFalse($this->validator->isDateExpired(2030, 1));
    }

    public function testIsDateExpiredReturnsTrueForInvalidYear(): void
    {
        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);

        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->assertTrue($this->validator->isDateExpired(0, 6));
    }

    public function testIsDateExpiredReturnsTrueForInvalidMonth(): void
    {
        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);

        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->assertTrue($this->validator->isDateExpired(2025, 0));
        $this->assertTrue($this->validator->isDateExpired(2025, 13));
    }

    public function testValidateSkipsValidationForStoredCard(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->with('tokenbase_id')
            ->willReturn('123');

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $result = $this->validator->validate(['payment' => $payment]);

        $this->assertSame($this->result, $result);
    }

    public function testValidateFailsForDisallowedCardType(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, null],
                ['cc_number', null, '4111111111111111'],
                ['cc_cid', null, '123'],
                ['cc_exp_year', null, '2030'],
                ['cc_exp_month', null, '12'],
            ]);
        $payment->method('getAdditionalInformation')
            ->willReturn(null);

        $this->config->method('getValue')
            ->willReturnMap([
                ['cctypes', null, 'MC,AE'],
                ['useccv', null, '0'],
            ]);

        $this->ccTypes->method('getTypeForCard')
            ->willReturn([
                'type' => 'VI',
                'title' => 'Visa',
                'luhn' => true,
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);
        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false
                    && count($args['failsDescription']) === 1;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForInvalidLuhnNumber(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, null],
                ['cc_number', null, '4111111111111112'],
                ['cc_cid', null, '123'],
                ['cc_exp_year', null, '2030'],
                ['cc_exp_month', null, '12'],
            ]);
        $payment->method('getAdditionalInformation')
            ->willReturn(null);

        $this->config->method('getValue')
            ->willReturnMap([
                ['cctypes', null, 'VI,MC'],
                ['useccv', null, '0'],
            ]);

        $this->ccTypes->method('getTypeForCard')
            ->willReturn([
                'type' => 'VI',
                'title' => 'Visa',
                'luhn' => true,
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturn('2025');
        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForInvalidCvv(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, null],
                ['cc_number', null, '4111111111111111'],
                ['cc_cid', null, '12'],
                ['cc_exp_year', null, '2030'],
                ['cc_exp_month', null, '12'],
            ]);
        $payment->method('getAdditionalInformation')
            ->willReturn(null);

        $this->config->method('getValue')
            ->willReturnMap([
                ['cctypes', null, 'VI,MC'],
                ['useccv', null, '1'],
            ]);

        $this->ccTypes->method('getTypeForCard')
            ->willReturn([
                'type' => 'VI',
                'title' => 'Visa',
                'luhn' => true,
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturn('2025');
        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidatePassesForValidCard(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, null],
                ['cc_number', null, '4111111111111111'],
                ['cc_cid', null, '123'],
                ['cc_exp_year', null, '2030'],
                ['cc_exp_month', null, '12'],
            ]);
        $payment->method('getAdditionalInformation')
            ->willReturn(null);

        $this->config->method('getValue')
            ->willReturnMap([
                ['cctypes', null, 'VI,MC'],
                ['useccv', null, '1'],
            ]);

        $this->ccTypes->method('getTypeForCard')
            ->willReturn([
                'type' => 'VI',
                'title' => 'Visa',
                'luhn' => true,
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);
        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForExpiredCard(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, null],
                ['cc_number', null, '4111111111111111'],
                ['cc_cid', null, '123'],
                ['cc_exp_year', null, '2020'],
                ['cc_exp_month', null, '12'],
            ]);
        $payment->method('getAdditionalInformation')
            ->willReturn(null);

        $this->config->method('getValue')
            ->willReturnMap([
                ['cctypes', null, 'VI,MC'],
                ['useccv', null, '1'],
            ]);

        $this->ccTypes->method('getTypeForCard')
            ->willReturn([
                'type' => 'VI',
                'title' => 'Visa',
                'luhn' => true,
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturnMap([
                ['Y', '2025'],
                ['m', '06'],
            ]);
        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForUndetectedCardType(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, null],
                ['cc_number', null, 'abc123'],
            ]);
        $payment->method('getAdditionalInformation')
            ->willReturn(null);

        $this->config->method('getValue')
            ->with('cctypes')
            ->willReturn('VI,MC');

        $this->ccTypes->method('getTypeForCard')
            ->willReturn(false);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testGetCcTypesReturnsTypesInstance(): void
    {
        $this->assertSame($this->ccTypes, $this->validator->getCcTypes());
    }

    public function testValidateStripsSpacesAndDashesFromCardNumber(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, null],
                ['cc_number', null, '4111-1111-1111-1111'],
                ['cc_cid', null, '123'],
                ['cc_exp_year', null, '2030'],
                ['cc_exp_month', null, '12'],
            ]);
        $payment->expects($this->once())
            ->method('setData')
            ->with('cc_number', '4111111111111111');
        $payment->method('getAdditionalInformation')
            ->willReturn(null);

        $this->config->method('getValue')
            ->willReturnMap([
                ['cctypes', null, 'VI'],
                ['useccv', null, '0'],
            ]);

        $this->ccTypes->method('getTypeForCard')
            ->with('4111111111111111')
            ->willReturn([
                'type' => 'VI',
                'title' => 'Visa',
                'luhn' => true,
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturn('2025');
        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateSkipsLuhnForUnionPay(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, null],
                ['cc_number', null, '6221261234567890'],
                ['cc_cid', null, '123'],
                ['cc_exp_year', null, '2030'],
                ['cc_exp_month', null, '12'],
            ]);
        $payment->method('getAdditionalInformation')
            ->willReturn(null);

        $this->config->method('getValue')
            ->willReturnMap([
                ['cctypes', null, 'UN'],
                ['useccv', null, '0'],
            ]);

        // UnionPay cards have luhn = false
        $this->ccTypes->method('getTypeForCard')
            ->willReturn([
                'type' => 'UN',
                'title' => 'UnionPay',
                'luhn' => false,
                'code' => ['name' => 'CVN', 'size' => 3],
            ]);

        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturn('2025');
        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        // Should pass even though the number doesn't pass Luhn
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testAmexRequires4DigitCvv(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, null],
                ['cc_number', null, '378282246310005'],
                ['cc_cid', null, '123'],
                ['cc_exp_year', null, '2030'],
                ['cc_exp_month', null, '12'],
            ]);
        $payment->method('getAdditionalInformation')
            ->willReturn(null);

        $this->config->method('getValue')
            ->willReturnMap([
                ['cctypes', null, 'AE'],
                ['useccv', null, '1'],
            ]);

        $this->ccTypes->method('getTypeForCard')
            ->willReturn([
                'type' => 'AE',
                'title' => 'American Express',
                'luhn' => true,
                'code' => ['name' => 'CID', 'size' => 4],
            ]);

        $dateMock = $this->createMock(\DateTime::class);
        $dateMock->method('format')
            ->willReturn('2025');
        $this->dateProcessor->method('date')
            ->willReturn($dateMock);

        // 3-digit CVV should fail for AmEx
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }
}
