<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Migration;

/**
 * Per-run scratch state for one migration pass: the dry-run/force flags, the set
 * of already-handled targets (so credential fallbacks and dry-runs don't double
 * count), and the accumulating change log. Created fresh in each migrate() call,
 * which keeps the migrator service itself stateless and reentrant.
 */
final class MigrationRun
{
    /** @var array<string, bool> Targets already handled, keyed "scope|scope_id|path". */
    private array $planned = [];

    /** @var ChangeRecord[] */
    private array $records = [];

    /**
     * @param bool $dryRun When true, nothing is persisted — the change set is still computed.
     * @param bool $force When true, values already present at a target are overwritten.
     */
    public function __construct(
        public readonly bool $dryRun,
        public readonly bool $force
    ) {
    }

    /**
     * Claim a target for this run. Returns true the first time a target is seen,
     * false on every subsequent attempt (so it is handled exactly once).
     */
    public function claim(string $scope, int $scopeId, string $target): bool
    {
        $key = $scope . '|' . $scopeId . '|' . $target;
        if (isset($this->planned[$key])) {
            return false;
        }
        $this->planned[$key] = true;

        return true;
    }

    /**
     * Append a change record to the run log.
     */
    public function add(ChangeRecord $record): void
    {
        $this->records[] = $record;
    }

    /**
     * @return ChangeRecord[]
     */
    public function records(): array
    {
        return $this->records;
    }
}
