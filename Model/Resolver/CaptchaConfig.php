<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Resolver;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Exposes the active captcha provider's client config to headless clients so
 * they can render the widget and submit the token via the X-Captcha-Token header.
 */
class CaptchaConfig implements ResolverInterface
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
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $formType = (string)($args['formType'] ?? '');

        if (!$this->config->isEnabled()) {
            return ['is_enabled' => false];
        }

        $provider = $this->providerPool->getActive();
        $render = $provider->getRenderConfig();

        $isEnabled = true;
        if ($formType !== '') {
            $isEnabled = $this->config->isFormProtectionEnabled() && $this->config->isFormEnabled($formType);
        }

        return [
            'is_enabled' => $isEnabled,
            'provider' => $provider->getCode(),
            'site_key' => $provider->getSiteKey(),
            'response_param' => $provider->getResponseParamName(),
            'script_url' => $provider->getClientScriptUrl(),
            'is_score_based' => $provider->isScoreBased(),
            'action' => $formType !== '' ? $formType : null,
            'score_threshold' => ($formType !== '' && $provider->isScoreBased())
                ? $this->config->getFormScoreThreshold($formType)
                : null,
        ];
    }
}
