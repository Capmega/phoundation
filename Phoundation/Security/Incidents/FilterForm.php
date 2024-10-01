<?php

/**
 * Class FilterForm
 *
 * This class manages the FilterForm object for the security pages
 *
 * @author    Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license   This plugin is developed by Medinet and may only be used by others with explicit written authorization
 * @copyright Copyright (c) 2024 Medinet <copyright@medinet.ca>
 * @package Phoundation\Security
 */


declare(strict_types=1);

namespace Phoundation\Security\Incidents;

use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Web\Html\Enums\EnumInputType;


class FilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    public function __construct(?string $content = null)
    {
        parent::__construct($content);

        $this->definitions->get('status')->setRender(false);

        // Set basic definitions
        $this->definitions
             ->add(Definition::new(null, 'severity')
                 ->setLabel(tr('Severity'))
                 ->setSize(4)
                 ->setOptional(true)
                 ->setInputType(EnumInputType::text)
                 ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                     return Severities::new()->getHtmlSelect()
                                             ->setAutoSubmit(true)
                                             ->setName($field_name)
                                             ->setSelected(isset_get($this->source[$key], 'medium'));
                 }));

        // Auto apply
        $this->applyValidator(self::class);
    }


    /**
     * Automatically apply current filters to the query builder
     *
     * @param QueryBuilderInterface $builder
     *
     * @return $this
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $builder): static
    {
        if ($this->getSeverities()) {
            $values = SqlQueries::in($this->getSeverities());
            $builder->addWhere('`security_incidents`.`severity` IN (' . SqlQueries::inColumns($values) . ')', $values);
        }

        return parent::applyFiltersToQueryBuilder($builder);
    }


    /**
     * Returns what severities should be filtered on
     *
     * @return array
     */
    public function getSeverities(): array
    {
        static $return;

        if (!isset($return)) {
            $severity = $this->get('severity');

            if (empty($severity)) {
                $severity = 'medium';
            }

            $return = match ($severity) {
                'notice' => ['notice', 'low', 'medium', 'high', 'severe'],
                'low'    => ['low', 'medium', 'high', 'severe'],
                'medium' => ['medium', 'high', 'severe'],
                'high'   => ['high', 'severe'],
                'severe' => ['severe'],
            };
        }

        return $return;
    }
}
