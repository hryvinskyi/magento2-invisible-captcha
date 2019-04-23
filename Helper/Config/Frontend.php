<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Helper\Config;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Frontend
 */
class Frontend extends AbstractHelper
{
    /**
     * Configuration path
     */
    const CONFIG_PATH_FRONTEND_ENABLED = 'hryvinskyi_invisible_captcha/frontend/enabled';
    const CONFIG_PATH_FRONTEND_ENABLED_CUSTOMER_LOGIN = 'hryvinskyi_invisible_captcha/frontend/enabledCustomerLogin';

    /**
     * Is google recaptcha enable global
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnable(
        string $scopeType = ScopeInterface::SCOPE_WEBSITE,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_FRONTEND_ENABLED,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha enable customer login
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnableCustomerLogin(
        string $scopeType = ScopeInterface::SCOPE_WEBSITE,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_FRONTEND_ENABLED_CUSTOMER_LOGIN,
            $scopeType,
            $scopeCode
        );
    }
}