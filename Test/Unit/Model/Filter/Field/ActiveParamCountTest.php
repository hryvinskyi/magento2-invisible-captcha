<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\ActiveParamCount;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ActiveParamCountTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;
    private ActiveParamCount $field;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->config = $this->createMock(ConfigInterface::class);
        $this->field = new ActiveParamCount($this->request, $this->config);
    }

    public function testMetadata(): void
    {
        $this->assertSame('active_param_count', $this->field->getCode());
        $this->assertSame('Active Param Count (non-ignored)', (string)$this->field->getLabel());
        $this->assertSame(FieldInterface::TYPE_NUMERIC, $this->field->getType());
    }

    public function testCountsOnlyMeaningfulNonIgnoredParams(): void
    {
        $this->config->method('getLayeredNavIgnoredParams')->willReturn(['p', 'form_key']);
        $this->request->method('getParams')->willReturn([
            'p' => '2',              // ignored
            'form_key' => 'abc',     // ignored
            'brand' => 'acme',       // counts
            'color' => 'red',        // counts
            'empty_str' => '',       // skipped
            'null_v' => null,        // skipped
            'empty_arr' => [],       // skipped
            'flag' => 1,             // counts
        ]);

        $this->assertSame(3, $this->field->getValue());
    }

    public function testZeroWhenAllParamsIgnoredOrEmpty(): void
    {
        $this->config->method('getLayeredNavIgnoredParams')->willReturn(['p']);
        $this->request->method('getParams')->willReturn([
            'p' => '2',
            'empty' => '',
        ]);

        $this->assertSame(0, $this->field->getValue());
    }

    public function testZeroWhenNoParams(): void
    {
        $this->config->method('getLayeredNavIgnoredParams')->willReturn([]);
        $this->request->method('getParams')->willReturn([]);
        $this->assertSame(0, $this->field->getValue());
    }
}
