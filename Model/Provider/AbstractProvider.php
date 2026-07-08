<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerifierInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequestFactory;

/**
 * Shared provider behaviour: credentials, configured-state, verify-URL
 * resolution and request construction. Concrete providers declare their
 * code/label/metadata and render config.
 */
abstract class AbstractProvider implements ProviderInterface
{
    /** Default token refresh interval (ms) — overridden per provider. */
    protected const DEFAULT_TOKEN_TTL_MS = 110000;

    /** Default server-side verify endpoint — overridden per provider. */
    protected const DEFAULT_VERIFY_URL = '';

    /**
     * @param ProviderConfigInterface $providerConfig
     * @param VerifierInterface $verifier
     * @param VerificationRequestFactory $requestFactory
     */
    public function __construct(
        protected readonly ProviderConfigInterface $providerConfig,
        protected readonly VerifierInterface $verifier,
        protected readonly VerificationRequestFactory $requestFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getSiteKey(?string $scopeCode = null): string
    {
        return $this->providerConfig->getSiteKey($this->getCode(), $scopeCode);
    }

    /**
     * @inheritDoc
     */
    public function getSecretKey(?string $scopeCode = null): string
    {
        return $this->providerConfig->getSecretKey($this->getCode(), $scopeCode);
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(?string $scopeCode = null): bool
    {
        return $this->getSiteKey($scopeCode) !== '' && $this->getSecretKey($scopeCode) !== '';
    }

    /**
     * @inheritDoc
     */
    public function getVerifyUrl(?string $scopeCode = null): string
    {
        $override = (string)($this->providerConfig->getWidgetOption($this->getCode(), 'verify_url', $scopeCode) ?? '');

        return $override !== '' ? $override : static::DEFAULT_VERIFY_URL;
    }

    /**
     * @inheritDoc
     */
    public function getVerifier(): VerifierInterface
    {
        return $this->verifier;
    }

    /**
     * @inheritDoc
     */
    public function getTokenTtlMs(): int
    {
        return static::DEFAULT_TOKEN_TTL_MS;
    }

    /**
     * @inheritDoc
     */
    public function createVerificationRequest(?string $scopeCode = null): VerificationRequestInterface
    {
        return $this->requestFactory->create()
            ->setSecret($this->getSecretKey($scopeCode))
            ->setVerifyUrl($this->getVerifyUrl($scopeCode));
    }

    /**
     * Base render config shared by every provider.
     *
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    protected function baseRenderConfig(?string $scopeCode, array $context): array
    {
        $config = [
            'provider' => $this->getCode(),
            'siteKey' => $this->getSiteKey($scopeCode),
            'scriptUrl' => $this->getClientScriptUrl($scopeCode),
            'responseParam' => $this->getResponseParamName(),
            'tokenTtl' => $this->getTokenTtlMs(),
            'isScoreBased' => $this->isScoreBased(),
            'supportsAction' => $this->supportsAction(),
        ];

        if ($this->supportsAction() && isset($context['action']) && $context['action'] !== '') {
            $config['action'] = (string)$context['action'];
        }

        return $config;
    }
}
