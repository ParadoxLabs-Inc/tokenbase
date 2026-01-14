<?php

declare(strict_types=1);

namespace ParadoxLabs\TokenBase\Test\Unit\Gateway\Validator\CreditCard;

use ParadoxLabs\TokenBase\Gateway\Validator\CreditCard\Types;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for credit card type detection
 */
class TypesTest extends TestCase
{
    private Types $types;

    protected function setUp(): void
    {
        $this->types = new Types();
    }

    /**
     * @dataProvider visaCardNumbersProvider
     */
    public function testDetectsVisaCards(string $cardNumber): void
    {
        $result = $this->types->getTypeForCard($cardNumber);

        $this->assertIsArray($result);
        $this->assertSame('VI', $result['type']);
        $this->assertSame('Visa', $result['title']);
        $this->assertTrue($result['luhn']);
        $this->assertSame(3, $result['code']['size']);
    }

    public static function visaCardNumbersProvider(): array
    {
        return [
            'visa 16 digit' => ['4111111111111111'],
            'visa 13 digit' => ['4111111111111'],
            'visa 19 digit' => ['4111111111111111111'],
            'visa different prefix' => ['4012888888881881'],
        ];
    }

    /**
     * @dataProvider masterCardNumbersProvider
     */
    public function testDetectsMasterCardCards(string $cardNumber): void
    {
        $result = $this->types->getTypeForCard($cardNumber);

        $this->assertIsArray($result);
        $this->assertSame('MC', $result['type']);
        $this->assertSame('MasterCard', $result['title']);
        $this->assertTrue($result['luhn']);
        $this->assertSame(3, $result['code']['size']);
    }

    public static function masterCardNumbersProvider(): array
    {
        return [
            'mastercard 51xx' => ['5105105105105100'],
            'mastercard 52xx' => ['5200828282828210'],
            'mastercard 53xx' => ['5300000000000000'],
            'mastercard 54xx' => ['5400000000000000'],
            'mastercard 55xx' => ['5500000000000004'],
            'mastercard 2221' => ['2221000000000000'],
            'mastercard 2720' => ['2720000000000000'],
        ];
    }

    /**
     * @dataProvider amexCardNumbersProvider
     */
    public function testDetectsAmexCards(string $cardNumber): void
    {
        $result = $this->types->getTypeForCard($cardNumber);

        $this->assertIsArray($result);
        $this->assertSame('AE', $result['type']);
        $this->assertSame('American Express', $result['title']);
        $this->assertTrue($result['luhn']);
        $this->assertSame(4, $result['code']['size']);
    }

    public static function amexCardNumbersProvider(): array
    {
        return [
            'amex 34xx' => ['340000000000009'],
            'amex 37xx' => ['378282246310005'],
        ];
    }

    /**
     * @dataProvider discoverCardNumbersProvider
     */
    public function testDetectsDiscoverCards(string $cardNumber): void
    {
        $result = $this->types->getTypeForCard($cardNumber);

        $this->assertIsArray($result);
        $this->assertSame('DI', $result['type']);
        $this->assertSame('Discover', $result['title']);
        $this->assertTrue($result['luhn']);
        $this->assertSame(3, $result['code']['size']);
    }

    public static function discoverCardNumbersProvider(): array
    {
        return [
            'discover 60110' => ['6011000000000000'],
            'discover 60112' => ['6011200000000000'],
            'discover 65xx' => ['6500000000000000'],
            'discover 644x' => ['6440000000000000'],
        ];
    }

    /**
     * @dataProvider jcbCardNumbersProvider
     */
    public function testDetectsJcbCards(string $cardNumber): void
    {
        $result = $this->types->getTypeForCard($cardNumber);

        $this->assertIsArray($result);
        $this->assertSame('JCB', $result['type']);
        $this->assertSame('JCB', $result['title']);
        $this->assertTrue($result['luhn']);
    }

    public static function jcbCardNumbersProvider(): array
    {
        return [
            'jcb 3528' => ['3528000000000000'],
            'jcb 3589' => ['3589000000000000'],
        ];
    }

