<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Test\Unit\Model\Migration;

use Hryvinskyi\InvisibleCaptcha\Model\Migration\CoreConfigGateway;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CoreConfigGatewayTest extends TestCase
{
    /** @var ResourceConnection&MockObject */
    private ResourceConnection $resourceConnection;
    /** @var ConfigResource&MockObject */
    private ConfigResource $configResource;
    /** @var AdapterInterface&MockObject */
    private AdapterInterface $connection;
    private CoreConfigGateway $gateway;

    protected function setUp(): void
    {
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->configResource = $this->createMock(ConfigResource::class);
        $this->connection = $this->createMock(AdapterInterface::class);

        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnSelf();

        $this->connection->method('select')->willReturn($select);
        $this->connection->method('quoteInto')->willReturnCallback(
            static fn (string $text, $value): string => str_replace('?', "'" . $value . "'", $text)
        );
        $this->resourceConnection->method('getConnection')->willReturn($this->connection);
        $this->resourceConnection->method('getTableName')->willReturnArgument(0);

        $this->gateway = new CoreConfigGateway($this->resourceConnection, $this->configResource);
    }

    public function testFetchTreeIndexesRowsByScopeScopeIdAndPath(): void
    {
        $this->connection->method('fetchAll')->willReturn([
            ['scope' => 'default', 'scope_id' => '0', 'path' => 'recaptcha_frontend/type_recaptcha_v3/public_key', 'value' => 'PK'],
            ['scope' => 'websites', 'scope_id' => '2', 'path' => 'recaptcha_backend/type_for/user_login', 'value' => 'invisible'],
        ]);

        $tree = $this->gateway->fetchTree(['recaptcha_frontend/', 'recaptcha_backend/']);

        $this->assertSame('PK', $tree['default'][0]['recaptcha_frontend/type_recaptcha_v3/public_key']);
        $this->assertSame('invisible', $tree['websites'][2]['recaptcha_backend/type_for/user_login']);
    }

    public function testFetchTreeReturnsEmptyForNoPrefixes(): void
    {
        $this->connection->expects($this->never())->method('fetchAll');

        $this->assertSame([], $this->gateway->fetchTree([]));
    }

    public function testExistsReflectsRowPresence(): void
    {
        $this->connection->method('fetchOne')->willReturn('123');
        $this->assertTrue($this->gateway->exists('some/path', 'default', 0));
    }

    public function testExistsFalseWhenNoRow(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);
        $this->assertFalse($this->gateway->exists('some/path', 'default', 0));
    }

    public function testWriteDelegatesToConfigResource(): void
    {
        $this->configResource->expects($this->once())
            ->method('saveConfig')
            ->with('some/path', 'value', 'websites', 2);

        $this->gateway->write('some/path', 'value', 'websites', 2);
    }
}
