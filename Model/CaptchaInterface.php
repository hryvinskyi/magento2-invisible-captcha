<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Model\Provider\FailureInterface;

/**
 * Class AbstractCaptcha
 */
interface CaptchaInterface
{
    /**
     * @return string
     */
    public function getAction(): string;

    /**
     * @return string
     */
    public function getToken(): ?string;

    /**
     * @return FailureInterface
     */
    public function getFailure(): FailureInterface;

    /**
     * @return float
     */
    public function getScoreThreshold(): float;

    /**
     * @return bool
     */
    public function isEnabled(): bool;
}
