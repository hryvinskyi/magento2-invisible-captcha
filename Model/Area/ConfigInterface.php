<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Area;

interface ConfigInterface
{
    /**
     * Disable config path
     *
     * @return string
     */
    public function disableConfigPath(): string;

    /**
     * Disable captcha by area
     *
     * @param null|string $website
     */
    public function disableCaptcha(string $website = null): void;
}