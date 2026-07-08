<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Strategy\Token;

use Hryvinskyi\InvisibleCaptcha\Api\Strategy\TokenStrategyInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Reads the captcha token from a JSON request body (used by AJAX forms that
 * POST application/json, e.g. the authentication popup).
 */
class JsonBody implements TokenStrategyInterface
{
    /**
     * @param RequestInterface $request
     * @param Json $json
     * @param string $fieldName
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Json $json,
        private readonly string $fieldName = RequestParam::DEFAULT_FIELD
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getToken(): ?string
    {
        if (!$this->request instanceof Http) {
            return null;
        }

        $content = $this->request->getContent();
        if ($content === null || $content === '') {
            return null;
        }

        try {
            $params = $this->json->unserialize($content);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        if (is_array($params) && isset($params[$this->fieldName])) {
            return (string)$params[$this->fieldName];
        }

        return null;
    }
}
