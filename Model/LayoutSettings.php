<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;

/**
 * Class LayoutSettings
 */
class LayoutSettings
{
    /**
     * @var General
     */
    private $config;

    /**
     * LayoutSettings constructor.
     *
     * @param General $config
     */
    public function __construct(
        General $config
    ) {
        $this->config = $config;
    }

    /**
     * Return captcha config for frontend
     * @return array
     */
    public function getCaptchaSettings()
    {
        return ['siteKey' => $this->config->getSiteKey()];
    }
}
