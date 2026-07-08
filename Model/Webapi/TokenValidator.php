<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Webapi;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

/**
 * Validates a captcha token submitted with a WebAPI/GraphQL request, using the
 * active provider. The form key doubles as the expected action for score-based
 * providers, so the client must execute the provider with that action name.
 */
class TokenValidator
{
    /**
     * @param ProviderPoolInterface $providerPool
     * @param ConfigInterface $config
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(
        private readonly ProviderPoolInterface $providerPool,
        private readonly ConfigInterface $config,
        private readonly RemoteAddress $remoteAddress
    ) {
    }

    /**
     * @param string $formKey Captcha form key (see ConfigInterface::FORM_*).
     * @param string $token Token submitted by the client.
     */
    public function isValid(string $formKey, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $provider = $this->providerPool->getActive();

        $request = $provider->createVerificationRequest()
            ->setResponse($token)
            ->setRemoteIp($this->remoteAddress->getRemoteAddress() ?: null);

        if ($provider->supportsAction()) {
            $request->setExpectedAction($formKey);
        }

        if ($provider->isScoreBased()) {
            $request->setScoreThreshold($this->config->getFormScoreThreshold($formKey));
        }

        return $provider->getVerifier()->verify($request)->isSuccess();
    }
}
