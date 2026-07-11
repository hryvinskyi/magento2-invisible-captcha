<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Filter;

use Magento\Framework\Phrase;

/**
 * A single filterable property of the current request — analogous to a
 * Cloudflare "field" (URI, Hostname, IP source address, …).
 *
 * Implementations are registered in the {@see FieldProviderInterface}
 * registry via di.xml so other modules can contribute their own.
 */
interface FieldInterface
{
    public const TYPE_STRING = 'string';
    public const TYPE_NUMERIC = 'numeric';
    public const TYPE_BOOLEAN = 'boolean';

    /**
     * Stable machine code used in the admin config to identify the field.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Human-readable label shown in the admin Field dropdown.
     *
     * @return Phrase
     */
    public function getLabel(): Phrase;

    /**
     * Value type — drives which operators apply and how values are cast.
     * Use one of the TYPE_* constants.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Resolve the field's value from the current request context.
     *
     * @return string|int|float|null
     */
    public function getValue(): string|int|float|null;
}
