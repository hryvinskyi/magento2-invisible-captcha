<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;

/**
 * Issues and validates the HMAC-signed route-gate verification cookie.
 *
 * The HMAC is keyed on the Magento crypt key (provider-independent) so switching
 * captcha providers does not invalidate already-verified sessions.
 */
class CookieManager
{
    public const COOKIE_NAME = 'hryvinskyi_captcha_verified';

    private ?string $hmacKey = null;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param ConfigInterface $config
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly ConfigInterface $config,
        private readonly DeploymentConfig $deploymentConfig
    ) {
    }

    /**
     * Check whether the request carries a valid, unexpired verification cookie.
     */
    public function isVerified(): bool
    {
        $value = (string)$this->cookieManager->getCookie(self::COOKIE_NAME, '');
        if ($value === '') {
            return false;
        }

        $parts = explode(':', $value, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$timestamp, $hmac] = $parts;
        if (!ctype_digit($timestamp) || (int)$timestamp < time()) {
            return false;
        }

        return hash_equals($this->generateHmac((int)$timestamp), $hmac);
    }

    /**
     * Issue a fresh HMAC-signed verification cookie.
     *
     * @throws CookieSizeLimitReachedException
     * @throws FailureToSendException
     * @throws InputException
     */
    public function setVerified(): void
    {
        $lifetime = $this->config->getCookieLifetime();
        $expiry = time() + $lifetime;
        $value = $expiry . ':' . $this->generateHmac($expiry);

        /** @var PublicCookieMetadata $metadata */
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $metadata->setDuration($lifetime);
        $metadata->setPath('/');
        $metadata->setHttpOnly(true);
        $metadata->setSecure(true);
        $metadata->setSameSite('Strict');

        $this->cookieManager->setPublicCookie(self::COOKIE_NAME, $value, $metadata);
    }

    /**
     * Remove the verification cookie from the client.
     *
     * @throws FailureToSendException
     * @throws InputException
     */
    public function deleteCookie(): void
    {
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $metadata->setPath('/');

        $this->cookieManager->deleteCookie(self::COOKIE_NAME, $metadata);
    }

    /**
     * Build the HMAC signature for a given expiry timestamp.
     */
    private function generateHmac(int $timestamp): string
    {
        return hash_hmac('sha256', (string)$timestamp, $this->getHmacKey());
    }

    /**
     * Resolve a stable, provider-independent signing key from the crypt key.
     */
    private function getHmacKey(): string
    {
        if ($this->hmacKey === null) {
            $cryptKey = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY);
            // crypt/key may contain multiple newline-separated keys; the latest is canonical.
            $keys = array_filter(explode("\n", $cryptKey));
            $this->hmacKey = $keys !== [] ? (string)end($keys) : 'hryvinskyi-invisible-captcha';
        }

        return $this->hmacKey;
    }
}
