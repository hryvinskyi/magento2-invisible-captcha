<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Provider;

use Hryvinskyi\InvisibleCaptcha\Api\Verification\VerificationRequestInterface;
use Magento\Framework\Phrase;

/**
 * Google reCAPTCHA Enterprise — score-based, verified through the
 * `projects/{projectId}/assessments` REST API using a Google API key.
 *
 * The provider's "secret key" field stores the Google API key; the verify URL is
 * built from the project id and API key.
 */
class RecaptchaEnterprise extends AbstractProvider
{
    private const ASSESSMENTS_URL = 'https://recaptchaenterprise.googleapis.com/v1/projects/%s/assessments?key=%s';
    protected const DEFAULT_TOKEN_TTL_MS = 90000;

    public function getCode(): string
    {
        return self::CODE_RECAPTCHA_ENTERPRISE;
    }

    public function getLabel(): Phrase
    {
        return __('Google reCAPTCHA Enterprise (score-based)');
    }

    public function isScoreBased(): bool
    {
        return true;
    }

    public function supportsAction(): bool
    {
        return true;
    }

    public function getResponseParamName(): string
    {
        return 'g-recaptcha-response';
    }

    public function getClientScriptUrl(?string $scopeCode = null): string
    {
        return 'https://www.google.com/recaptcha/enterprise.js';
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(?string $scopeCode = null): bool
    {
        return parent::isConfigured($scopeCode)
            && $this->providerConfig->getProjectId($this->getCode(), $scopeCode) !== '';
    }

    /**
     * @inheritDoc
     */
    public function getVerifyUrl(?string $scopeCode = null): string
    {
        $override = (string)($this->providerConfig->getWidgetOption($this->getCode(), 'verify_url', $scopeCode) ?? '');
        if ($override !== '') {
            return $override;
        }

        return sprintf(
            self::ASSESSMENTS_URL,
            rawurlencode($this->providerConfig->getProjectId($this->getCode(), $scopeCode)),
            rawurlencode($this->getSecretKey($scopeCode))
        );
    }

    /**
     * @inheritDoc
     */
    public function createVerificationRequest(?string $scopeCode = null): VerificationRequestInterface
    {
        return parent::createVerificationRequest($scopeCode)->setExtra([
            'siteKey' => $this->getSiteKey($scopeCode),
            'projectId' => $this->providerConfig->getProjectId($this->getCode(), $scopeCode),
        ]);
    }

    public function getRenderConfig(?string $scopeCode = null, array $context = []): array
    {
        return $this->baseRenderConfig($scopeCode, $context) + [
            'badge' => 'bottomright',
            'widgetMode' => 'score',
            'enterprise' => true,
        ];
    }
}
