<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Block\Adminhtml\System\Config\Form\Field;

use Hryvinskyi\InvisibleCaptcha\Block\Adminhtml\System\Config\Form\Field\MaxmindDbFile;
use Magento\Framework\App\ObjectManager as AppObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Escaper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class MaxmindDbFileTest extends TestCase
{
    private const ELEMENT_NAME = 'groups[geolocation][fields][maxmind_db][value]';

    private MaxmindDbFile $block;

    protected function setUp(): void
    {
        // The Backend\Template base constructor resolves helper dependencies
        // through the global ObjectManager unconditionally — seed it with an
        // auto-mocking stub so the block can be constructed in a unit test.
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $mockFactory = function (string $type): object {
            /** @var class-string<object> $type */
            return $this->createMock($type);
        };
        $objectManager->method('get')->willReturnCallback($mockFactory);
        $objectManager->method('create')->willReturnCallback($mockFactory);
        AppObjectManager::setInstance($objectManager);

        $block = (new ObjectManager($this))->getObject(MaxmindDbFile::class);
        self::assertInstanceOf(MaxmindDbFile::class, $block);
        $this->block = $block;

        // Replace the auto-mocked escaper with a deterministic pass-through so
        // the rendered markup is assertable.
        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(static fn ($value): string => (string)$value);
        $escaper->method('escapeHtmlAttr')->willReturnCallback(static fn ($value): string => (string)$value);
        $property = new \ReflectionProperty(\Magento\Framework\View\Element\AbstractBlock::class, '_escaper');
        $property->setValue($this->block, $escaper);
    }

    protected function tearDown(): void
    {
        $property = new \ReflectionProperty(AppObjectManager::class, '_instance');
        $property->setValue(null, null);
    }

    public function testRendersCurrentFileAndDeleteCheckboxWhenValuePresent(): void
    {
        $html = $this->renderElement('GeoLite2-Country.mmdb');

        self::assertStringContainsString('base-input', $html, 'parent file input should be preserved');
        self::assertStringContainsString('GeoLite2-Country.mmdb', $html);
        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringContainsString('name="' . self::ELEMENT_NAME . '[delete]"', $html);
    }

    public function testRendersNoDeleteCheckboxWhenValueEmpty(): void
    {
        $html = $this->renderElement('');

        self::assertStringContainsString('base-input', $html);
        self::assertStringNotContainsString('type="checkbox"', $html);
        self::assertStringNotContainsString('[delete]', $html);
    }

    private function renderElement(string $value): string
    {
        // getElementHtml/getName/getHtmlId are declared on AbstractElement;
        // getValue is a magic DataObject accessor, so it must be added.
        $element = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getElementHtml', 'getHtmlId', 'getName'])
            ->addMethods(['getValue'])
            ->getMock();
        $element->method('getElementHtml')->willReturn('<input class="base-input" type="file" />');
        $element->method('getValue')->willReturn($value);
        $element->method('getHtmlId')->willReturn('geolocation_maxmind_db');
        $element->method('getName')->willReturn(self::ELEMENT_NAME);

        $method = new \ReflectionMethod($this->block, '_getElementHtml');

        $result = $method->invoke($this->block, $element);
        self::assertIsString($result);

        return $result;
    }
}
