<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\ViewModel;

use Hryvinskyi\InvisibleCaptcha\Api\SilentVerificationInterface;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Hands the background verification's configuration to its template.
 */
class SilentVerification implements ArgumentInterface
{
    /**
     * Id of the JSON block the background verification script reads its configuration from.
     */
    public const CONFIG_ELEMENT_ID = 'hryvinskyi-captcha-silent-config';

    /**
     * Asset the template renders after the configuration block.
     */
    public const SCRIPT_FILE_ID = 'Hryvinskyi_InvisibleCaptcha::js/silent-verification.js';

    /**
     * @param SilentVerificationInterface $silentVerification
     * @param JsonHexTag $jsonSerializer
     */
    public function __construct(
        private readonly SilentVerificationInterface $silentVerification,
        private readonly JsonHexTag $jsonSerializer
    ) {
    }

    /**
     * Whether the page carries the background verification.
     */
    public function isActive(): bool
    {
        return $this->silentVerification->isActive();
    }

    /**
     * The configuration as JSON safe to print inside a `<script>` data block:
     * `<` and `>` are hex-escaped, so no value can close the block.
     */
    public function getConfigJson(): string
    {
        return $this->jsonSerializer->serialize($this->silentVerification->getClientConfig());
    }
}
