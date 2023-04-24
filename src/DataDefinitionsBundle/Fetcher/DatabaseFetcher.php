<?php

namespace Wvision\Bundle\DataDefinitionsBundle\Fetcher;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Db;
use Wvision\Bundle\DataDefinitionsBundle\Context\FetcherContextInterface;
use Wvision\Bundle\DataDefinitionsBundle\Model\ExportDefinitionInterface;

class DatabaseFetcher implements FetcherInterface
{
    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function fetch(FetcherContextInterface $context, int $limit, int $offset): array
    {
        $queryBuilder = $this->getQueryBuilder($context->getDefinition());
        // set offset and limit
        $queryBuilder->setFirstResult($offset);
        $queryBuilder->setMaxResults($limit);

        $result = $queryBuilder->execute()->fetchAllAssociative();

        $mockupObjects = [];
        foreach ($result as $row) {
            $id = $row['id'] ?? 0;
            $mockup = new MockupObject($id, $row);
            $mockupObjects[] = $mockup;
        }
        return $mockupObjects;
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     */
    public function count(FetcherContextInterface $context): int
    {
        $queryBuilder = $this->getQueryBuilder($context->getDefinition(), true);
        return $queryBuilder->execute()->fetchOne();
    }

    private function getQueryBuilder(ExportDefinitionInterface $definition, bool $count = false): QueryBuilder
    {
        $connection = Db::getConnection();
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