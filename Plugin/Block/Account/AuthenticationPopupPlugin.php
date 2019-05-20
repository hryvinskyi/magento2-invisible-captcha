<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

namespace Hryvinskyi\InvisibleCaptcha\Plugin\Block\Account;

use Hryvinskyi\Base\Helper\Json;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\Frontend;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\LayoutSettings;
use Magento\Customer\Block\Account\AuthenticationPopup;

class AuthenticationPopupPlugin
{

    /**
     * @var LayoutSettings
     */
    private $layoutSettings;

    /**
     * @var General
     */
    private $generalConfig;

    /**
     * @var Frontend
     */
    private $frontendConfig;

    /**
     * AuthenticationPopupPlugin constructor.
     *
     * @param LayoutSettings $layoutSettings
     * @param General $generalConfig
     * @param Frontend $frontendConfig
     */
    public function __construct(
        LayoutSettings $layoutSettings,
        General $generalConfig,
        Frontend $frontendConfig
    ) {
        $this->layoutSettings = $layoutSettings;
        $this->generalConfig = $generalConfig;
        $this->frontendConfig = $frontendConfig;
    }

    /**
     * @param AuthenticationPopup $subject
     * @param string $result
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetJsLayout(AuthenticationPopup $subject, $result)
    {
        $layout = Json::decode($result);
        $layout['components']['authenticationPopup']['children']['invisible-captcha']['config']
            = $this->layoutSettings->getCaptchaSettings();

        if (
            (
                !$this->generalConfig->hasEnabled()
                || !$this->frontendConfig->hasEnabled()
                || !$this->frontendConfig->hasEnabledCustomerLogin()
            )
            && isset($layout['components']['authenticationPopup']['children']['invisible-captcha'])
        ) {
            unset($layout['components']['authenticationPopup']['children']['invisible-captcha']);
        }

        return Json::encode($layout);
    }
}
