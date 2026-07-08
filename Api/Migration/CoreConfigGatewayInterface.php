<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Api\Migration;

/**
 * Narrow persistence port over `core_config_data`, owned by the migration use
 * case so its mapping/derivation policy stays independent of (and unit-testable
 * without) the DB adapter. The infrastructure implementation lives outside.
 */
interface CoreConfigGatewayInterface
{
    /**
     * Load every config row whose path starts with one of the given prefixes,
     * indexed as [scope][scopeId][path] => value.
     *
     * @param string[] $pathPrefixes
     * @return array<string, array<int, array<string, string>>>
     */
    public function fetchTree(array $pathPrefixes): array;

    /**
     * Whether a raw config row already exists at the given path/scope.
     */
    public function exists(string $path, string $scope, int $scopeId): bool;

    /**
     * Insert or update a single config row.
     */
    public function write(string $path, string $value, string $scope, int $scopeId): void;

    /**
     * Delete a single config row (no-op when the row does not exist).
     */
    public function delete(string $path, string $scope, int $scopeId): void;
}
