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
 * Class Frontend
 */
class Frontend extends AbstractConfig
{
    /**
     * Configuration path
     */
    const CONFIG_PATH_FRONTEND_ENABLED = 'hryvinskyi_invisible_captcha/frontend/enabled';
    const CONFIG_PATH_FRONTEND_ENABLED_CUSTOMER_LOGIN = 'hryvinskyi_invisible_captcha/frontend/enabledCustomerLogin';
    const CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_CUSTOMER_LOGIN = 'hryvinskyi_invisible_captcha/frontend/scoreThresholdCustomerLogin';
    const CONFIG_PATH_FRONTEND_ENABLED_CUSTOMER_CREATE = 'hryvinskyi_invisible_captcha/frontend/enabledCustomerCreate';
    const CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_CUSTOMER_CREATE = 'hryvinskyi_invisible_captcha/frontend/scoreThresholdCustomerCreate';
    const CONFIG_PATH_FRONTEND_ENABLED_CUSTOMER_FORGOT = 'hryvinskyi_invisible_captcha/frontend/enabledCustomerForgot';
    const CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_CUSTOMER_FORGOT = 'hryvinskyi_invisible_captcha/frontend/scoreThresholdCustomerForgot';
    const CONFIG_PATH_FRONTEND_ENABLED_CONTACT = 'hryvinskyi_invisible_captcha/frontend/enabledContact';
    const CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_CONTACT = 'hryvinskyi_invisible_captcha/frontend/scoreThresholdContact';
    const CONFIG_PATH_FRONTEND_ENABLED_NEWSLETTER = 'hryvinskyi_invisible_captcha/frontend/enabledNewsletter';
    const CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_NEWSLETTER = 'hryvinskyi_invisible_captcha/frontend/scoreThresholdNewsletter';
    const CONFIG_PATH_FRONTEND_ENABLED_SENDFRIEND = 'hryvinskyi_invisible_captcha/frontend/enabledSendFriend';
    const CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_SENDFRIEND = 'hryvinskyi_invisible_captcha/frontend/scoreThresholdSendFriend';

    /**
     * Is google recaptcha enable global
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
    public function hasEnabledCustomerLogin(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_FRONTEND_ENABLED_CUSTOMER_LOGIN,
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
    public function getScoreThresholdCustomerLogin(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): float {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_CUSTOMER_LOGIN,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha enable customer create
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnabledCustomerCreate(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_FRONTEND_ENABLED_CUSTOMER_CREATE,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Get google recaptcha score threshold customer create
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return float
     */
    public function getScoreThresholdCustomerCreate(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): float {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_CUSTOMER_CREATE,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha enable customer forgot
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnabledCustomerForgot(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_FRONTEND_ENABLED_CUSTOMER_FORGOT,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Get google recaptcha score threshold customer forgot
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return float
     */
    public function getScoreThresholdCustomerForgot(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): float {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_CUSTOMER_FORGOT,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha enable contact
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnabledContact(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_FRONTEND_ENABLED_CONTACT,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Get google recaptcha score threshold contact
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return float
     */
    public function getScoreThresholdContact(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): float {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_CONTACT,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha enable newsletter
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnabledNewsletter(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_FRONTEND_ENABLED_NEWSLETTER,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Get google recaptcha score threshold newsletter
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return float
     */
    public function getScoreThresholdNewsletter(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): float {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_NEWSLETTER,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha enable send to friend
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function hasEnabledSendFriend(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_FRONTEND_ENABLED_SENDFRIEND,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Get google recaptcha score threshold send to friend
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return float
     */
    public function getScoreThresholdSendFriend(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): float {
        return (float)$this->scopeConfig->getValue(
            self::CONFIG_PATH_FRONTEND_SCORE_THRESHOLD_SENDFRIEND,
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
        return self::CONFIG_PATH_FRONTEND_ENABLED;
    }
}
