<?php

/**
 * Class FilterForm
 *
 * This class manages the FilterForm object for the security pages
 *
 * @author    Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license   This plugin is developed by Medinet and may only be used by others with explicit written authorization
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */


declare(strict_types=1);

namespace Phoundation\Os\Tasks;

use Phoundation\Data\Categories\Categories;
use Phoundation\Data\Categories\Category;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Os\Tasks\Task;
use Phoundation\Security\Incidents\Severities;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Span;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
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
                                                     ->setOutput(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
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

        // Auto apply
        $this->applyValidator(self::class);
    }


    /**
     * Automatically apply current filters to the query builder
     *
     * @param QueryBuilderInterface $o_builder
     *
     * @return static
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $o_builder): static
    {
        if ($this->o_applied_filters->keyExists('category') and $this->o_definitions->isRendered('category', false)) {
//            if ($this->getCategoriesId()) {
//                $values = SqlQueries::in($this->getCategoriesId());
//                $o_builder->addWhere('`security_incidents`.`severity` IN (' . SqlQueries::inColumns($values) . ')', $values);
//            }
        }

        $this->o_applied_filters->removeKeys([
            'severity',
        ]);

        return parent::applyFiltersToQueryBuilder($o_builder);
    }
}