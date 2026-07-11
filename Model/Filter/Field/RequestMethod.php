<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldValueHintInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;

class RequestMethod implements FieldInterface, FieldValueHintInterface
{
    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'request_method';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('HTTP Method');
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return self::TYPE_STRING;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return strtoupper((string)$this->request->getMethod());
    }

    /**
     * @inheritDoc
     */
    public function getValueHint(): array
    {
        return [
            'pattern' => '^[A-Za-z]+$',
            'message' => (string)__('Enter an HTTP method name, e.g. GET or POST.'),
            'placeholder' => 'POST',
        ];
    }
}
