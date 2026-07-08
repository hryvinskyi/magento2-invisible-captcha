<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Token;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\TokenStrategyInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Reads the captcha token from a standard request parameter.
 */
class RequestParam implements TokenStrategyInterface
{
    public const DEFAULT_FIELD = 'hryvinskyi_invisible_token';

    /**
     * @param RequestInterface $request
     * @param string $fieldName Neutral wrapper field the JS populates for every provider.
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly string $fieldName = self::DEFAULT_FIELD
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getToken(): ?string
    {
        $value = $this->request->getParam($this->fieldName);

        return $value === null ? null : (string)$value;
    }
}
