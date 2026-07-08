<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Verification\Validator;

use Hryvinskyi\InvisibleCaptcha\Model\Verification\Validator\Host;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationRequest;
use Hryvinskyi\InvisibleCaptcha\Model\Verification\VerificationResult;
use PHPUnit\Framework\TestCase;

class HostTest extends TestCase
{
    private Host $validator;

    protected function setUp(): void
    {
        $this->validator = new Host();
    }

    public function testNoOpWhenExpectedHostnameUnset(): void
    {
        $request = new VerificationRequest();
        $result = new VerificationResult(true, [], 'whatever.example.com');

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testNoOpWhenProviderReportsNoHostname(): void
    {
        $request = (new VerificationRequest())->setExpectedHostname('example.com');
        $result = new VerificationResult(true, [], null);

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testPassesWhenHostnameMatches(): void
    {
        $request = (new VerificationRequest())->setExpectedHostname('example.com');
        $result = new VerificationResult(true, [], 'example.com');

        $this->assertNull($this->validator->validate($request, $result));
    }

    public function testFailsWhenHostnameMismatches(): void
    {
        $request = (new VerificationRequest())->setExpectedHostname('example.com');
        $result = new VerificationResult(true, [], 'evil.example.com');

        $this->assertSame(Host::ERROR_CODE, $this->validator->validate($request, $result));
    }
}
