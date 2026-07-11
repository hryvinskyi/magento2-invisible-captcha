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
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;

class QueryString implements FieldInterface, FieldValueHintInterface
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
        return 'query_string';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('Query String');
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
        if ($this->request instanceof HttpRequest) {
            $query = (string)$this->request->getServer('QUERY_STRING');
            if ($query !== '') {
                return $query;
            }
        }

        $uri = (string)$this->request->getRequestUri();
        $queryStart = strpos($uri, '?');

        return $queryStart === false ? '' : substr($uri, $queryStart + 1);
    }

    /**
     * @inheritDoc
     */
    public function getValueHint(): array
    {
        return [
            'placeholder' => 'price=10-20',
        ];
    }
}
