<?php

namespace Wvision\Bundle\DataDefinitionsBundle\Fetcher;

use Wvision\Bundle\DataDefinitionsBundle\Model\ExportDefinitionInterface;

class DatabaseFetcher implements FetcherInterface
{

    public function fetch(ExportDefinitionInterface $definition, $params, int $limit, int $offset, array $configuration)
    {
        // TODO: Implement fetch() method.
    }

    public function count(ExportDefinitionInterface $definition, $params, array $configuration): int
    {
        // TODO: Implement count() method.
    }
}