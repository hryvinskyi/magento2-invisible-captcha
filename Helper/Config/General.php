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
 * Class General
 */
class General extends AbstractConfig
{
    /**
     * Configuration path
     */
    const CONFIG_PATH_GENERAL_ENABLE_MODULE = 'hryvinskyi_invisible_captcha/general/enabledCaptcha';
    const CONFIG_PATH_GENERAL_SITE_KEY = 'hryvinskyi_invisible_captcha/general/captchaSiteKey';
    const CONFIG_PATH_GENERAL_SECRET_KEY = 'hryvinskyi_invisible_captcha/general/captchaSecretKey';
    const CONFIG_PATH_GENERAL_USE_LAZY_LOAD = 'hryvinskyi_invisible_captcha/general/useLazyLoad';
    const CONFIG_PATH_GENERAL_DISABLE_SUBMIT_FORM = 'hryvinskyi_invisible_captcha/general/disableSubmitForm';
    const CONFIG_PATH_GENERAL_HIDE_BADGE = 'hryvinskyi_invisible_captcha/general/hideBadge';
    const CONFIG_PATH_GENERAL_HIDE_BADGE_TEXT = 'hryvinskyi_invisible_captcha/general/hideBadgeText';
    const CONFIG_PATH_GENERAL_DEBUG = 'hryvinskyi_invisible_captcha/general/debug';

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
            self::CONFIG_PATH_GENERAL_ENABLE_MODULE,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Return Captcha Key
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return string
     */
    public function getSiteKey(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): string {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_GENERAL_SITE_KEY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Return Secret Key
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return string
     */
    public function getSecretKey(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): string {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_GENERAL_SECRET_KEY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha load lazy
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function isLazyLoad(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_GENERAL_USE_LAZY_LOAD,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is google recaptcha load lazy
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function isDisabledSubmitForm(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): bool {
        return $this->scopeConfig->isSetFlag(
            self::CONFIG_PATH_GENERAL_DISABLE_SUBMIT_FORM,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is hide badge
     *
     * @return bool
     */
    public function isHideBadge(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_GENERAL_HIDE_BADGE);
    }

    /**
     * Return hide badge text
     *
     * @param string $scopeType
     * @param mixed $scopeCode
     *
     * @return bool
     */
    public function getHideBadgeText(
        string $scopeType = ScopeInterface::SCOPE_WEBSITES,
        $scopeCode = null
    ): string {
        return (string)$this->scopeConfig->getValue(
            self::CONFIG_PATH_GENERAL_HIDE_BADGE_TEXT,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * Is enabled debugging
     *
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CONFIG_PATH_GENERAL_DEBUG);
    }

    /**
     * Get config by path
     *
     * @param string $path
     * @param mixed $storeId
     * @param string $scope
     *
     * @return mixed
     */
    public function getConfigValueByPath(
        $path,
        $storeId = null,
        $scope = ScopeInterface::SCOPE_WEBSITES
    ) {
        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }

    /**
     * Disable config path
     *
     * @return string
     */
    public function disableConfigPath(): string
    {
        return self::CONFIG_PATH_GENERAL_ENABLE_MODULE;
    }
}
