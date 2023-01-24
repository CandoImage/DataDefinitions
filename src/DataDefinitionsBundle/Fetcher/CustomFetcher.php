<?php

namespace Wvision\Bundle\DataDefinitionsBundle\Fetcher;

use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Db\Connection;
use Wvision\Bundle\DataDefinitionsBundle\Model\ExportDefinitionInterface;

class CustomFetcher implements FetcherInterface
{
    public function fetch(ExportDefinitionInterface $definition, $params, int $limit, int $offset, array $configuration)
    {
        $result = [];
        $mockupObjects = [];
        foreach ($result as $row) {
            $id = $row['id'] ?? 0;
            $mockup = new MockupObject($id, $row);
            $mockupObjects[] = $mockup;
        }
        return $mockupObjects;
    }

    public function count(ExportDefinitionInterface $definition, $params, array $configuration): int
    {
        return 0;
    }

    private function getData(ExportDefinitionInterface $definition, bool $count = false): QueryBuilder
    {
        /** @var Connection $connection */
        $connection = \Pimcore::getKernel()->getContainer()->get('database_connection');
        $queryBuilder = new QueryBuilder($connection);
        $queryBuilder->setMaxResults(null);
        $queryBuilder->setFirstResult(0);
        $queryBuilder->from($definition->getClass());
        $queryBuilder->select('*');
        if ($count) {
            $queryBuilder->select('COUNT(*)');
        }
        return $queryBuilder;
    }
}