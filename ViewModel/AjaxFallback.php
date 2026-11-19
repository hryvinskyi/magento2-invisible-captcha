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
}
