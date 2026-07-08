<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Plugin\Webapi;

use Hryvinskyi\InvisibleCaptcha\Api\Webapi\WebapiConfigProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\EndpointFactory;
use Hryvinskyi\InvisibleCaptcha\Model\Webapi\TokenValidator;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Webapi\Controller\Rest\RequestValidator;
use Magento\Webapi\Controller\Rest\Router;

/**
 * Enables captcha validation for protected RESTful WebAPI endpoints (e.g. the
 * checkout place-order and coupon calls used by AJAX / one-step checkouts). The
 * token is read from the `X-Captcha-Token` header (`X-ReCaptcha` accepted for
 * compatibility).
 */
class RestValidationPlugin
{
    /**
     * @param TokenValidator $tokenValidator
     * @param WebapiConfigProviderInterface $configProvider
     * @param RestRequest $request
     * @param Router $restRouter
     * @param EndpointFactory $endpointFactory
     */
    public function __construct(
        private readonly TokenValidator $tokenValidator,
        private readonly WebapiConfigProviderInterface $configProvider,
        private readonly RestRequest $request,
        private readonly Router $restRouter,
        private readonly EndpointFactory $endpointFactory
    ) {
    }

    /**
     * @param RequestValidator $subject
     * @param callable $proceed
     * @return void
     * @throws WebapiException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundValidate(RequestValidator $subject, callable $proceed): void
    {
        $request = clone $this->request;
        $proceed();

        $route = $this->restRouter->match($request);
        $endpoint = $this->endpointFactory->create([
            'class' => $route->getServiceClass(),
            'method' => $route->getServiceMethod(),
            'name' => $route->getRoutePath(),
        ]);

        $formKey = $this->configProvider->getFormKeyFor($endpoint);
        if ($formKey === null) {
            return;
        }

        if (!$this->tokenValidator->isValid($formKey, $this->extractToken())) {
            throw new WebapiException(__('Captcha validation failed, please try again.'));
        }
    }

    /**
     * Read the captcha token from the supported request headers.
     */
    private function extractToken(): string
    {
        $token = (string)$this->request->getHeader('X-Captcha-Token');
        if ($token === '') {
            $token = (string)$this->request->getHeader('X-ReCaptcha');
        }

        return $token;
    }
}
