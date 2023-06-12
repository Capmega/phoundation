<?php

namespace Phoundation\Processes;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinition;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryName;
use Phoundation\Data\DataEntry\Traits\DataEntryResults;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\Interfaces\InterfaceDataValidator;
use Phoundation\Processes\Exception\TasksException;
use Phoundation\Web\Http\Html\Enums\InputType;


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
     * Country class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'process task';

        parent::__construct($identifier);
    }


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
     * Executes this task, and stores all relevant results data in the database
     *
     * @return void
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
     * @param DataEntryFieldDefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DataEntryFieldDefinitionsInterface $field_definitions): void
    {
        $field_definitions
            ->add(DataEntryFieldDefinition::new('name')
                ->setLabel(tr('Name'))
                ->setOptional(true)
                ->setSize(4)
                ->setMaxlength(64)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isName();
                }))
            ->add(DataEntryFieldDefinition::new('seo_name')
                ->setVisible(false))
            ->add(DataEntryFieldDefinition::new('send_to')
                ->setVisible(false)
                ->setMaxlength(128)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isEmail();
                }))
            ->add(DataEntryFieldDefinition::new('execute_after')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Execute after')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isDateTime();
                }))
            ->add(DataEntryFieldDefinition::new('execute_on')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Executed on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isDateTime();
                }))
            ->add(DataEntryFieldDefinition::new('finished_on')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Finished on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isDateTime();
                }))
            ->add(DataEntryFieldDefinition::new('send_to_id')
                ->setInputType(InputType::select)
                ->setLabel('Send to user')
                ->setSource('SELECT `id`, CONCAT(`email`, " <", `firstnames`, " ", `lastnames`, ">") FROM `accounts_users` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isId();
                }))
            ->add(DataEntryFieldDefinition::new('parents_id')
                ->setInputType(InputType::select)
                ->setLabel('Parent task')
                ->setSource('SELECT `id`, CONCAT(`email`, " (", `name`, ")") FROM `processes_tasks` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isId();
                }))
            ->add(DataEntryFieldDefinition::new('time_limit')
                ->setInputType(InputType::numeric)
                ->setLabel('Time limit')
                ->setSize(4)
                ->setMin(0)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isPositive(true);
                }))
            ->add(DataEntryFieldDefinition::new('time_spent')
                ->setInputType(InputType::numeric)
                ->setLabel('Time spent')
                ->setDisabled(true)
                ->setMin(0)
                ->setSize(4)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isPositive(true);
                }))
            ->add(DataEntryFieldDefinition::new('parallel')
                ->setInputType(InputType::checkbox)
                ->setLabel('Execute parallel')
                ->setSize(4)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isBoolean();
                }))
            ->add(DataEntryFieldDefinition::new('Verbose')
                ->setInputType(InputType::checkbox)
                ->setLabel('Verbose output')
                ->setSize(4)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isBoolean();
                }))
            ->add(DataEntryFieldDefinition::new('pid')
                ->setInputType(InputType::numeric)
                ->setLabel('Process ID')
                ->setDisabled(true)
                ->setSize(4)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isId();
                }))
            ->add(DataEntryFieldDefinition::new('command')
                ->setLabel('Command')
                ->setSize(12)
                ->setMaxlength(128))
            ->add(DataEntryFieldDefinition::new('arguments')
                ->setLabel('Arguments')
                ->setSize(12)
                ->setMaxlength(65535))
            ->add(DataEntryFieldDefinition::new('executed_command')
                ->setLabel('Executed command')
                ->setSize(12)
                ->setMaxlength(65663))
            ->add(DataEntryFieldDefinition::new('results')
                ->setLabel('Results')
                ->setSize(12)
                ->setReadonly(true))
            ->add(DataEntryFieldDefinition::new('description')
                ->setLabel('description')
                ->setSize(12)
                ->setMaxlength(65535)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isDescription();
                }));
    }
}