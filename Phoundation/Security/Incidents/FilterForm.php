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
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Web\Html\Enums\EnumElement;


class FilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    public function __construct(?string $content = null)
    {
        // Pull all filter data from HTTP GET
        $this->source = GetValidator::new()
            ->select('date_range')->isOptional()->copyTo('date_range_split')->doNotValidate()
            ->select('date_range_split')->isOptional($this->getDateRangeDefault())->sanitizeForceArray(' - ')->each()->isDate()
            ->select('users_id')->isOptional()->isDbId()
            ->select('severity')->isOptional('medium')->isInArray(Severities::new())
            ->validate(false);

        parent::__construct($content);

        // Set basic definitions
        $this->definitions
             ->add(Definition::new(null, 'severity')
                 ->setLabel(tr('Severity'))
                 ->setSize(2)
                 ->setOptional(true)
                 ->setElement(EnumElement::select)
                 ->setContent(function (DefinitionInterface $definition, string $key, string $field_name, array $source) {
                     return Severities::new()->getHtmlSelect()
                                             ->setAutoSubmit(true)
                                             ->setName($field_name)
                                             ->setSelected(isset_get($this->source[$key], 'medium'));
                 }));
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
