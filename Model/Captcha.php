<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\CaptchaInterface;
use Hryvinskyi\InvisibleCaptcha\Api\EnablementInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ScoreThresholdInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Strategy\FailureStrategyInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Strategy\TokenStrategyInterface;

/**
 * Per-form captcha descriptor. One DI virtualType is configured per protected
 * form, binding the action, token strategy, score threshold, failure strategy
 * and enablement gate.
 */
class Captcha implements CaptchaInterface
{
    /**
     * @param TokenStrategyInterface $tokenStrategy
     * @param FailureStrategyInterface $failureProvider
     * @param EnablementInterface $enablement
     * @param ScoreThresholdInterface|null $scoreThreshold
     * @param string|null $action
     */
    public function __construct(
        private readonly TokenStrategyInterface $tokenStrategy,
        private readonly FailureStrategyInterface $failureProvider,
        private readonly EnablementInterface $enablement,
        private readonly ?ScoreThresholdInterface $scoreThreshold = null,
        private readonly ?string $action = null
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @inheritDoc
     */
    public function getToken(): ?string
    {
        return $this->tokenStrategy->getToken();
    }

    /**
     * @inheritDoc
     */
    public function getScoreThreshold(): ?float
    {
        return $this->scoreThreshold?->getValue();
    }

    /**
     * @inheritDoc
     */
    public function getFailure(): FailureStrategyInterface
    {
        return $this->failureProvider;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->enablement->isEnabled();
    }
}
