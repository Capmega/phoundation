<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryName;
use Phoundation\Data\DataEntry\Traits\DataEntryResults;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Os\Processes\Exception\TasksException;
use Phoundation\Web\Html\Enums\InputType;


/**
 * Class Task
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class Task extends DataEntry
{
    use DataEntryName;
    use DataEntryResults;
    use DataEntryDescription;


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'processes_tasks';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'process task';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'code';
    }


    /**
     * Executes this task, and stores all relevant results data in the database
     *
     * @return static
     */
    public function execute(): static
    {
        if ($this->getExecutedOn()) {
            throw new TasksException(tr('Cannot execute task ":id", it is already being executed', [
                ':id' => $this->getId()
            ]));
        }

        return $this;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new($this, 'name')
                ->setLabel(tr('Name'))
                ->setOptional(true)
                ->setSize(4)
                ->setMaxlength(64)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isName();
                }))
            ->addDefinition(Definition::new($this, 'seo_name')
                ->setVisible(false))
            ->addDefinition(Definition::new($this, 'send_to')
                ->setVisible(false)
                ->setMaxlength(128)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isEmail();
                }))
            ->addDefinition(Definition::new($this, 'execute_after')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Execute after')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new($this, 'execute_on')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Executed on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new($this, 'finished_on')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Finished on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new($this, 'send_to_id')
                ->setInputType(InputType::select)
                ->setLabel('Send to user')
                ->setSource('SELECT `id`, CONCAT(`email`, " <", `firstnames`, " ", `lastnames`, ">") FROM `accounts_users` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'parents_id')
                ->setInputType(InputType::select)
                ->setLabel('Parent task')
                ->setSource('SELECT `id`, CONCAT(`email`, " (", `name`, ")") FROM `processes_tasks` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'time_limit')
                ->setInputType(InputType::number)
                ->setLabel('Time limit')
                ->setSize(4)
                ->setMin(0)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isPositive(true);
                }))
            ->addDefinition(Definition::new($this, 'time_spent')
                ->setInputType(InputType::number)
                ->setLabel('Time spent')
                ->setDisabled(true)
                ->setMin(0)
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isPositive(true);
                }))
            ->addDefinition(Definition::new($this, 'parallel')
                ->setInputType(InputType::checkbox)
                ->setLabel('Execute parallel')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'Verbose')
                ->setInputType(InputType::checkbox)
                ->setLabel('Verbose output')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'pid')
                ->setInputType(InputType::number)
                ->setLabel('Process ID')
                ->setDisabled(true)
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'command')
                ->setLabel('Command')
                ->setSize(12)
                ->setMaxlength(128))
            ->addDefinition(Definition::new($this, 'arguments')
                ->setLabel('Arguments')
                ->setSize(12)
                ->setMaxlength(65_535))
            ->addDefinition(Definition::new($this, 'executed_command')
                ->setLabel('Executed command')
                ->setSize(12)
                ->setMaxlength(65_663))
            ->addDefinition(Definition::new($this, 'results')
                ->setLabel('Results')
                ->setSize(12)
                ->setReadonly(true))
            ->addDefinition(DefinitionFactory::getDescription($this)
                ->setHelpText(tr('A description for this task')));
    }
}