    /**
     * @dataProvider dinersCardNumbersProvider
     */
    public function testDetectsDinersCards(string $cardNumber): void
    {
        $result = $this->types->getTypeForCard($cardNumber);

        $this->assertIsArray($result);
        $this->assertSame('DN', $result['type']);
        $this->assertSame('Diners', $result['title']);
        $this->assertTrue($result['luhn']);
    }

    public static function dinersCardNumbersProvider(): array
    {
        return [
            'diners 300' => ['30000000000004'],
            'diners 305' => ['30500000000003'],
            'diners 36' => ['36000000000008'],
            'diners 38' => ['38000000000006'],
        ];
    }

    /**
     * @dataProvider unionPayCardNumbersProvider
     */
    public function testDetectsUnionPayCards(string $cardNumber): void
    {
        $result = $this->types->getTypeForCard($cardNumber);

        $this->assertIsArray($result);
        $this->assertSame('UN', $result['type']);
        $this->assertSame('UnionPay', $result['title']);
        $this->assertFalse($result['luhn']);
    }

    public static function unionPayCardNumbersProvider(): array
    {
        return [
            'unionpay 6221' => ['6221260000000000'],
            'unionpay 624' => ['6240000000000000'],
            'unionpay 626' => ['6260000000000000'],
        ];
    }

    public function testGetTypesReturnsAllTypes(): void
    {
        $types = $this->types->getTypes();

        $this->assertIsArray($types);
        $this->assertGreaterThanOrEqual(10, count($types));

        foreach ($types as $type) {
            $this->assertArrayHasKey('type', $type);
            $this->assertArrayHasKey('title', $type);
            $this->assertArrayHasKey('pattern', $type);
            $this->assertArrayHasKey('luhn', $type);
            $this->assertArrayHasKey('code', $type);
            $this->assertArrayHasKey('lengths', $type);
        }
    }

    public function testGetTypeByCodeReturnsCorrectType(): void
    {
        $visa = $this->types->getType('VI');
        $this->assertIsArray($visa);
        $this->assertSame('Visa', $visa['title']);

        $mastercard = $this->types->getType('MC');
        $this->assertIsArray($mastercard);
        $this->assertSame('MasterCard', $mastercard['title']);

        $amex = $this->types->getType('AE');
        $this->assertIsArray($amex);
        $this->assertSame('American Express', $amex['title']);
    }

    public function testGetTypeReturnsCorrectCvvSize(): void
    {
        $visa = $this->types->getType('VI');
        $this->assertSame(3, $visa['code']['size']);

        $amex = $this->types->getType('AE');
        $this->assertSame(4, $amex['code']['size']);
    }

    public function testGetTypeReturnsFalseForUnknownCode(): void
    {
        $result = $this->types->getType('XX');
        $this->assertFalse($result);
    }

    public function testGetTypeForCardReturnsFalseForInvalidNumber(): void
    {
        $result = $this->types->getTypeForCard('abc123');
        $this->assertFalse($result);
    }

    public function testGetTypeForCardHandlesEmptyString(): void
    {
        $result = $this->types->getTypeForCard('');

        // Empty string matches 'Other' type pattern (^\d*$)
        $this->assertIsArray($result);
        $this->assertSame('OT', $result['type']);
    }

    public function testUnknownNumericCardFallsToOther(): void
    {
        // A numeric string that doesn't match specific patterns falls to 'Other'
        $result = $this->types->getTypeForCard('9999999999999999');

        $this->assertIsArray($result);
        $this->assertSame('OT', $result['type']);
        $this->assertSame('Other', $result['title']);
    }

    public function testCardTypeLengthsAreCorrect(): void
    {
        $visa = $this->types->getType('VI');
        $this->assertContains(16, $visa['lengths']);
        $this->assertContains(13, $visa['lengths']);

        $amex = $this->types->getType('AE');
        $this->assertContains(15, $amex['lengths']);
        $this->assertNotContains(16, $amex['lengths']);
    }
}
