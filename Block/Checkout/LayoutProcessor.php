<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Block\Checkout;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ClientConfigProvider;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

/**
 * Injects the invisible-captcha config into the checkout login (customer-email
 * and authentication) jsLayout, or removes it when not enabled.
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    /**
     * @param ClientConfigProvider $clientConfigProvider
     * @param ConfigInterface $config
     */
    public function __construct(
        private readonly ClientConfigProvider $clientConfigProvider,
        private readonly ConfigInterface $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function process($jsLayout)
    {
        $settings = $this->clientConfigProvider->getFormConfig();

        $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
        ['shippingAddress']['children']['customer-email']['children']['invisible-captcha']['config'] = $settings;

        $jsLayout['components']['checkout']['children']['authentication']['children']['invisible-captcha']['config']
            = $settings;

        if ($this->isLoginCaptchaEnabled()) {
            return $jsLayout;
        }

        unset(
            $jsLayout['components']['checkout']['children']['authentication']['children']['invisible-captcha'],
            $jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']['children']
            ['shippingAddress']['children']['customer-email']['children']['invisible-captcha']
        );

        return $jsLayout;
    }

    /**
     * Whether captcha protection for customer login is active.
     */
    private function isLoginCaptchaEnabled(): bool
    {
        return $this->config->isEnabled()
            && $this->config->isFormProtectionEnabled()
            && $this->config->isFormAreaEnabled(ConfigInterface::AREA_FRONTEND)
            && $this->config->isFormEnabled(ConfigInterface::FORM_CUSTOMER_LOGIN);
    }
}
