<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Geo\Source;

use Hryvinskyi\InvisibleCaptcha\Model\Geo\Source\CloudflareHeader;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CloudflareHeaderTest extends TestCase
{
    /** @var HttpRequest&MockObject */
    private HttpRequest $request;
    private CloudflareHeader $source;

    protected function setUp(): void
    {
        $this->request = $this->createMock(HttpRequest::class);
        $this->source = new CloudflareHeader($this->request);
    }

    public function testMetadata(): void
    {
        $this->assertSame('cloudflare', $this->source->getCode());
        $this->assertSame('Cloudflare (CF-IPCountry header)', (string)$this->source->getLabel());
        $this->assertTrue($this->source->isConfigured());
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function resolveProvider(): array
    {
        return [
            'uppercase code' => ['UA', 'UA'],
            'lowercase normalized' => ['ua', 'UA'],
            'whitespace trimmed' => [' de ', 'DE'],
            'unknown XX maps to null' => ['XX', null],
            'tor T1 passes through' => ['T1', 'T1'],
            'missing header' => [null, null],
            'empty header' => ['', null],
            'three-letter garbage' => ['USA', null],
            'symbol garbage' => ['@#', null],
        ];
    }

    #[DataProvider('resolveProvider')]
    public function testResolve(?string $header, ?string $expected): void
    {
        $this->request->method('getServer')
            ->with('HTTP_CF_IPCOUNTRY')
            ->willReturn($header);

        $this->assertSame($expected, $this->source->resolve('203.0.113.7'));
    }

    public function testClientIpArgumentIsIgnored(): void
    {
        $this->request->method('getServer')->willReturn('UA');

        $this->assertSame('UA', $this->source->resolve('literally-not-an-ip'));
    }
}
