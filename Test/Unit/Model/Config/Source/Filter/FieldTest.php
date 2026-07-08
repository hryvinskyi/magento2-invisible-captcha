<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Config\Source\Filter;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Config\Source\Filter\Field;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    /** @var FieldProviderInterface&MockObject */
    private FieldProviderInterface $fieldProvider;
    private Field $source;

    protected function setUp(): void
    {
        $this->fieldProvider = $this->createMock(FieldProviderInterface::class);
        $this->source = new Field($this->fieldProvider);
    }

    public function testToOptionArrayMapsRegisteredFields(): void
    {
        $this->fieldProvider->method('getAll')->willReturn([
            'uri_path' => $this->makeField('uri_path', 'URI Path'),
            'client_ip' => $this->makeField('client_ip', 'Client IP'),
        ]);

        $options = $this->source->toOptionArray();

        $this->assertCount(2, $options);
        $this->assertSame('uri_path', $options[0]['value']);
        $this->assertSame('URI Path', (string)$options[0]['label']);
        $this->assertSame('client_ip', $options[1]['value']);
        $this->assertSame('Client IP', (string)$options[1]['label']);
    }

    public function testToOptionArrayWithNoFields(): void
    {
        $this->fieldProvider->method('getAll')->willReturn([]);

        $this->assertSame([], $this->source->toOptionArray());
    }

    /**
     * @return FieldInterface&MockObject
     */
    private function makeField(string $code, string $label): FieldInterface
    {
        $field = $this->createMock(FieldInterface::class);
        $field->method('getCode')->willReturn($code);
        $field->method('getLabel')->willReturn(__($label));

        return $field;
    }
}
