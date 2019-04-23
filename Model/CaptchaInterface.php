<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

/**
 * Class AbstractCaptcha
 */
interface CaptchaInterface
{
    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @param string $url
     *
     * @return CaptchaInterface
     */
    public function setUrl(string $url): CaptchaInterface;

    /**
     * @param string $url
     *
     * @return bool
     */
    public function isEnabled(string $url): bool;
}