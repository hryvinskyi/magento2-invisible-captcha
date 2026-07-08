<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Verification\Validator;

use Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator\TimeoutSeconds;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequest;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationResult;
use PHPUnit\Framework\TestCase;

class TimeoutSecondsTest extends TestCase
{
    private TimeoutSeconds $validator;

    protected function setUp(): void
    {
        $this->validator = new TimeoutSeconds();
    }

    public function testNoOpWhenTimeoutUnset(): void
    {
        $request = new VerificationRequest();
        $result = new VerificationResult(true, [], null, date('c', time() - 10000));

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testNoOpWhenTimeoutIsNotPositive(): void
    {
        $request = (new VerificationRequest())->setChallengeTimeout(0);
        $result = new VerificationResult(true, [], null, date('c', time() - 10000));

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testNoOpWhenProviderReportsNoTimestamp(): void
    {
        $request = (new VerificationRequest())->setChallengeTimeout(60);
        $result = new VerificationResult(true, [], null, null);

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testNoOpWhenTimestampIsUnparseable(): void
    {
        $request = (new VerificationRequest())->setChallengeTimeout(60);
        $result = new VerificationResult(true, [], null, 'not-a-real-timestamp');

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testPassesWhenChallengeIsWithinTimeout(): void
    {
        $request = (new VerificationRequest())->setChallengeTimeout(300);
        $result = new VerificationResult(true, [], null, date('c', time() - 10));

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testFailsWhenChallengeIsOlderThanTimeout(): void
    {
        $request = (new VerificationRequest())->setChallengeTimeout(60);
        $result = new VerificationResult(true, [], null, date('c', time() - 600));

        $this->assertSame(TimeoutSeconds::ERROR_CODE, $this->validator->validate($request, $result));
    }
}
