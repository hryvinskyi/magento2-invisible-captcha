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

class FullActionName implements FieldInterface, FieldValueHintInterface
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
        return 'action_name';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('Full Action Name');
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
        return (string)$this->request->getFullActionName();
    }

    /**
     * @inheritDoc
     */
    public function getValueHint(): array
    {
        return [
            'pattern' => '^[A-Za-z0-9_]+$',
            'message' => (string)__('Full action names contain only letters, digits and underscores.'),
            'placeholder' => 'catalog_product_view',
        ];
    }
}
