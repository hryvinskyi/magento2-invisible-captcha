<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Verification\Validator;

use Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator\Action;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequest;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationResult;
use PHPUnit\Framework\TestCase;

class ActionTest extends TestCase
{
    private Action $validator;

    protected function setUp(): void
    {
        $this->validator = new Action();
    }

    public function testNoOpWhenExpectedActionUnset(): void
    {
        $request = new VerificationRequest();
        $result = new VerificationResult(true, [], null, null, 0.9, 'login');

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testNoOpWhenProviderEchoedNoAction(): void
    {
        $request = (new VerificationRequest())->setExpectedAction('login');
        $result = new VerificationResult(true, [], null, null, 0.9, null);

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testPassesWhenActionMatches(): void
    {
        $request = (new VerificationRequest())->setExpectedAction('login');
        $result = new VerificationResult(true, [], null, null, 0.9, 'login');

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testFailsWhenActionMismatches(): void
    {
        $request = (new VerificationRequest())->setExpectedAction('login');
        $result = new VerificationResult(true, [], null, null, 0.9, 'checkout');

        $this->assertSame(Action::ERROR_CODE, $this->validator->validate($request, $result));
    }
}
