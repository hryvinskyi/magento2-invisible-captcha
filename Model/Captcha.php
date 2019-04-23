<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

/**
 * Class Captcha
 */
class Captcha implements CaptchaInterface
{
    /**
     * @var null
     */
    private $url;

    /**
     * @var CheckEnabledVerifyInterface|null
     */
    private $checkEnabledVerify;

    /**
     * Captcha constructor.
     *
     * @param string|null $url
     * @param CheckEnabledVerifyInterface|null $checkEnabledVerify
     */
    public function __construct(
        ?string $url,
        ?CheckEnabledVerifyInterface $checkEnabledVerify
    ) {
        $this->url = $url;
        $this->checkEnabledVerify = $checkEnabledVerify;
    }

    /**
     * @inheritdoc
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @inheritdoc
     */
    public function setUrl(string $url): CaptchaInterface
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isEnabled(string $url): bool
    {
        return strpos($url, $this->getUrl()) !== false
            && (!$this->checkEnabledVerify || $this->checkEnabledVerify->verify());
    }
}