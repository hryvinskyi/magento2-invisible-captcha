<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Model\Provider\FailureInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Provider\TokenResponseInterface;

/**
 * Class Captcha
 */
class Captcha implements CaptchaInterface
{
    /**
     * @var string|null
     */
    private $action;

    /**
     * @var TokenResponseInterface
     */
    private $tokenResponse;

    /**
     * @var FailureInterface
     */
    private $failureProvider;

    /**
     * @var CheckEnabledVerifyInterface|null
     */
    private $checkEnabledVerify;

    /**
     * @var ScoreThresholdInterface|null
     */
    private $scoreThreshold;

    /**
     * Captcha constructor.
     *
     * @param string $action
     * @param TokenResponseInterface $tokenResponse
     * @param FailureInterface $failureProvider
     * @param ScoreThresholdInterface|null $scoreThreshold
     * @param CheckEnabledVerifyInterface|null $checkEnabledVerify
     */
    public function __construct(
        string $action,
        TokenResponseInterface $tokenResponse,
        FailureInterface $failureProvider,
        ?ScoreThresholdInterface $scoreThreshold,
        ?CheckEnabledVerifyInterface $checkEnabledVerify
    ) {
        $this->action = $action;
        $this->tokenResponse = $tokenResponse;
        $this->failureProvider = $failureProvider;
        $this->scoreThreshold = $scoreThreshold;
        $this->checkEnabledVerify = $checkEnabledVerify;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->tokenResponse->getToken();
    }

    /**
     * @return float
     */
    public function getScoreThreshold(): float
    {
        return $this->scoreThreshold ? $this->scoreThreshold->getValue() : 0.5;
    }

    /**
     * @return FailureInterface
     */
    public function getFailure(): FailureInterface
    {
        return $this->failureProvider;
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(): bool
    {
        return !$this->checkEnabledVerify || $this->checkEnabledVerify->verify();
    }
}
