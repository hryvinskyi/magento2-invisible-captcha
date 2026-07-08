<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Strategy\Token\RequestParam;

/**
 * Builds the client-side captcha configuration consumed by the form JS
 * (jsLayout) — provider identity, site key, script URL, response param, widget
 * options and global flags. Replaces the legacy reCAPTCHA-only LayoutSettings.
 */
class ClientConfigProvider
{
    /**
     * @param ConfigInterface $config
     * @param ProviderPoolInterface $providerPool
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly ProviderPoolInterface $providerPool
    ) {
    }

    /**
     * Client config for form-level widgets.
     *
     * @param array<string, mixed> $context Optional context (e.g. ['action' => '...']).
     * @return array<string, mixed>
     */
    public function getFormConfig(?string $scopeCode = null, array $context = []): array
    {
        if (!$this->config->isEnabled($scopeCode)) {
            return [];
        }

        $provider = $this->providerPool->getActive($scopeCode);

        return $provider->getRenderConfig($scopeCode, $context) + [
            'lazyLoad' => $this->config->isLazyLoad($scopeCode),
            'isDisabledSubmitForm' => $this->config->isDisableSubmitForm($scopeCode),
            'tokenField' => RequestParam::DEFAULT_FIELD,
        ];
    }
}
