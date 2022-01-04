<?php
/**
 * Data Definitions.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2016-2019 w-vision AG (https://www.w-vision.ch)
 * @license    https://github.com/w-vision/DataDefinitions/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace Wvision\Bundle\DataDefinitionsBundle\Controller;

use CoreShop\Bundle\ResourceBundle\Controller\ResourceController;
use Doctrine\DBAL\Schema\Column;
use Pimcore\Db\Connection;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wvision\Bundle\DataDefinitionsBundle\Model\ExportDefinitionInterface;
use Wvision\Bundle\DataDefinitionsBundle\Model\ExportMapping\FromColumn;

class ExportDefinitionController extends ResourceController
{
    /**
     * @return mixed|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getConfigAction()
    {
        $providers = $this->getConfigProviders();
        $interpreters = $this->getConfigInterpreters();
        $runners = $this->getConfigRunners();
        $getters = $this->getConfigGetters();
        $fetchers = $this->getConfigFetchers();
        $importRuleConditions = $this->getImportRuleConditions();
        $importRuleActions = $this->getImportRuleActions();

        return $this->viewHandler->handle(
            [
                'providers' => array_keys($providers),
                'interpreter' => array_keys($interpreters),
                'runner' => array_keys($runners),
                'getters' => array_keys($getters),
                'fetchers' => array_keys($fetchers),
                'import_rules' => [
                    'conditions' => array_keys($importRuleConditions),
                    'actions' => array_keys($importRuleActions)
                ]
            ]
        );
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function exportAction(Request $request): Response
    {
        $id = (int)$request->get('id');

        if ($id) {
            $definition = $this->repository->find($id);

            if ($definition instanceof ExportDefinitionInterface) {

                $name = $definition->getName();
                unset($definition->id, $definition->creationDate, $definition->modificationDate);

                $response = new Response();
                $response->headers->set('Content-Type', 'application/json');
                $response->headers->set(
                    'Content-Disposition',
                    sprintf('attachment; filename="export-definition-%s.json"', $name)
                );
                $response->headers->set('Pragma', 'no-cache');
                $response->headers->set('Expires', '0');
                $response->headers->set('Content-Transfer-Encoding', 'binary');

                $response->setContent(json_encode($definition));

                return $response;
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function importAction(Request $request)
    {
        $id = (int)$request->get('id');
        $definition = $this->repository->find($id);

        if ($id && $definition instanceof ExportDefinitionInterface && $request->files->has('Filedata')) {
            $uploadedFile = $request->files->get('Filedata');

            if ($uploadedFile instanceof UploadedFile) {
                $jsonContent = file_get_contents($uploadedFile->getPathname());
                $data = $this->decodeJson($jsonContent, false);

                $form = $this->resourceFormFactory->create($this->metadata, $definition);
                $handledForm = $form->submit($data);

                if ($handledForm->isValid()) {
                    $definition = $form->getData();

                    $this->manager->persist($definition);
                    $this->manager->flush();

                    return $this->viewHandler->handle(['success' => true]);
                }
            }
        }

        return $this->viewHandler->handle(['success' => false]);
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function duplicateAction(Request $request)
    {
        $id = (int)$request->get('id');
        $definition = $this->repository->find($id);
        $name = (string)$request->get('name');

        if ($definition instanceof ExportDefinitionInterface && $name) {
            $newDefinition = clone $definition;
            $newDefinition->setId(null);
            $newDefinition->setName($name);

            $this->manager->persist($newDefinition);
            $this->manager->flush();

            return $this->viewHandler->handle(['success' => true, 'data' => $newDefinition]);
        }

        return $this->viewHandler->handle(['success' => false]);
    }

    /**
     * Get Pimcore Class Definition.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getColumnsAction(Request $request)
    {
        $id = $request->get('id');
        $definition = $this->repository->find($id);

        if (!$definition instanceof ExportDefinitionInterface || !$definition->getClass()) {
            return $this->viewHandler->handle(['success' => false]);
        }

        $classDefinition = DataObject\ClassDefinition::getByName($definition->getClass());
        $fields = $classDefinition->getFieldDefinitions();

        $csLoadedGroupIds = [];
        $activatedLanguages = \Pimcore\Tool::getValidLanguages();

        $result = $this->getSystemFields();

        $bricks = [];
        $collections = [];

        foreach ($classDefinition->getFieldDefinitions() as $field) {
            if ($field instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                $bricks[$field->getName()] = $field->getAllowedTypes();
            } elseif ($field instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
                $collections[$field->getName()] = $field->getAllowedTypes();
            }
        }

        foreach ($fields as $field) {
            switch (get_class($field)) {
                case DataObject\ClassDefinition\Data\Localizedfields::class:
                    foreach ($activatedLanguages as $language) {
                        $localizedFields = $field->getFieldDefinitions();

                        foreach ($localizedFields as $localizedField) {
                            $localizedField = $this->getFieldConfiguration($localizedField);

                            $localizedField->setGroup('localizedfield.' . strtolower($language));
                            $localizedField->setType('localizedfields');
                            $localizedField->setIdentifier(sprintf('%s~%s', $localizedField->getIdentifier(),
                                $language));
                            $localizedField->setGetter('localizedfield');
                            $localizedField->setConfig(['language' => $language]);
                            $localizedField->setGetterConfig(['language' => $language]);
                            $localizedField->setType('localizedfields');
                            $result[] = $localizedField;
                        }
                    }
                    break;

                case DataObject\ClassDefinition\Data\Objectbricks::class:
                    $list = new DataObject\Objectbrick\Definition\Listing();
                    $list = $list->load();

                    foreach ($list as $brickDefinition) {
                        if ($brickDefinition instanceof DataObject\Objectbrick\Definition) {
                            $key = $brickDefinition->getKey();
                            $classDefs = $brickDefinition->getClassDefinitions();

                            foreach ($classDefs as $classDef) {
                                if ($classDef['classname'] === $classDefinition->getName() && $classDef['fieldname'] === $field->getName()) {
                                    $fields = $brickDefinition->getFieldDefinitions();

                                    foreach ($fields as $brickField) {
                                        $resultField = $this->getFieldConfiguration($brickField);

                                        $resultField->setGroup('objectbrick.' . $key);
                                        $resultField->setType('objectbricks');
                                        $resultField->setIdentifier(sprintf('objectbrick~%s~%s~%s', $field->getName(),
                                            $key, $resultField->getIdentifier()));
                                        $resultField->setGetter('objectbrick');
                                        $resultField->setConfig(['class' => $key]);
                                        $resultField->setType('objectbrick');
                                        $result[] = $resultField;
                                    }

                                    break;
                                }
                            }
                        }
                    }
                    break;

                case DataObject\ClassDefinition\Data\Fieldcollections::class:
                    foreach ($field->getAllowedTypes() as $type) {
                        $definition = DataObject\Fieldcollection\Definition::getByKey($type);

                        $fieldDefinition = $definition->getFieldDefinitions();

                        foreach ($fieldDefinition as $fieldcollectionField) {
                            $resultField = $this->getFieldConfiguration($fieldcollectionField);

                            $resultField->setGroup('fieldcollection.' . $type);
                            $resultField->setType('fieldcollections');
                            $resultField->setIdentifier(sprintf('fieldcollection~%s~%s~%s', $field->getName(), $type,
                                $resultField->getIdentifier()));
                            $resultField->setGetter('fieldcollection');
                            $resultField->setConfig(['class' => $type]);
                            $resultField->setType('fieldcollection');

                            $result[] = $resultField;
                        }
                    }
                    break;

                case DataObject\ClassDefinition\Data\Classificationstore::class:
                    $resultField = $this->getFieldConfiguration($field);
                    $resultField->setType('object');
                    $resultField->setGetter('classificationstore_field');

                    $result[] = $resultField;

                    $list = new DataObject\Classificationstore\GroupConfig\Listing();
                    $allowedGroupIds = $field->getAllowedGroupIds();

                    if ($allowedGroupIds) {
                        $list->setCondition('ID in (' . implode(',', $allowedGroupIds) . ') AND storeId = ?',
                            [$field->getStoreId()]);
                    } else {
                        $list->setCondition('storeId = ?', [$field->getStoreId()]);
                    }

                    $list->load();

                    $groupConfigList = $list->getList();

                    /**
                     * @var DataObject\Classificationstore\GroupConfig $config
                     */
                    foreach ($groupConfigList as $config) {
                        foreach ($config->getRelations() as $relation) {
                            if ($relation instanceof DataObject\Classificationstore\KeyGroupRelation) {
                                $keyId = $relation->getKeyId();

                                $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);

                                $toColumn = new FromColumn();
                                $toColumn->setGroup(sprintf('classificationstore - %s (%s)', $config->getName(),
                                    $config->getId()));
                                $toColumn->setIdentifier(sprintf('classificationstore~%s~%s~%s', $field->getName(),
                                    $keyConfig->getId(), $config->getId()));
                                $toColumn->setType('classificationstore');
                                $toColumn->setFieldtype($keyConfig->getType());
                                $toColumn->setGetter('classificationstore');
                                $toColumn->setConfig([
                                    'field' => $field->getName(),
                                    'keyId' => $keyConfig->getId(),
                                    'groupId' => $config->getId(),
                                ]);
                                $toColumn->setLabel($keyConfig->getName());
                                $toColumn->setType('classificationstore');

                                $result[] = $toColumn;
                            }
                        }
                    }
                    break;

                default:
                    $resultField = $this->getFieldConfiguration($field);
                    $resultField->setType('object');

                    $result[] = $resultField;
                    break;
            }
        }

        return $this->viewHandler->handle([
            'success' => true,
            'fields' => $result,
            'bricks' => $bricks,
            'fieldcollections' => $collections,
        ]);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getTablesAction(Request $request)
    {
        $tables = [];
        /** @var Connection $connection */
        $connection = $this->getDoctrine()->getConnection();
        if ($connection) {
            $tableList = $connection->getSchemaManager()->listTables();
            foreach ($tableList as $table) {
                $tables[] = [
                    'id' => $table->getName(),
                    'text' => $table->getName(),
                    'leaf' => true
                ];
            }
            $tableViews = $connection->getSchemaManager()->listViews();
            foreach ($tableViews as $view) {
                $tables[] = [
                    'id' => $view->getName(),
                    'text' => $view->getName(),
                    'leaf' => true
                ];
            }
        }
        return $this->adminJson($tables);
    }

    /**
     * Get Raw table columns
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getDbColumnsAction(Request $request)
    {
        $id = $request->get('id');
        $definition = $this->repository->find($id);

        if (!$definition instanceof ExportDefinitionInterface || !$definition->getClass()) {
            return $this->viewHandler->handle(['success' => false]);
        }
        $fields = [];

        if ($definition->getClass()) {
            /** @var Connection $connection */
            $connection = $this->getDoctrine()->getConnection();
            $schema = $connection->getSchemaManager();
            $columns = $schema->listTableColumns($definition->getClass());
            foreach ($columns as $column) {
                $resultField = $this->getDbFieldConfiguration($column);
                $resultField->setType('object');
                $fields[] = $resultField;
            }
        }
        return $this->viewHandler->handle([
            'success' => true,
            'fields' => $fields,
            'bricks' => [],
            'fieldcollections' => [],
        ]);
    }

    /**
     * @return array
     */
    protected function getSystemFields()
    {
        $systemColumns = [
            [
                'name' => 'id',
                'fieldtype' => 'numeric',
                'title' => 'ID',
            ],
            [
                'name' => 'key',
                'fieldtype' => 'input',
                'title' => 'Key',
            ],
            [
                'name' => 'path',
                'fieldtype' => 'input',
                'title' => 'Path',
            ],
            [
                'name' => 'published',
                'fieldtype' => 'input',
                'title' => 'Published',
            ],
            [
                'name' => 'creationDate',
                'fieldtype' => 'datetime',
                'title' => 'Creation Date',
            ],
            [
                'name' => 'modificationDate',
                'fieldtype' => 'datetime',
                'title' => 'Modification Date',
            ],
            [
                'name' => 'custom',
                'fieldtype' => 'input',
                'title' => 'Custom',
            ],
            [
                'name' => 'children',
                'fieldtype' => 'input',
                'title' => 'Children',
            ],
            [
                'name' => 'parentId',
                'fieldtype' => 'numeric',
                'title' => 'Parent Id'
            ]
        ];

        $result = [];

        foreach ($systemColumns as $sysColumn) {
            $toColumn = new FromColumn();

            $toColumn->setLabel($sysColumn['title']);
            $toColumn->setFieldtype($sysColumn['fieldtype']);
            $toColumn->setIdentifier($sysColumn['name']);
            $toColumn->setType('system');
            $toColumn->setGroup('systemfields');

            $result[] = $toColumn;
        }

        return $result;
    }

    /**
     * @param DataObject\ClassDefinition\Data $field
     * @param string $group
     * @return FromColumn
     */
    protected function getFieldConfiguration(DataObject\ClassDefinition\Data $field, $group = 'fields'): FromColumn
    {
        $fromColumn = new FromColumn();

        $fromColumn->setLabel($field->getTitle());
        $fromColumn->setFieldtype($field->getFieldtype());
        $fromColumn->setIdentifier($field->getName());
        $fromColumn->setGroup($group);

        return $fromColumn;
    }

    /**
     * @param Column $column
     * @param string $group
     * @return FromColumn
     */
    protected function getDbFieldConfiguration(Column $column, $group = 'fields'): FromColumn
    {
        $fromColumn = new FromColumn();
        $fromColumn->setLabel($column->getName());
        // convert to pimcore datatype
        switch ($column->getType()->getName()) {
            case 'integer':
                $type = 'numeric';
                break;
            case 'string':
                $type = 'input';
                break;
            default:
                $type = $column->getType()->getName();
        }
        $fromColumn->setFieldtype($type);
        $fromColumn->setIdentifier($column->getName());
        $fromColumn->setGroup($group);

        return $fromColumn;
    }

    /**
     * @return array
     */
    protected function getConfigProviders(): array
    {
        return $this->getParameter('data_definitions.export_providers');
    }

    /**
     * @return array
     */
    protected function getConfigInterpreters(): array
    {
        return $this->getParameter('data_definitions.interpreters');
    }

    /**
     * @return array
     */
    protected function getConfigRunners(): array
    {
        return $this->getParameter('data_definitions.export_runners');
    }

    /**
     * @return array
     */
    protected function getConfigGetters(): array
    {
        return $this->getParameter('data_definitions.getters');
    }

    /**
     * @return array
     */
    protected function getConfigFetchers(): array
    {
        return $this->getParameter('data_definitions.fetchers');
    }

    /**
     * @return array
     */
    protected function getImportRuleConditions(): array
    {
        return $this->getParameter('data_definitions.import_rule.conditions');
    }

    /**
     * @return array
     */
    protected function getImportRuleActions(): array
    {
        return $this->getParameter('data_definitions.import_rule.actions');
    }
}

