<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Observer;

use Hryvinskyi\InvisibleCaptcha\Api\CaptchaInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Provider\ProviderPoolInterface;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

/**
 * Provider-agnostic form verification observer. One DI virtualType per protected
 * form binds a {@see CaptchaInterface} descriptor. On a non-GET request to a
 * protected form, it verifies the token with the active provider and delegates
 * to the form's failure strategy when verification fails.
 */
class FormVerify implements ObserverInterface
{
    /**
     * @param ProviderPoolInterface $providerPool
     * @param RemoteAddress $remoteAddress
     * @param HttpRequest $request
     * @param CaptchaInterface $provider Per-form captcha descriptor.
     */
    public function __construct(
        private readonly ProviderPoolInterface $providerPool,
        private readonly RemoteAddress $remoteAddress,
        private readonly HttpRequest $request,
        private readonly CaptchaInterface $provider
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        if (!$this->provider->isEnabled()) {
            return;
        }

        if (strcasecmp($this->request->getMethod(), 'GET') === 0) {
            return;
        }

        $captchaProvider = $this->providerPool->getActive();

        $request = $captchaProvider->createVerificationRequest()
            ->setResponse((string)$this->provider->getToken())
            ->setRemoteIp($this->remoteAddress->getRemoteAddress() ?: null);

        if ($captchaProvider->supportsAction() && $this->provider->getAction() !== null) {
            $request->setExpectedAction($this->provider->getAction());
        }

        if ($captchaProvider->isScoreBased() && $this->provider->getScoreThreshold() !== null) {
            $request->setScoreThreshold($this->provider->getScoreThreshold());
        }

        $result = $captchaProvider->getVerifier()->verify($request);

        if ($result->isSuccess()) {
            return;
        }

        /** @var AbstractAction|null $controller */
        $controller = $observer->getData('controller_action');
        $this->provider->getFailure()->execute(
            $result,
            $controller ? $controller->getResponse() : null
        );
    }
}
