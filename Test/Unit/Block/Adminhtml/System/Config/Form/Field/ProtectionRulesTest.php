<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Block\Adminhtml\System\Config\Form\Field;

use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\FieldProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorInterface;
use Hryvinskyi\InvisibleCaptcha\Api\Filter\OperatorProviderInterface;
use Hryvinskyi\InvisibleCaptcha\Block\Adminhtml\System\Config\Form\Field\ProtectionRules;
use Magento\Framework\App\ObjectManager as AppObjectManager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class ProtectionRulesTest extends TestCase
{
    private ProtectionRules $block;

    protected function setUp(): void
    {
        // The Backend\Template base constructor resolves JsonHelper/DirectoryHelper
        // through the global ObjectManager unconditionally — seed it with an
        // auto-mocking stub so the block can be constructed in a unit test.
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(
            fn (string $type): object => $this->createMock($type)
        );
        $objectManager->method('create')->willReturnCallback(
            fn (string $type): object => $this->createMock($type)
        );
        AppObjectManager::setInstance($objectManager);

        $field = $this->createMock(FieldInterface::class);
        $field->method('getCode')->willReturn('action_name');
        $field->method('getLabel')->willReturn(__('Full Action Name'));
        $field->method('getType')->willReturn('string');

        $fieldProvider = $this->createMock(FieldProviderInterface::class);
        $fieldProvider->method('getAll')->willReturn([$field]);

        $operator = $this->createMock(OperatorInterface::class);
        $operator->method('getCode')->willReturn('eq');
        $operator->method('getLabel')->willReturn(__('equals'));
        $operator->method('supports')->willReturn(true);

        $operatorProvider = $this->createMock(OperatorProviderInterface::class);
        $operatorProvider->method('getAll')->willReturn([$operator]);

        $json = $this->createMock(Json::class);
        $json->method('serialize')->willReturnCallback(static fn ($v) => json_encode($v));
        $json->method('unserialize')->willReturnCallback(static fn ($v) => json_decode((string)$v, true));

        $this->block = (new ObjectManager($this))->getObject(ProtectionRules::class, [
            'fieldProvider' => $fieldProvider,
            'operatorProvider' => $operatorProvider,
            'serializer' => $json,
        ]);
    }

    protected function tearDown(): void
    {
        // Clear the seeded global ObjectManager so no other test inherits it.
        $property = new \ReflectionProperty(AppObjectManager::class, '_instance');
        $property->setValue(null, null);
    }

    public function testGetEditorConfigJsonExposesFieldsOperatorsDefaultsAndInitialValue(): void
    {
        $this->block->setData('name_prefix', 'groups[protection][fields][rules]');
        $this->block->setData(
            'initial_value',
            json_encode([['combinator' => 'or', 'field' => 'action_name', 'operator' => 'eq', 'value' => 'cms_index_index']])
        );

        $decoded = json_decode($this->block->getEditorConfigJson(), true);

        self::assertSame('groups[protection][fields][rules]', $decoded['inputName']);
        self::assertSame('action_name', $decoded['fields'][0]['value']);
        self::assertSame('string', $decoded['fields'][0]['type']);
        self::assertSame('eq', $decoded['operators'][0]['value']);
        self::assertContains('string', $decoded['operators'][0]['supports']);
        self::assertSame('action_name', $decoded['defaults']['field']);
        self::assertSame('eq', $decoded['defaults']['operator']);
        self::assertSame('or', $decoded['initial'][0]['combinator']);
        self::assertSame('cms_index_index', $decoded['initial'][0]['value']);
        self::assertArrayHasKey('labels', $decoded);
    }
}
