<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Filter\Operator;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface;
use Psr\Log\LoggerInterface;

/**
 * Shared matching core for the regex operators.
 *
 * The rules editor promises Cloudflare-style expressions, so admins write bare
 * patterns like `.*` or `^catalog_.*` — while PCRE demands delimiters. The value
 * is therefore tried as-is first (full delimited PCRE incl. modifiers keeps
 * working, e.g. `~^/checkout~i`), and on a PCRE parse error retried wrapped in
 * `~...~`. Only when both forms fail is the pattern reported invalid.
 */
abstract class AbstractRegex implements OperatorInterface
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function supports(string $fieldType): bool
    {
        return $fieldType === FieldInterface::TYPE_STRING;
    }

    /**
     * Whether the pattern matches the field value.
     *
     * Returns null when the pattern is invalid both as delimited PCRE and as a
     * bare pattern — callers treat that as "cannot evaluate" and fail safe, so
     * a single bad admin entry cannot block legitimate traffic or 500 the
     * request. The failure is logged.
     */
    protected function match(string|int|float|null $fieldValue, string $configValue): ?bool
    {
        $subject = (string)($fieldValue ?? '');

        $result = @preg_match($configValue, $subject);
        if ($result !== false) {
            return $result === 1;
        }

        // Bare (Cloudflare-style) pattern: wrap in a neutral delimiter.
        $wrapped = '~' . str_replace('~', '\~', $configValue) . '~';
        $result = @preg_match($wrapped, $subject);
        if ($result !== false) {
            return $result === 1;
        }

        $this->logger->warning(sprintf(
            '[InvisibleCaptcha] invalid regex skipped | pattern=%s | error=%s',
            $configValue,
            (string)preg_last_error_msg()
        ));

        return null;
    }
}
