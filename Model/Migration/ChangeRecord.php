<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Migration;

use Hryvinskyi\InvisibleCaptcha\Api\Migration\RecaptchaMigratorInterface;

/**
 * Immutable record of one path the migration considered: where it came from,
 * where it went, at which scope, and what happened. Secret values arrive
 * pre-masked. For STATUS_SOURCE_DISABLED, source and target both hold the
 * cleared native path and the value is empty.
 */
final class ChangeRecord
{
    /**
     * @param string|null $source Native path the value came from (null for derived values).
     * @param string $target Destination path in the hryvinskyi_invisible_captcha tree.
     * @param string $scope Config scope ('default'|'websites'|'stores').
     * @param int $scopeId Scope id.
     * @param string $value Value written (secrets masked).
     * @param string $status One of {@see RecaptchaMigratorInterface}::STATUS_*.
     */
    public function __construct(
        public readonly ?string $source,
        public readonly string $target,
        public readonly string $scope,
        public readonly int $scopeId,
        public readonly string $value,
        public readonly string $status
    ) {
    }
}
