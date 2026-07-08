<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Plugin\Block\Account;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Model\ClientConfigProvider;
use Magento\Customer\Block\Account\AuthenticationPopup;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Injects the invisible-captcha config into the authentication popup jsLayout,
 * or removes it when customer-login protection is disabled.
 */
class AuthenticationPopupPlugin
{
    /**
     * @param ClientConfigProvider $clientConfigProvider
     * @param ConfigInterface $config
     * @param Json $json
     */
    public function __construct(
        private readonly ClientConfigProvider $clientConfigProvider,
        private readonly ConfigInterface $config,
        private readonly Json $json
    ) {
    }

    /**
     * @param AuthenticationPopup $subject
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetJsLayout(AuthenticationPopup $subject, $result)
    {
        $layout = $this->json->unserialize($result);

        $layout['components']['authenticationPopup']['children']['invisible-captcha']['config']
            = $this->clientConfigProvider->getFormConfig();

        if (!$this->isLoginCaptchaEnabled()
            && isset($layout['components']['authenticationPopup']['children']['invisible-captcha'])
        ) {
            unset($layout['components']['authenticationPopup']['children']['invisible-captcha']);
        }

        return $this->json->serialize($layout);
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
