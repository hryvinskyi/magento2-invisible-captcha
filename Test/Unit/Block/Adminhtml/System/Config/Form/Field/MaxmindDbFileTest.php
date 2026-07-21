<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Block\Adminhtml\System\Config\Form\Field;

use Hryvinskyi\InvisibleCaptcha\Block\Adminhtml\System\Config\Form\Field\MaxmindDbFile;
use Magento\Framework\Data\Form\Element\AbstractElement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MaxmindDbFileTest extends TestCase
{
    private const ELEMENT_NAME = 'groups[geolocation][fields][maxmind_db][value]';
    private const ELEMENT_ID = 'geolocation_maxmind_db';
    private const TEMPLATE_OUTPUT = '<div class="hic-mmdb">rendered</div>';

    /** @var MaxmindDbFile&MockObject */
    private MaxmindDbFile $block;

    protected function setUp(): void
    {
        // The block renders through a template; stub _toHtml() so the test never
        // needs a template engine and can assert the element data is copied onto
        // the block and the template output is returned verbatim.
        $this->block = $this->getMockBuilder(MaxmindDbFile::class)
            ->onlyMethods(['_toHtml'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->block->method('_toHtml')->willReturn(self::TEMPLATE_OUTPUT);
    }

    public function testCopiesElementDataOntoBlockAndReturnsTemplateOutput(): void
    {
        $html = $this->renderElement('GeoLite2-Country.mmdb', false);

        self::assertSame(self::TEMPLATE_OUTPUT, $html);
        self::assertSame(self::ELEMENT_NAME, $this->block->getInputName());
        self::assertSame(self::ELEMENT_ID, $this->block->getInputId());
        self::assertSame('GeoLite2-Country.mmdb', $this->block->getCurrentFile());
        self::assertFalse($this->block->isDisabled());
    }

    public function testCurrentFileIsEmptyWhenNoValueStored(): void
    {
        $this->renderElement('', false);

        self::assertSame('', $this->block->getCurrentFile());
    }

    public function testNonStringValueBecomesEmptyCurrentFile(): void
    {
        $this->renderElement(['delete' => '1'], false);

        self::assertSame('', $this->block->getCurrentFile());
    }

    public function testDisabledStateIsCopiedFromElement(): void
    {
        $this->renderElement('GeoLite2-Country.mmdb', true);

        self::assertTrue($this->block->isDisabled());
    }

    public function testMaxmindExtensionLoadedReflectsRuntime(): void
    {
        self::assertSame(extension_loaded('maxminddb'), $this->block->isMaxmindExtensionLoaded());
    }

    /**
     * @param string|array<string, string> $value
     * @param bool $disabled
     * @return string
     */
    private function renderElement($value, bool $disabled): string
    {
        // getName/getHtmlId are declared on AbstractElement; getValue is a magic
        // DataObject accessor, so it must be added. `disabled` is read through the
        // real getData(), so it is seeded via setData().
        $element = $this->getMockBuilder(AbstractElement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHtmlId', 'getName'])
            ->addMethods(['getValue'])
            ->getMock();
        $element->method('getName')->willReturn(self::ELEMENT_NAME);
        $element->method('getHtmlId')->willReturn(self::ELEMENT_ID);
        $element->method('getValue')->willReturn($value);
        $element->setData('disabled', $disabled);

        $method = new \ReflectionMethod($this->block, '_getElementHtml');
        $result = $method->invoke($this->block, $element);
        self::assertIsString($result);

        return $result;
    }
}
