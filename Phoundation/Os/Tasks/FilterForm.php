<?php

/**
 * Class FilterForm
 *
 * This class manages the FilterForm object for the security pages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

namespace Phoundation\Os\Tasks;

use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Web\Html\Enums\EnumInputType;


class FilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    public function __construct(?string $source = null)
    {
        parent::__construct($source);

        // Set basic definitions
        $this->getDefinitionsObject()->add(Definition::new('category')
                                                     ->setLabel(tr('Category'))
                                                     ->setSize(4)
                                                     ->setOptional(true)
                                                     ->setInputType(EnumInputType::text)
                                                     ->setOutput(function (DefinitionInterface $_definition, string $key, string $field_name, array $source) {
//
//                                                         $tasks_category = Category::new()->load(['name' => 'tasks']);
//
//                                                         return Categories::new()
//                                                                          ->setParentsId($tasks_category->getId())
//                                                                          ->getHtmlSelectOld()
//                                                                          ->setAutoSubmit(true)
//                                                                          ->setName($field_name)
//                                                                          ->setSelected(isset_get($this->source[$key], 'medium'));
//
                                                         return null;
                                                     }));
    }


    /**
     * Automatically apply current filters to the query builder
     *
     * @param QueryBuilderInterface $_builder
     *
     * @return static
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $_builder): static
    {
        if ($this->_applied_filters->keyExists('category') and $this->_definitions->isRendered('category', false)) {
//            if ($this->getCategoriesId()) {
//                $values = QueryBuilder::in($this->getCategoriesId());
//                $_builder->addWhere('`security_incidents`.`severity` IN (' . QueryBuilder::inColumns($values) . ')', $values);
//            }
        }

        $this->_applied_filters->removeKeys([
            'severity',
        ]);

        return parent::applyFiltersToQueryBuilder($_builder);
    }
}
