<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\ScoreThreshold;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ScoreThreshold\FormScoreThreshold;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormScoreThresholdTest extends TestCase
{
    /** @var ConfigInterface&MockObject */
    private ConfigInterface $config;

    protected function setUp(): void
    {
        $this->config = $this->createMock(ConfigInterface::class);
    }

    public function testGetValueDelegatesToConfigWithBoundForm(): void
    {
        $this->config->expects($this->once())
            ->method('getFormScoreThreshold')
            ->with(ConfigInterface::FORM_CUSTOMER_LOGIN, null)
            ->willReturn(0.7);

        $resolver = new FormScoreThreshold($this->config, ConfigInterface::FORM_CUSTOMER_LOGIN);

        $this->assertSame(0.7, $resolver->getValue());
    }

    public function testGetValuePassesScopeCode(): void
    {
        $this->config->expects($this->once())
            ->method('getFormScoreThreshold')
            ->with(ConfigInterface::FORM_CONTACT, 'store_de')
            ->willReturn(0.3);

        $resolver = new FormScoreThreshold($this->config, ConfigInterface::FORM_CONTACT);

        $this->assertSame(0.3, $resolver->getValue('store_de'));
    }
}
