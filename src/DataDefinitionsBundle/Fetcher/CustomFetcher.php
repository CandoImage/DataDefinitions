<?php

namespace Wvision\Bundle\DataDefinitionsBundle\Fetcher;

use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Db\Connection;
use Wvision\Bundle\DataDefinitionsBundle\Context\FetcherContextInterface;

class CustomFetcher implements FetcherInterface
{
    // @TODO: this must be rested as the method parameters changed
    public function fetch(FetcherContextInterface $context, int $limit, int $offset)
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

    // @TODO: this must be rested as the method parameters changed
    public function count(FetcherContextInterface $context): int
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