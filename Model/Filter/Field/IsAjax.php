<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\AjaxRequestDetectorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Phrase;

/**
 * Resolves to 1 for XHR / background requests. Composes with other fields to
 * keep service endpoints out of broad rules — e.g.
 * `robots_txt_blocked eq 1 and is_ajax eq 0` challenges robots.txt violations
 * without breaking the AJAX calls (customer section load, minicart, add to
 * cart) that pages fire in the background.
 */
class IsAjax implements FieldInterface
{
    /**
     * @param AjaxRequestDetectorInterface $ajaxRequestDetector
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly AjaxRequestDetectorInterface $ajaxRequestDetector,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'is_ajax';
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): Phrase
    {
        return __('Is AJAX Request');
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return self::TYPE_BOOLEAN;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): int
    {
        return $this->ajaxRequestDetector->isAjax($this->request) ? 1 : 0;
    }
}
