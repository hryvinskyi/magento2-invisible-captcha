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
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Enables captcha validation for protected GraphQL mutations. The token is read
 * from the `X-Captcha-Token` header (`X-ReCaptcha` accepted for compatibility).
 */
class GraphQlValidator
{
    /**
     * @param HttpRequest $request
     * @param WebapiConfigProviderInterface $configProvider
     * @param TokenValidator $tokenValidator
     * @param EndpointFactory $endpointFactory
     */
    public function __construct(
        private readonly HttpRequest $request,
        private readonly WebapiConfigProviderInterface $configProvider,
        private readonly TokenValidator $tokenValidator,
        private readonly EndpointFactory $endpointFactory
    ) {
    }

    /**
     * @param ResolverInterface $subject
     * @param Field $fieldInfo
     * @param mixed $context
     * @param ResolveInfo $resolveInfo
     * @return void
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeResolve(
        ResolverInterface $subject,
        Field $fieldInfo,
        $context,
        ResolveInfo $resolveInfo
    ): void {
        if ($resolveInfo->operation->operation !== 'mutation') {
            return;
        }

        $endpoint = $this->endpointFactory->create([
            'class' => ltrim((string)$fieldInfo->getResolver(), '\\'),
            'method' => 'resolve',
            'name' => $fieldInfo->getName(),
        ]);

        $formKey = $this->configProvider->getFormKeyFor($endpoint);
        if ($formKey === null) {
            return;
        }

        if (!$this->tokenValidator->isValid($formKey, $this->extractToken())) {
            throw new GraphQlInputException(__('Captcha validation failed, please try again.'));
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
