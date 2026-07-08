<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Enablement;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\EnablementInterface;

/**
 * Generic per-form enablement gate. One DI virtualType per protected form binds
 * an area + form key. Replaces the legacy per-class Verify\* hierarchy.
 */
class FormEnablement implements EnablementInterface
{
    /**
     * @param ConfigInterface $config
     * @param string $area One of ConfigInterface::AREA_FRONTEND | AREA_ADMINHTML.
     * @param string $form One of ConfigInterface::FORM_*.
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly string $area,
        private readonly string $form
    ) {
        if (!in_array($area, [ConfigInterface::AREA_FRONTEND, ConfigInterface::AREA_ADMINHTML], true)) {
            throw new \InvalidArgumentException('Area must be one of frontend or adminhtml.');
        }
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(?string $scopeCode = null): bool
    {
        return $this->config->isEnabled($scopeCode)
            && $this->config->isFormProtectionEnabled($scopeCode)
            && $this->config->isFormAreaEnabled($this->area, $scopeCode)
            && $this->config->isFormEnabled($this->form, $scopeCode);
    }
}
