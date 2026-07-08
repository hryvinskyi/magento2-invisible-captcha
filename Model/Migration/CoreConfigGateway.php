<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */
declare(strict_types=1);

namespace Hryvinskyi\InvisibleCaptcha\Model\Migration;

use Hryvinskyi\InvisibleCaptcha\Api\Migration\CoreConfigGatewayInterface;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\ResourceConnection;

/**
 * `core_config_data` adapter for {@see CoreConfigGatewayInterface}. Isolates all
 * DB access (raw reads via the connection, writes via the config resource model)
 * behind the port so the migrator holds only mapping/derivation policy.
 */
class CoreConfigGateway implements CoreConfigGatewayInterface
{
    private const CORE_CONFIG_TABLE = 'core_config_data';

    /**
     * @param ResourceConnection $resourceConnection
     * @param ConfigResource $configResource
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly ConfigResource $configResource
    ) {
    }

    /**
     * @inheritDoc
     */
    public function fetchTree(array $pathPrefixes): array
    {
        if ($pathPrefixes === []) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::CORE_CONFIG_TABLE);

        $select = $connection->select()->from($table, ['scope', 'scope_id', 'path', 'value']);
        $conditions = [];
        foreach ($pathPrefixes as $prefix) {
            $conditions[] = $connection->quoteInto('path LIKE ?', $prefix . '%');
        }
        $select->where(implode(' OR ', $conditions));

        $tree = [];
        foreach ($connection->fetchAll($select) as $row) {
            $tree[$row['scope']][(int)$row['scope_id']][$row['path']] = (string)$row['value'];
        }

        return $tree;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $path, string $scope, int $scopeId): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::CORE_CONFIG_TABLE);

        $select = $connection->select()
            ->from($table, 'config_id')
            ->where('path = ?', $path)
            ->where('scope = ?', $scope)
            ->where('scope_id = ?', $scopeId);

        return $connection->fetchOne($select) !== false;
    }

    /**
     * @inheritDoc
     */
    public function write(string $path, string $value, string $scope, int $scopeId): void
    {
        $this->configResource->saveConfig($path, $value, $scope, $scopeId);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path, string $scope, int $scopeId): void
    {
        $this->configResource->deleteConfig($path, $scope, $scopeId);
    }
}
