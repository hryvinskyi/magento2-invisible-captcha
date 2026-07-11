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

class UserAgent implements FieldInterface, FieldValueHintInterface
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
        return 'user_agent';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('User Agent');
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
        return (string)$this->request->getHeader('User-Agent');
    }

    /**
     * @inheritDoc
     */
    public function getValueHint(): array
    {
        return [
            'placeholder' => 'Mozilla/5.0 (compatible; SomeBot/1.0)',
        ];
    }
}
