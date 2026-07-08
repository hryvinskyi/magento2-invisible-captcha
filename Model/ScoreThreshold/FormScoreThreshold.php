<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\ScoreThreshold;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ScoreThresholdInterface;

/**
 * Generic per-form score threshold resolver. One DI virtualType per form binds
 * a form key. Replaces the legacy per-class ScoreThreshold\* hierarchy.
 */
class FormScoreThreshold implements ScoreThresholdInterface
{
    /**
     * @param ConfigInterface $config
     * @param string $form One of ConfigInterface::FORM_*.
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly string $form
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getValue(?string $scopeCode = null): float
    {
        return $this->config->getFormScoreThreshold($this->form, $scopeCode);
    }
}
