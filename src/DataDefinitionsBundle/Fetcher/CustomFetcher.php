<?php

namespace Wvision\Bundle\DataDefinitionsBundle\Fetcher;

use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Db;
use Wvision\Bundle\DataDefinitionsBundle\Context\FetcherContextInterface;
use Wvision\Bundle\DataDefinitionsBundle\Model\ExportDefinitionInterface;

class CustomFetcher implements FetcherInterface
{
    public function fetch(FetcherContextInterface $context, int $limit, int $offset): array
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

    public function count(FetcherContextInterface $context): int
    {
        return 0;
    }

    private function getData(ExportDefinitionInterface $definition, bool $count = false): QueryBuilder
    {
        $db = Db::getConnection();
        $queryBuilder = new QueryBuilder($db);
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