<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads per-provider credentials and widget options from
 * `hryvinskyi_invisible_captcha/providers/<code>/*`.
 */
class ProviderConfig implements ProviderConfigInterface
{
    private const PATH_PREFIX = 'hryvinskyi_invisible_captcha/providers/';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getSiteKey(string $providerCode, ?string $scopeCode = null): string
    {
        return (string)($this->getWidgetOption($providerCode, 'site_key', $scopeCode) ?? '');
    }

    /**
     * @inheritDoc
     */
    public function getSecretKey(string $providerCode, ?string $scopeCode = null): string
    {
        $encrypted = (string)($this->getWidgetOption($providerCode, 'secret_key', $scopeCode) ?? '');

        return $encrypted !== '' ? (string)$this->encryptor->decrypt($encrypted) : '';
    }

    /**
     * @inheritDoc
     */
    public function getProjectId(string $providerCode, ?string $scopeCode = null): string
    {
        return (string)($this->getWidgetOption($providerCode, 'project_id', $scopeCode) ?? '');
    }

    /**
     * @inheritDoc
     */
    public function getWidgetOption(string $providerCode, string $key, ?string $scopeCode = null): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::PATH_PREFIX . $providerCode . '/' . $key,
            ScopeInterface::SCOPE_STORE,
            $scopeCode
        );

        return $value === null ? null : (string)$value;
    }
}
