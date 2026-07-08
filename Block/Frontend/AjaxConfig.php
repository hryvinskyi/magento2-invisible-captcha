<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Block\Frontend;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Emits a tiny `window.hryvinskyiCaptchaAjaxConfig = {...}` blob into the
 * `<head>` before ajax-fallback.js loads, so the JS can read its admin
 * config instead of the hardcoded defaults.
 */
class AjaxConfig extends Template
{
    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly ConfigInterface $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Build the JSON payload the JS reads from the script tag's data-config
     * attribute. Hosted on an attribute (not in inline-script body) so that
     * downstream HTML wrappers / minifiers can't break it: HTML entities in
     * attribute values are decoded by the parser, so even if a wrapper
     * encodes `"` to `&quot;`, `getAttribute()` returns the original JSON.
     *
     * @return string
     */
    public function getConfigJson(): string
    {
        return (string)json_encode(
            [
                'ajaxMarkerParams' => array_values($this->config->getAjaxMarkerParams()),
                'backgroundAjaxMarkerParams' => array_values($this->config->getBackgroundAjaxMarkerParams()),
                'filterAnchorSelector' => $this->config->getFilterAnchorSelector(),
                'filterParamPattern' => $this->config->getFilterParamPattern(),
            ],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}
