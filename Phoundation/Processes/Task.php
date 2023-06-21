<?php

declare(strict_types=1);

namespace Phoundation\Processes;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryName;
use Phoundation\Data\DataEntry\Traits\DataEntryResults;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
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
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = true)
    {
        $this->table        = 'processes_tasks';
        $this->entry_name   = 'process task';

        parent::__construct($identifier, $init);
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
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new('name')
                ->setLabel(tr('Name'))
                ->setOptional(true)
                ->setSize(4)
                ->setMaxlength(64)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isName();
                }))
            ->addDefinition(Definition::new('seo_name')
                ->setVisible(false))
            ->addDefinition(Definition::new('send_to')
                ->setVisible(false)
                ->setMaxlength(128)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isEmail();
                }))
            ->addDefinition(Definition::new('execute_after')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Execute after')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new('execute_on')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Executed on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new('finished_on')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Finished on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new('send_to_id')
                ->setInputType(InputType::select)
                ->setLabel('Send to user')
                ->setSource('SELECT `id`, CONCAT(`email`, " <", `firstnames`, " ", `lastnames`, ">") FROM `accounts_users` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isId();
                }))
            ->addDefinition(Definition::new('parents_id')
                ->setInputType(InputType::select)
                ->setLabel('Parent task')
                ->setSource('SELECT `id`, CONCAT(`email`, " (", `name`, ")") FROM `processes_tasks` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isId();
                }))
            ->addDefinition(Definition::new('time_limit')
                ->setInputType(InputType::number)
                ->setLabel('Time limit')
                ->setSize(4)
                ->setMin(0)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isPositive(true);
                }))
            ->addDefinition(Definition::new('time_spent')
                ->setInputType(InputType::number)
                ->setLabel('Time spent')
                ->setDisabled(true)
                ->setMin(0)
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isPositive(true);
                }))
            ->addDefinition(Definition::new('parallel')
                ->setInputType(InputType::checkbox)
                ->setLabel('Execute parallel')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new('Verbose')
                ->setInputType(InputType::checkbox)
                ->setLabel('Verbose output')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new('pid')
                ->setInputType(InputType::number)
                ->setLabel('Process ID')
                ->setDisabled(true)
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isId();
                }))
            ->addDefinition(Definition::new('command')
                ->setLabel('Command')
                ->setSize(12)
                ->setMaxlength(128))
            ->addDefinition(Definition::new('arguments')
                ->setLabel('Arguments')
                ->setSize(12)
                ->setMaxlength(65_535))
            ->addDefinition(Definition::new('executed_command')
                ->setLabel('Executed command')
                ->setSize(12)
                ->setMaxlength(65_663))
            ->addDefinition(Definition::new('results')
                ->setLabel('Results')
                ->setSize(12)
                ->setReadonly(true))
            ->addDefinition(DefinitionFactory::getDescription()
                ->setHelpText(tr('A description for this task')));
    }
}