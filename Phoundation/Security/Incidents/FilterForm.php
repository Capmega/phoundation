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

namespace Phoundation\Security\Incidents;

use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Databases\Sql\Interfaces\QueryBuilderInterface;
use Phoundation\Databases\Sql\SqlQueries;
use Phoundation\Os\Tasks\Task;
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

        $this->o_definitions->get('status')->setRender(false);

        // Set basic definitions
        $this->o_definitions
             ->add(Definition::new('severity')
                             ->setLabel(tr('Severity'))
                             ->setSize(4)
                             ->setOptional(true)
                             ->setInputType(EnumInputType::text)
                             ->setOutput(function (DefinitionInterface $o_definition, string $key, string $field_name, array $source) {
                                 return Severities::new()
                                                  ->getHtmlSelectOld()
                                                  ->setAutoSubmit(true)
                                                  ->setName($field_name)
                                                  ->setSelected(isset_get($this->source[$key], 'medium'));
                             }));
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


    /**
     * Render this FilterForm
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // TODO Remove next line to implement support for clearing incidents
        return parent::render();

        $task_in_progress = (bool) Task::new()->loadNullOrNull(['name' => 'clearing incidents']);

        return parent::render() .
               Form::new()
                   ->setRequestMethod(EnumHttpRequestMethod::post)
                   ->setContent(Span::new()
                                    ->setContent(Button::new()
                                                       ->addClass('mr-2')
                                                       ->setContent(tr('Clear incidents'))
                                                       ->setDisabled($task_in_progress) . ($task_in_progress ? 'This task is already in progress' : '')));

        return $return;
    }
}
