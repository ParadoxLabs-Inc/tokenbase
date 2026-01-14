<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Gateway\Validator;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Model\Info;
use ParadoxLabs\TokenBase\Gateway\Validator\CreditCard;
use ParadoxLabs\TokenBase\Gateway\Validator\CreditCard\Types;
use ParadoxLabs\TokenBase\Gateway\Validator\StoredCard;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for stored card validation
 */
class StoredCardTest extends TestCase
{
    private StoredCard $validator;
    private ResultInterfaceFactory|MockObject $resultFactory;
    private CreditCard|MockObject $ccValidator;
    private ConfigInterface|MockObject $config;
    private Types|MockObject $ccTypes;
    private ResultInterface|MockObject $result;

    protected function setUp(): void
    {
        $this->resultFactory = $this->createMock(ResultInterfaceFactory::class);
        $this->ccValidator = $this->createMock(CreditCard::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->ccTypes = $this->createMock(Types::class);
        $this->result = $this->createMock(ResultInterface::class);

        $this->resultFactory->method('create')
            ->willReturn($this->result);

        $this->ccValidator->method('getCcTypes')
            ->willReturn($this->ccTypes);

        $this->validator = new StoredCard(
            $this->resultFactory,
            $this->ccValidator,
            $this->config,
        );
    }

    public function testValidatePassesForNewCard(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->with('tokenbase_id')
            ->willReturn(null);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $result = $this->validator->validate(['payment' => $payment]);

        $this->assertSame($this->result, $result);
    }

    public function testValidatePassesForStoredCardWhenCvvNotRequired(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_number', null, null],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('0');

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForStoredCardWithMissingCvv(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_type', null, 'VI'],
                ['cc_cid', null, null],
                ['cc_number', null, null],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);
        $payment->method('getAdditionalInformation')
            ->with('is_subscription_generated')
            ->willReturn(null);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('1');

        $this->ccTypes->method('getType')
            ->with('VI')
            ->willReturn([
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false
                    && count($args['failsDescription']) === 1;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidatePassesForSubscriptionGeneratedPayment(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_number', null, null],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);
        $payment->method('getAdditionalInformation')
            ->with('is_subscription_generated')
            ->willReturn(1);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('1');

        // CVV is required, but this is subscription-generated so it should pass
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForInvalidCvvLength(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_type', null, 'VI'],
                ['cc_cid', null, '12'],
                ['cc_number', null, null],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);
        $payment->method('getAdditionalInformation')
            ->with('is_subscription_generated')
            ->willReturn(null);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('1');

        $this->ccTypes->method('getType')
            ->with('VI')
            ->willReturn([
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidatePassesForValidCvv(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_type', null, 'VI'],
                ['cc_cid', null, '456'],
                ['cc_number', null, null],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);
        $payment->method('getAdditionalInformation')
            ->with('is_subscription_generated')
            ->willReturn(null);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('1');

        $this->ccTypes->method('getType')
            ->with('VI')
            ->willReturn([
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForAmexWith3DigitCvv(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_type', null, 'AE'],
                ['cc_cid', null, '123'],
                ['cc_number', null, null],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);
        $payment->method('getAdditionalInformation')
            ->with('is_subscription_generated')
            ->willReturn(null);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('1');

        $this->ccTypes->method('getType')
            ->with('AE')
            ->willReturn([
                'code' => ['name' => 'CID', 'size' => 4],
            ]);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateSkipsCardNumberValidationForMaskedNumber(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_number', null, 'XXXX1111'],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('0');

        // Should not call Luhn validation
        $this->ccValidator->expects($this->never())
            ->method('isCcNumberMod10Valid');

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForInvalidCardNumberOnEdit(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_number', null, '4111111111111112'],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('0');

        $this->ccValidator->method('isCcNumberMod10Valid')
            ->with('4111111111111112')
            ->willReturn(false);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForTooShortCardNumber(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_number', null, '411111111'],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('0');

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForNonNumericCardNumber(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_number', null, 'abcd1111111111111'],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('0');

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForExpiredCard(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_number', null, null],
                ['cc_exp_year', null, '2020'],
                ['cc_exp_month', null, '12'],
            ]);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('0');

        $this->ccValidator->method('isDateExpired')
            ->with('2020', '12')
            ->willReturn(true);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidatePassesForValidExpirationDate(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_number', null, null],
                ['cc_exp_year', null, '2030'],
                ['cc_exp_month', null, '12'],
            ]);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('0');

        $this->ccValidator->method('isDateExpired')
            ->with('2030', '12')
            ->willReturn(false);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateSkipsExpirationCheckWhenNotProvided(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_number', null, null],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('0');

        // Should not call expiration validation
        $this->ccValidator->expects($this->never())
            ->method('isDateExpired');

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateHandlesUnknownCardType(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_type', null, 'XX'],
                ['cc_cid', null, '123'],
                ['cc_number', null, null],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);
        $payment->method('getAdditionalInformation')
            ->with('is_subscription_generated')
            ->willReturn(null);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('1');

        $this->ccTypes->method('getType')
            ->with('XX')
            ->willReturn(false);

        // Should still validate CVV >= 3 digits
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [], 'errorCodes' => []])
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }

    public function testValidateFailsForNonNumericCvv(): void
    {
        $payment = $this->createMock(Info::class);
        $payment->method('getData')
            ->willReturnMap([
                ['tokenbase_id', null, '123'],
                ['cc_type', null, 'VI'],
                ['cc_cid', null, 'abc'],
                ['cc_number', null, null],
                ['cc_exp_year', null, null],
                ['cc_exp_month', null, null],
            ]);
        $payment->method('getAdditionalInformation')
            ->with('is_subscription_generated')
            ->willReturn(null);

        $this->config->method('getValue')
            ->with('require_ccv')
            ->willReturn('1');

        $this->ccTypes->method('getType')
            ->with('VI')
            ->willReturn([
                'code' => ['name' => 'CVV', 'size' => 3],
            ]);

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($args) {
                return $args['isValid'] === false;
            }))
            ->willReturn($this->result);

        $this->validator->validate(['payment' => $payment]);
    }
}
