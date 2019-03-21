<?php
/**
 * Copyright (c) 2019. Volodumur Hryvinskyi.  All rights reserved.
 * @author: <mailto:volodumur@hryvinskyi.com>
 * @github: <https://github.com/scriptua>
 */

declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    /**
     * Configuration path
     */
    const CONFIG_PATH_GENERAL_ENABLE_MODULE = 'hryvinskyi_invisible_captcha/general/enabledCaptcha';
    const CONFIG_PATH_GENERAL_SITE_KEY = 'hryvinskyi_invisible_captcha/general/captchaSiteKey';
    const CONFIG_PATH_GENERAL_SECRET_KEY = 'hryvinskyi_invisible_captcha/general/captchaSecretKey';
    const CONFIG_PATH_GENERAL_ALLOWED_URLS = 'hryvinskyi_invisible_captcha/general/captchaUrls';
    const CONFIG_PATH_DISPLAY_ALLOWED_SELECTORS = 'hryvinskyi_invisible_captcha/general/captchaSelectors';


    /**
     * @param $string
     *
     * @return mixed
     */
    public function stringValidationAndCovertInArray($string)
    {
        $validate = function ($urls) {
            return preg_split('|\s*[\r\n]+\s*|', $urls, -1, PREG_SPLIT_NO_EMPTY);
        };

        return $validate($string);
    }

    /**
     * Is google recaptcha enable
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
        string $scopeType = ScopeInterface::SCOPE_WEBSITE,
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
        string $scopeType = ScopeInterface::SCOPE_WEBSITE,
        $scopeCode = null
    ): string {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_GENERAL_SECRET_KEY,
            $scopeType,
            $scopeCode
        );
    }

    /**
     * @param $path
     * @param null $storeId
     * @param string $scope
     *
     * @return mixed
     */
    public function getConfigValueByPath(
        $path,
        $storeId = null,
        $scope = ScopeInterface::SCOPE_WEBSITE
    ) {
        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }

    /**
     * @param string $scopeType
     * @param null $scopeCode
     *
     * @return array
     */
    public function getCaptchaUrls(
        string $scopeType = ScopeInterface::SCOPE_WEBSITE,
        $scopeCode = null
    ): array {
        $urls = $this->scopeConfig->getValue(
            self::CONFIG_PATH_GENERAL_ALLOWED_URLS,
            $scopeType,
            $scopeCode
        ) ?: '';
        $urls = trim($urls);

        return $urls ? $this->stringValidationAndCovertInArray($urls) : [];
    }

    /**
     * @param string $scopeType
     * @param null $scopeCode
     *
     * @return array
     */
    public function getCaptchaSelectors(
        string $scopeType = ScopeInterface::SCOPE_WEBSITE,
        $scopeCode = null
    ): array {
        $urls = $this->scopeConfig->getValue(
            self::CONFIG_PATH_DISPLAY_ALLOWED_SELECTORS,
            $scopeType,
            $scopeCode
        ) ?: '';
        $urls = trim($urls);

        return $urls ? $this->stringValidationAndCovertInArray($urls) : [];
    }
}
