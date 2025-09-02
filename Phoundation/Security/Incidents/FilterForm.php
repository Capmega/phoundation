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

namespace Phoundation\Security\Incidents;

use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Web\Html\Enums\EnumInputType;


class FilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    public function __construct(?string $source = null)
    {
        parent::__construct($source);

        $this->o_definitions->get('status')->setRender(false);

        // Set basic definitions
        $this->o_definitions
             ->add(Definition::new('severity')
                 ->setLabel(tr('Severity'))
                 ->setSize(4)
                 ->setOptional(true)
                 ->setInputType(EnumInputType::text)
                 ->setContent(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
                     return Severities::new()->getHtmlSelectOld()
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
     * @param QueryBuilderInterface $o_builder
     *
     * @return static
     */
    public function applyFiltersToQueryBuilder(QueryBuilderInterface $o_builder): static
    {
        if ($this->o_applied_filters->keyExists('severity') and $this->o_definitions->isRendered('severity', false)) {
            if ($this->getSeverities()) {
                $values = SqlQueries::in($this->getSeverities());
                $o_builder->addWhere('`security_incidents`.`severity` IN (' . SqlQueries::inColumns($values) . ')', $values);
            }
        }

        $this->o_applied_filters->removeKeys([
            'severity',
        ]);

        return parent::applyFiltersToQueryBuilder($o_builder);
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
