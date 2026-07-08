<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;

class UriPath implements FieldInterface
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
        return 'uri_path';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('URI Path');
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
        $uri = (string)$this->request->getRequestUri();
        $queryStart = strpos($uri, '?');

        return $queryStart === false ? $uri : substr($uri, 0, $queryStart);
    }
}
