<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Filter\Field;

use Hryvinskyi\InvisibleCaptcha\Api\AjaxRequestDetectorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\ConfigInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldValueHintInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Http\ClientIpResolverInterface;
use Hryvinskyi\InvisibleCaptcha\Api\NoRouteActionInterface;
use Hryvinskyi\InvisibleCaptcha\Api\RobotsTxt\MatcherInterface;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\ActiveParamCount;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\ClientIp;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\FullActionName;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\Hostname;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\IsAjax;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\NoRoute;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\QueryString;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\Referer;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\RequestMethod;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\RequestUri;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\RobotsTxtBlocked;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\UriPath;
use Hryvinskyi\InvisibleCaptcha\Model\Filter\Field\UserAgent;
use Magento\Framework\App\Request\Http as HttpRequest;
use PHPUnit\Framework\TestCase;

/**
 * The value hints the built-in fields expose to the rules editor: every
 * hinted field carries a placeholder, and every pattern is a valid regex
 * with sane accept/reject behavior.
 */
class FieldValueHintsTest extends TestCase
{
    public function testEveryHintedFieldExposesAPlaceholderAndValidPattern(): void
    {
        foreach ($this->hintedFields() as $field) {
            $this->assertInstanceOf(FieldValueHintInterface::class, $field);
            $hint = $field->getValueHint();
            $this->assertNotSame('', $hint['placeholder'] ?? '', $field->getCode());

            if (isset($hint['pattern'])) {
                $this->assertNotFalse(
                    @preg_match('~' . $hint['pattern'] . '~', ''),
                    $field->getCode() . ' pattern must compile'
                );
                $this->assertNotSame('', $hint['message'] ?? '', $field->getCode() . ' pattern needs a message');
            }
        }
    }

    /**
     * @dataProvider clientIpPatternProvider
     */
    public function testClientIpPattern(string $value, bool $expected): void
    {
        $field = new ClientIp(
            $this->createMock(HttpRequest::class),
            $this->createMock(ClientIpResolverInterface::class)
        );
        $pattern = '~' . $field->getValueHint()['pattern'] . '~';

        $this->assertSame($expected, (bool)preg_match($pattern, $value));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function clientIpPatternProvider(): array
    {
        return [
            'ipv4' => ['192.168.10.1', true],
            'ipv6 loopback' => ['::1', true],
            'ipv6' => ['2a00:1450:4001:82f::200e', true],
            'word' => ['localhost', false],
            'ipv4 with garbage' => ['1.2.3.4x', false],
        ];
    }

    /**
     * @dataProvider actionNamePatternProvider
     */
    public function testFullActionNamePattern(string $value, bool $expected): void
    {
        $field = new FullActionName($this->createMock(HttpRequest::class));
        $pattern = '~' . $field->getValueHint()['pattern'] . '~';

        $this->assertSame($expected, (bool)preg_match($pattern, $value));
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function actionNamePatternProvider(): array
    {
        return [
            'action name' => ['catalog_product_view', true],
            'with space' => ['catalog product', false],
            'with slash' => ['catalog/product/view', false],
        ];
    }

    public function testRequestMethodPatternAcceptsMethodTokensOnly(): void
    {
        $field = new RequestMethod($this->createMock(HttpRequest::class));
        $pattern = '~' . $field->getValueHint()['pattern'] . '~';

        $this->assertSame(1, preg_match($pattern, 'POST'));
        $this->assertSame(0, preg_match($pattern, 'GET POST'));
    }

    public function testBooleanFieldsExposeNoHint(): void
    {
        $request = $this->createMock(HttpRequest::class);
        $booleanFields = [
            new RobotsTxtBlocked($this->createMock(MatcherInterface::class), $request),
            new IsAjax($this->createMock(AjaxRequestDetectorInterface::class), $request),
            new NoRoute($this->createMock(NoRouteActionInterface::class), $request),
        ];

        foreach ($booleanFields as $field) {
            $this->assertNotInstanceOf(FieldValueHintInterface::class, $field, $field->getCode());
        }
    }

    /**
     * All built-in fields that expose a value hint.
     *
     * @return FieldValueHintInterface[]
     */
    private function hintedFields(): array
    {
        $request = $this->createMock(HttpRequest::class);

        return [
            new FullActionName($request),
            new RequestUri($request),
            new UriPath($request),
            new QueryString($request),
            new RequestMethod($request),
            new Hostname($request),
            new ClientIp($request, $this->createMock(ClientIpResolverInterface::class)),
            new UserAgent($request),
            new Referer($request),
            new ActiveParamCount($request, $this->createMock(ConfigInterface::class)),
        ];
    }
}
