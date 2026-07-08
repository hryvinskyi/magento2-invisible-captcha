<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Migration;

use Hryvinskyi\InvisibleCaptcha\Model\Migration\ChangeRecord;
use Hryvinskyi\InvisibleCaptcha\Model\Migration\MigrationRun;
use PHPUnit\Framework\TestCase;

class MigrationRunTest extends TestCase
{
    public function testClaimIsIdempotentPerTarget(): void
    {
        $run = new MigrationRun(false, false);

        $this->assertTrue($run->claim('default', 0, 'a/b/c'), 'first claim wins');
        $this->assertFalse($run->claim('default', 0, 'a/b/c'), 'second claim of same target is rejected');
        $this->assertTrue($run->claim('websites', 1, 'a/b/c'), 'same path at another scope is a distinct target');
    }

    public function testRecordsAccumulateInOrder(): void
    {
        $run = new MigrationRun(true, true);
        $this->assertTrue($run->dryRun);
        $this->assertTrue($run->force);

        $run->add(new ChangeRecord(null, 'p1', 'default', 0, 'v1', 'migrated'));
        $run->add(new ChangeRecord('s2', 'p2', 'default', 0, 'v2', 'skipped_exists'));

        $records = $run->records();
        $this->assertCount(2, $records);
        $this->assertSame('p1', $records[0]->target);
        $this->assertSame('p2', $records[1]->target);
    }
}
