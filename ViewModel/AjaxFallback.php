<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\ViewModel;

use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Hands the AJAX fallback its admin configuration: which query params mark an
 * AJAX or background request, and how a clicked filter link is recognised, so
 * a challenge answered to an AJAX request can send the visitor on to the page
 * they were heading to.
 *
 * The block may also carry `script_attributes` (a layout argument): extra
 * attributes printed on both the configuration block and the script tag. A
 * store whose response is rewritten after rendering (scripts moved, delayed or
 * merged) names there the attributes that keep the two tags in place and in
 * order, since the script wraps XMLHttpRequest and fetch and must run first.
 */
class AjaxFallback implements ArgumentInterface
{
    /**
     * Id of the JSON block the AJAX fallback script reads its configuration from.
     */
    public const CONFIG_ELEMENT_ID = 'hryvinskyi-captcha-ajax-config';

    /**
     * Asset the template renders after the configuration block.
     */
    public const SCRIPT_FILE_ID = 'Hryvinskyi_InvisibleCaptcha::js/ajax-fallback.js';

    /**
     * Attributes the template itself sets on the two tags; a configured value never replaces them.
     */
    private const RESERVED_ATTRIBUTES = ['type', 'id', 'src', 'async', 'defer'];

    /**
     * An HTML attribute name the template prints as it is.
     */
    private const ATTRIBUTE_NAME_PATTERN = '/^[a-z][a-z0-9_.:-]*$/i';

    /**
     * @param ConfigInterface $config
     * @param JsonHexTag $jsonSerializer
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly JsonHexTag $jsonSerializer
    ) {
    }

    /**
     * The configuration as JSON safe to print inside a `<script>` data block:
     * `<` and `>` are hex-escaped, so no admin-entered value can close the block.
     * The marker lists are re-indexed so they serialize as JSON arrays.
     */
    public function getConfigJson(): string
    {
        return $this->jsonSerializer->serialize([
            'ajaxMarkerParams' => array_values($this->config->getAjaxMarkerParams()),
            'backgroundAjaxMarkerParams' => array_values($this->config->getBackgroundAjaxMarkerParams()),
            'filterAnchorSelector' => $this->config->getFilterAnchorSelector(),
            'filterParamPattern' => $this->config->getFilterParamPattern(),
        ]);
    }

    /**
     * The extra attributes for the configuration block and the script tag, from
     * the block's `script_attributes` layout argument: entries whose name is a
     * plain attribute name the template does not set itself, and whose value is
     * text or a number; anything else is left out.
     *
     * @param mixed $configured The block's `script_attributes` data, as layout XML or code set it
     * @return array<string, string> Attribute name => value
     */
    public function getScriptAttributes(mixed $configured): array
    {
        if (!is_array($configured)) {
            return [];
        }

        $attributes = [];
        foreach ($configured as $name => $value) {
            if (!is_string($name)
                || preg_match(self::ATTRIBUTE_NAME_PATTERN, $name) !== 1
                || in_array(strtolower($name), self::RESERVED_ATTRIBUTES, true)
                || !(is_string($value) || is_int($value) || is_float($value))
            ) {
                continue;
            }
            $attributes[$name] = (string)$value;
        }

        return $attributes;
    }
}
