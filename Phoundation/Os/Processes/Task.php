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
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Exception\TasksException;
use Phoundation\Web\Html\Enums\InputElement;
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
            ->addDefinition(Definition::new($this, 'parents_id')
                ->setInputType(InputType::select)
                ->setLabel('Parent task')
                ->setSource('SELECT `id`, CONCAT(`email`, " (", `name`, ")") FROM `os_tasks` WHERE (`status` IS NULL OR `status` NOT IN ("deleted"))')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'execute_after')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Execute after')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new($this, 'start')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Executed on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new($this, 'stop')
                ->setInputType(InputType::datetime_local)
                ->setLabel('Finished on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new($this, 'send_to')
                ->setVisible(false)
                ->setMaxlength(128)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isEmail();
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
            ->addDefinition(Definition::new($this, 'servers_id')
                ->setInputType(InputType::select)
                ->setLabel('Execute on server')
                ->setSource('SELECT `id` FROM `servers` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'notifications_groups_id')
                ->setInputType(InputType::select)
                ->setLabel('Execute on server')
                ->setSource('SELECT `id` FROM `notifications_groups` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'execution_path')
                ->setInputType(InputType::text)
                ->setLabel('Execution path')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDirectory('/', Restrictions::writable('/'));
                }))
            ->addDefinition(Definition::new($this, 'command')
                ->setInputType(InputType::text)
                ->setLabel('Command')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'executed_command')
                ->setReadonly(true)
                ->setInputType(InputType::text)
                ->setLabel('Command')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'arguments')
                ->setInputType(InputType::text)
                ->setLabel('Arguments')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'variables')
                ->setInputType(InputType::text)
                ->setLabel('Argument variables')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'environment_variables')
                ->setInputType(InputType::text)
                ->setLabel('Environment variables')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'time_spent')
                ->setInputType(InputType::number)
                ->setLabel('Time spent')
                ->setDisabled(true)
                ->setMin(0)
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isPositive(true);
                }))
            ->addDefinition(Definition::new($this, 'background')
                ->setInputType(InputType::checkbox)
                ->setLabel('Execute in background')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'clear_logs')
                ->setInputType(InputType::checkbox)
                ->setLabel('Clear logs')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'debug')
                ->setInputType(InputType::checkbox)
                ->setLabel('Run in debug mode')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'escape_quotes')
                ->setInputType(InputType::checkbox)
                ->setLabel('Escape quotes')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'nocache')
                ->setInputType(InputType::select)
                ->setLabel('No cache mode')
                ->setSource([

                ])
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                }))
            ->addDefinition(Definition::new($this, 'ionice')
                ->setInputType(InputType::select)
                ->setLabel('IO Nice')
                ->setSource([

                ])
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                }))
            ->addDefinition(Definition::new($this, 'ionice')
                ->setInputType(InputType::select)
                ->setLabel('IO nice')
                ->setSource([
                    0 => 'none',
                    1 => 'realtime',
                    2 => 'best_effort',
                    3 => 'idle',
                ])
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'ionice_level')
                ->setInputType(InputType::number)
                ->setLabel('IO nice level')
                ->setMin(0)
                ->setMax(7)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'nice')
                ->setInputType(InputType::number)
                ->setLabel('Nice level')
                ->setOptional(true, 0)
                ->setMin(-20)
                ->setMax(20)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'timeout')
                ->setInputType(InputType::number)
                ->setLabel('Time limit')
                ->setOptional(true, 0)
                ->setMin(0)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'wait')
                ->setInputType(InputType::number)
                ->setLabel('Start wait')
                ->setOptional(true, 0)
                ->setMin(0)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'verbose')
                ->setInputType(InputType::checkbox)
                ->setLabel('Verbose output')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'quiet')
                ->setInputType(InputType::checkbox)
                ->setLabel('Quiet')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'sudo')
                ->setLabel('Sudo required / command')
                ->setSize(6)
                ->setMaxlength(32))
            ->addDefinition(Definition::new($this, 'term')
                ->setLabel('Terminal command')
                ->setSize(6)
                ->setMaxlength(32))
            ->addDefinition(Definition::new($this, 'pipe')
                ->setLabel('Pipe to')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'input_redirect')
                ->setLabel('Input redirect')
                ->setSize(6)
                ->setMaxlength(64))
            ->addDefinition(Definition::new($this, 'output_redirect')
                ->setLabel('Output redirect')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'restrictions')
                ->setLabel('Restrictions')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'packages')
                ->setLabel('Packages')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'pre_exec')
                ->setLabel('Pre execute')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'post_exec')
                ->setLabel('Post execute')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'command')
                ->setLabel('Command')
                ->setSize(6)
                ->setMaxlength(64))
            ->addDefinition(Definition::new($this, 'accepted_exit_codes')
                ->setLabel('Accepted Exit Codes')
                ->setSize(6)
                ->setMaxlength(64))
            ->addDefinition(Definition::new($this, 'arguments')
                ->setLabel('Arguments')
                ->setSize(12)
                ->setMaxlength(65_535))
            ->addDefinition(Definition::new($this, 'executed_command')
                ->setLabel('Executed command')
                ->setElement(InputElement::textarea)
                ->setSize(12)
                ->setMaxlength(65_535))
            ->addDefinition(Definition::new($this, 'results')
                ->setLabel('Results')
                ->setElement(InputElement::textarea)
                ->setSize(12)
                ->setMaxlength(16_777_215)
                ->setReadonly(true))
            ->addDefinition(Definition::new($this, 'pid')
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setLabel('Process ID')
                ->setDisabled(true)
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'exit_code')
                ->setReadonly(true)
                ->setLabel('Exit code')
                ->setInputType(InputType::number)
                ->setSize(2)
                ->setMin(0)
                ->setMax(255))
            ->addDefinition(Definition::new($this, 'results')
                ->setLabel('Results')
                ->setElement(InputElement::textarea)
                ->setSize(12)
                ->setMaxlength(16_777_215)
                ->setReadonly(true))
            ->addDefinition(Definition::new($this, 'log_file')
                ->setReadonly(true)
                ->setLabel('Log file')
                ->setInputType(InputType::text)
                ->setSize(6)
                ->setLength(512))
            ->addDefinition(Definition::new($this, 'pid_file')
                ->setReadonly(true)
                ->setLabel('PID file')
                ->setInputType(InputType::text)
                ->setSize(6)
                ->setLength(512))
            ->addDefinition(DefinitionFactory::getComments($this)
                ->setHelpText(tr('A description for this task')));
    }
}
