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
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
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

        $website = $this->createMock(Website::class);
        $website->method('getName')->willReturn('Main Website');

        $store = $this->createMock(Store::class);
        $store->method('isActive')->willReturn(true);
        $store->method('getWebsite')->willReturn($website);
        $store->method('getId')->willReturn(5);
        $store->method('getName')->willReturn('Default Store View');
        $store->method('getCode')->willReturn('default');
        $store->method('getBaseUrl')->willReturn('https://shop.test/');

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([$store]);
        $storeManager->method('getDefaultStoreView')->willReturn($store);

        $this->block = (new ObjectManager($this))->getObject(ProtectionRules::class, [
            'fieldProvider' => $fieldProvider,
            'operatorProvider' => $operatorProvider,
            'serializer' => $json,
            'storeManager' => $storeManager,
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
        // Plain interface mocks carry no metadata → default kind, no hint key.
        self::assertSame('text', $decoded['operators'][0]['valueKind']);
        self::assertArrayNotHasKey('hint', $decoded['fields'][0]);
        self::assertSame('action_name', $decoded['defaults']['field']);
        self::assertSame('eq', $decoded['defaults']['operator']);
        self::assertSame('or', $decoded['initial'][0]['combinator']);
        self::assertSame('cms_index_index', $decoded['initial'][0]['value']);
        self::assertArrayHasKey('labels', $decoded);
    }

    public function testGetEditorConfigJsonExposesTheTesterConfig(): void
    {
        $decoded = json_decode($this->block->getEditorConfigJson(), true);

        self::assertArrayHasKey('tester', $decoded);
        self::assertArrayHasKey('endpoint', $decoded['tester']);
        self::assertSame(5, $decoded['tester']['defaultStoreId']);
        self::assertSame(
            [['value' => 5, 'label' => 'Main Website / Default Store View (default)', 'baseUrl' => 'https://shop.test/']],
            $decoded['tester']['stores']
        );
    }
}
