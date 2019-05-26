<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Plugin\Block\Checkout;

use Hryvinskyi\Base\Helper\Json;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\Frontend;
use Hryvinskyi\InvisibleCaptcha\Helper\Config\General;
use Hryvinskyi\InvisibleCaptcha\Model\LayoutSettings;
use Magento\Checkout\Block\Onepage;

/**
 * Class OnepagePlugin
 */
class OnepagePlugin
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
     * @param Onepage $subject
     * @param string $result
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetJsLayout(Onepage $subject, $result)
    {
        $layout = Json::decode($result);

        $layout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['customer-email']['children']['invisible-captcha']['config']
            = $this->layoutSettings->getCaptchaSettings();

        $layout['components']['checkout']['children']['authentication']['children']['invisible-captcha']['config']
            = $this->layoutSettings->getCaptchaSettings();

        if (!$this->generalConfig->hasEnabled()
            || !$this->frontendConfig->hasEnabled()
            || !$this->frontendConfig->hasEnabledCustomerLogin()
        ) {
            if (isset($layout['components']['checkout']['children']['authentication']['children']['invisible-captcha'])) {
                unset($layout['components']['checkout']['children']['authentication']['children']['invisible-captcha']);
            }

            if (isset($layout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                ['shippingAddress']['children']['customer-email']['children']['invisible-captcha'])) {
                unset($layout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                    ['shippingAddress']['children']['customer-email']['children']['invisible-captcha']);
            }
        }

        return Json::encode($layout);
    }
}
