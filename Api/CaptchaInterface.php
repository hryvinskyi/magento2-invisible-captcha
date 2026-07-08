<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\FailureStrategyInterface;

/**
 * Per-form descriptor consumed by the form-verify observer. One instance is
 * configured (via DI virtualType) per protected form, binding an action,
 * token strategy, score threshold, failure strategy and enablement gate.
 *
 * Provider-tolerant: action and score threshold are nullable and only used by
 * providers that support them.
 */
interface CaptchaInterface
{
    /**
     * Action name for score-based providers (null for v2 / Turnstile).
     */
    public function getAction(): ?string;

    /**
     * The submitted token, read via the configured token strategy.
     */
    public function getToken(): ?string;

    /**
     * Score threshold for this form (null when not score-based).
     */
    public function getScoreThreshold(): ?float;

    /**
     * The failure strategy to invoke when verification fails.
     */
    public function getFailure(): FailureStrategyInterface;

    /**
     * Whether protection is currently active for this form.
     */
    public function isEnabled(): bool;
}
