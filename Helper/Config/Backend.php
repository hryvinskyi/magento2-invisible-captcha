<?php
/**
 * Copyright (c) 2019. Volodymyr Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodymyr@hryvinskyi.com>
 * @github: <https://github.com/hryvinskyi>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Helper\Config;

use Hryvinskyi\InvisibleCaptcha\Helper\AbstractConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Backend
 */
class Backend extends AbstractConfig
{
    /**
     * Configuration path
     */
    const CONFIG_PATH_BACKEND_ENABLED = 'hryvinskyi_invisible_captcha/backend/enabled';
    const CONFIG_PATH_BACKEND_ENABLED_LOGIN = 'hryvinskyi_invisible_captcha/backend/enabledLogin';
    const CONFIG_PATH_BACKEND_SCORE_THRESHOLD_LOGIN = 'hryvinskyi_invisible_captcha/backend/scoreThresholdLogin';
    const CONFIG_PATH_BACKEND_ENABLED_FORGOT = 'hryvinskyi_invisible_captcha/backend/enabledForgot';
    const CONFIG_PATH_BACKEND_SCORE_THRESHOLD_FORGOT = 'hryvinskyi_invisible_captcha/backend/scoreThresholdForgot';

    /**
     * Is google recaptcha enable backend
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnabled(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_BACKEND_ENABLED,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha enable login
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnabledLogin(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_BACKEND_ENABLED_LOGIN,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Get google recaptcha score threshold login
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return float
     */
    public function getScoreThresholdLogin(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): float {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_BACKEND_SCORE_THRESHOLD_LOGIN,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha enable forgot
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnabledForgot(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_BACKEND_ENABLED_FORGOT,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Get google recaptcha score threshold forgot
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return float
     */
    public function getScoreThresholdForgot(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): float {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_BACKEND_SCORE_THRESHOLD_FORGOT,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Disable config path
     *
     * @return string
     */
    public function disableConfigPath(): string
    {
        return self::CONFIG_PATH_BACKEND_ENABLED;
    }
}
