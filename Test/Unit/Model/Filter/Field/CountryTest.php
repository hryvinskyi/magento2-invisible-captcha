<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldValueHintInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Geo\CountryResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\Country;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CountryTest extends TestCase
{
    /** @var CountryResolverInterface&MockObject */
    private CountryResolverInterface $countryResolver;
    private Country $field;

    protected function setUp(): void
    {
        $this->countryResolver = $this->createMock(CountryResolverInterface::class);
        $this->field = new Country($this->countryResolver);
    }

    public function testMetadata(): void
    {
        $this->assertSame('country', $this->field->getCode());
        $this->assertSame('Country (ISO 3166-1 alpha-2)', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_STRING, $this->field->getType());
    }

    public function testGetValueReturnsResolvedCountryCode(): void
    {
        $this->countryResolver->method('getCountryCode')->willReturn('UA');
        $this->assertSame('UA', $this->field->getValue());
    }

    public function testGetValueReturnsEmptyStringWhenCountryUnknown(): void
    {
        $this->countryResolver->method('getCountryCode')->willReturn(null);
        $this->assertSame('', $this->field->getValue());
    }

    public function testExposesValueHintForTheRulesEditor(): void
    {
        $this->assertInstanceOf(FieldValueHintInterface::class, $this->field);

        $hint = $this->field->getValueHint();
        $this->assertSame('^[A-Za-z0-9]{2}$', $hint['pattern']);
        $this->assertSame('UA', $hint['placeholder']);
        $this->assertArrayHasKey('message', $hint);
    }
}
