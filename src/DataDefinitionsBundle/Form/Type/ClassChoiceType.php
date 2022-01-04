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

namespace Wvision\Bundle\DataDefinitionsBundle\Form\Type;

use Pimcore\Db\Connection;
use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ClassChoiceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $classes = new ClassDefinition\Listing();
        $classes = $classes->load();

        $choices = [];

        foreach ($classes as $class) {
            $className = $class->getName();
            $choices[$className] = $className;
        }

        // we also allow db tables in form
        /** @var Connection $connection */
        $connection = \Pimcore::getKernel()->getContainer()->get('database_connection');
        if ($connection) {
            $tableList = $connection->getSchemaManager()->listTables();
            foreach ($tableList as $table) {
                $tableName = $table->getName();
                $choices[$tableName] = $tableName;
            }
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}

