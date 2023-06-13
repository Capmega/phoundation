<?php

declare(strict_types=1);

namespace Phoundation\Processes;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryName;
use Phoundation\Data\DataEntry\Traits\DataEntryResults;
use Phoundation\Data\Interfaces\DataEntryInterface;
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
     * @param DataEntryInterface|string|int|null $identifier
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null)
    {
        $this->entry_name   = 'process task';

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
     * @param DefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions
            ->add(Definition::new('name')
                ->setLabel(tr('Name'))
                ->setOptional(true)
                ->setSize(4)
                ->setMaxlength(64)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isName();
                }))
            ->add(Definition::new('seo_name')
                ->setVisible(false))
            ->add(Definition::new('send_to')
                ->setVisible(false)
                ->setMaxlength(128)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isEmail();
                }))
            ->add(Definition::new('execute_after')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Execute after')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isDateTime();
                }))
            ->add(Definition::new('execute_on')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Executed on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isDateTime();
                }))
            ->add(Definition::new('finished_on')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Finished on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isDateTime();
                }))
            ->add(Definition::new('send_to_id')
                ->setInputType(InputType::select)
                ->setLabel('Send to user')
                ->setSource('SELECT `id`, CONCAT(`email`, " <", `firstnames`, " ", `lastnames`, ">") FROM `accounts_users` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isId();
                }))
            ->add(Definition::new('parents_id')
                ->setInputType(InputType::select)
                ->setLabel('Parent task')
                ->setSource('SELECT `id`, CONCAT(`email`, " (", `name`, ")") FROM `processes_tasks` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isId();
                }))
            ->add(Definition::new('time_limit')
                ->setInputType(InputType::numeric)
                ->setLabel('Time limit')
                ->setSize(4)
                ->setMin(0)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isPositive(true);
                }))
            ->add(Definition::new('time_spent')
                ->setInputType(InputType::numeric)
                ->setLabel('Time spent')
                ->setDisabled(true)
                ->setMin(0)
                ->setSize(4)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isPositive(true);
                }))
            ->add(Definition::new('parallel')
                ->setInputType(InputType::checkbox)
                ->setLabel('Execute parallel')
                ->setSize(4)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isBoolean();
                }))
            ->add(Definition::new('Verbose')
                ->setInputType(InputType::checkbox)
                ->setLabel('Verbose output')
                ->setSize(4)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isBoolean();
                }))
            ->add(Definition::new('pid')
                ->setInputType(InputType::numeric)
                ->setLabel('Process ID')
                ->setDisabled(true)
                ->setSize(4)
                ->addValidationFunction(function(InterfaceDataValidator $validator) {
                    $validator->isId();
                }))
            ->add(Definition::new('command')
                ->setLabel('Command')
                ->setSize(12)
                ->setMaxlength(128))
            ->add(Definition::new('arguments')
                ->setLabel('Arguments')
                ->setSize(12)
                ->setMaxlength(65_535))
            ->add(Definition::new('executed_command')
                ->setLabel('Executed command')
                ->setSize(12)
                ->setMaxlength(65_663))
            ->add(Definition::new('results')
                ->setLabel('Results')
                ->setSize(12)
                ->setReadonly(true))
            ->add(DefinitionFactory::new('description')
                ->setHelpText(tr('A description for this task')));
    }
}