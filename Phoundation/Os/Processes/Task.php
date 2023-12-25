<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryName;
use Phoundation\Data\DataEntry\Traits\DataEntryResults;
use Phoundation\Data\DataEntry\Traits\DataEntryStart;
use Phoundation\Data\DataEntry\Traits\DataEntryStop;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;
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
    use DataEntryStart;
    use DataEntryStop;
    use DataEntryServer;
    use DataEntryTask;
    use DataEntryName;
    use DataEntryResults;
    use DataEntryDescription;


    /**
     * Returns the parents_id for this object
     *
     * @return int|null
     */
    public function getParentsId(): ?int
    {
        return $this->getSourceFieldValue('int', 'parents_id');
    }


    /**
     * Sets the parents_id for this object
     *
     * @param int|null $parents_id
     * @return static
     */
    public function setParentsId(?int $parents_id): static
    {
        return $this->setSourceValue('parents_id', $parents_id);
    }


    /**
     * Returns the datetime after which this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getExecuteAfter(): ?DateTimeInterface
    {
        return $this->getSourceFieldValue('int', 'execute_after');
    }


    /**
     * Sets the datetime after which this task should be executed
     *
     * @param DateTimeInterface|string|null $execute_after
     * @return static
     */
    public function setExecuteAfter(DateTimeInterface|string|null $execute_after): static
    {
        return $this->setSourceValue('execute_after', $execute_after ? new DateTime($execute_after, 'system') : null);
    }


    /**
     * Returns the datetime after which this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getStart(): ?DateTimeInterface
    {
        return $this->getSourceFieldValue('datetime', 'start');
    }


    /**
     * Sets the datetime after which this task should be executed
     *
     * @param DateTimeInterface|string|null $start
     * @return static
     */
    public function setStart(DateTimeInterface|string|null $start): static
    {
        return $this->setSourceValue('start', $start ? new DateTime($start, 'system') : null);
    }


    /**
     * Returns the datetime after which this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getStop(): ?DateTimeInterface
    {
        return $this->getSourceFieldValue('datetime', 'stop');
    }


    /**
     * Sets the datetime after which this task should be executed
     *
     * @param DateTimeInterface|string|null $stop
     * @return static
     */
    public function setStop(DateTimeInterface|string|null $stop): static
    {
        return $this->setSourceValue('stop', $stop ? new DateTime($stop, 'system') : null);
    }


    /**
     * Returns the servers_id for where this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getServersId(): ?DateTimeInterface
    {
        return $this->getSourceFieldValue('int', 'servers_id');
    }


    /**
     * Sets the servers_id for where this task should be executed
     *
     * @param int|null $servers_id
     * @return static
     */
    public function setServersId(int|null $servers_id): static
    {
        return $this->setSourceValue('servers_id', get_null($servers_id));
    }


    /**
     * Returns the send_to_id for where this task should be executed
     *
     * @return int|null
     */
    public function getSendToId(): ?int
    {
        return $this->getSourceFieldValue('int', 'send_to_id');
    }


    /**
     * Sets the send_to_id for where this task should be executed
     *
     * @param int|null $send_to_id
     * @return static
     */
    public function setSendToId(int|null $send_to_id): static
    {
        return $this->setSourceValue('send_to_id', get_null($send_to_id));
    }


    /**
     * Returns the notifications_roles_id for where this task should be executed
     *
     * @return int|null
     */
    public function getNotificationsRolesId(): ?int
    {
        return $this->getSourceFieldValue('int', 'notifications_roles_id');
    }


    /**
     * Sets the notifications_roles_id for where this task should be executed
     *
     * @param int|null $notifications_roles_id
     * @return static
     */
    public function setNotificationsRolesId(int|null $notifications_roles_id): static
    {
        return $this->setSourceValue('notifications_roles_id', get_null($notifications_roles_id));
    }


    /**
     * Returns the pid for where this task should be executed
     *
     * @return int|null
     */
    public function getPid(): ?int
    {
        return $this->getSourceFieldValue('int', 'pid');
    }


    /**
     * Sets the pid for where this task should be executed
     *
     * @param int|null $pid
     * @return static
     */
    protected function setPid(int|null $pid): static
    {
        return $this->setSourceValue('pid', get_null($pid));
    }


    /**
     * Returns the exit_code for where this task should be executed
     *
     * @return int|null
     */
    public function getExitCode(): ?int
    {
        return $this->getSourceFieldValue('int', 'exit_code');
    }


    /**
     * Sets the exit_code for where this task should be executed
     *
     * @param int|null $exit_code
     * @return static
     */
    protected function setExitCode(int|null $exit_code): static
    {
        return $this->setSourceValue('exit_code', get_null($exit_code));
    }


    /**
     * Returns the nocache for where this task should be executed
     *
     * @return int|null
     */
    public function getNocache(): ?int
    {
        return $this->getSourceFieldValue('int', 'nocache');
    }


    /**
     * Sets the nocache for where this task should be executed
     *
     * @param int|null $nocache
     * @return static
     */
    public function setNocache(int|null $nocache): static
    {
        return $this->setSourceValue('nocache', get_null($nocache));
    }


    /**
     * Returns the ionice for where this task should be executed
     *
     * @return int|null
     */
    public function getIonice(): ?int
    {
        return $this->getSourceFieldValue('int', 'ionice');
    }


    /**
     * Sets the ionice for where this task should be executed
     *
     * @param int|null $ionice
     * @return static
     */
    public function setIonice(int|null $ionice): static
    {
        return $this->setSourceValue('ionice', get_null($ionice));
    }


    /**
     * Returns the ionice_level for where this task should be executed
     *
     * @return int|null
     */
    public function getIoniceLevel(): ?int
    {
        return $this->getSourceFieldValue('int', 'ionice_level');
    }


    /**
     * Sets the ionice_level for where this task should be executed
     *
     * @param int|null $ionice_level
     * @return static
     */
    public function setIoniceLevel(int|null $ionice_level): static
    {
        return $this->setSourceValue('ionice_level', get_null($ionice_level));
    }


    /**
     * Returns the nice for where this task should be executed
     *
     * @return int|null
     */
    public function getNice(): ?int
    {
        return $this->getSourceFieldValue('int', 'nice');
    }


    /**
     * Sets the nice for where this task should be executed
     *
     * @param int|null $nice
     * @return static
     */
    public function setNice(int|null $nice): static
    {
        return $this->setSourceValue('nice', get_null($nice));
    }


    /**
     * Returns the timeout for where this task should be executed
     *
     * @return int|null
     */
    public function getTimeout(): ?int
    {
        return $this->getSourceFieldValue('int', 'timeout');
    }


    /**
     * Sets the timeout for where this task should be executed
     *
     * @param int|null $timeout
     * @return static
     */
    public function setTimeout(int|null $timeout): static
    {
        return $this->setSourceValue('timeout', get_null($timeout));
    }


    /**
     * Returns the wait for where this task should be executed
     *
     * @return int|null
     */
    public function getWait(): ?int
    {
        return $this->getSourceFieldValue('int', 'wait');
    }


    /**
     * Sets the wait for where this task should be executed
     *
     * @param int|null $wait
     * @return static
     */
    public function setWait(int|null $wait): static
    {
        return $this->setSourceValue('wait', get_null($wait));
    }


    /**
     * Returns the background for where this task should be executed
     *
     * @return bool
     */
    public function getBackground(): bool
    {
        return $this->getSourceFieldValue('bool', 'background');
    }


    /**
     * Sets the background for where this task should be executed
     *
     * @param bool|null $background
     * @return static
     */
    public function setBackground(int|bool|null $background): static
    {
        return $this->setSourceValue('background', (bool) $background);
    }


    /**
     * Returns the clear_logs for where this task should be executed
     *
     * @return bool
     */
    public function getClearLogs(): bool
    {
        return $this->getSourceFieldValue('bool', 'clear_logs');
    }


    /**
     * Sets the clear_logs for where this task should be executed
     *
     * @param bool|null $clear_logs
     * @return static
     */
    public function setClearLogs(int|bool|null $clear_logs): static
    {
        return $this->setSourceValue('clear_logs', (bool) $clear_logs);
    }


    /**
     * Returns the debug for where this task should be executed
     *
     * @return bool
     */
    public function getDebugExecution(): bool
    {
        return $this->getSourceFieldValue('bool', 'debug_execution');
    }


    /**
     * Sets the debug for where this task should be executed
     *
     * @param bool|null $debug
     * @return static
     */
    public function setDebugExecution(int|bool|null $debug): static
    {
        return $this->setSourceValue('debug_execution', (bool) $debug);
    }


    /**
     * Returns if this task should escape quotes in the arguments
     *
     * @return bool
     */
    public function getEscapeQuotes(): bool
    {
        return $this->getSourceFieldValue('bool', 'escape_quotes');
    }


    /**
     * Sets if this task should escape quotes in the arguments
     *
     * @param bool|null $escape_quotes
     * @return static
     */
    public function setEscapeQuotes(int|bool|null $escape_quotes): static
    {
        return $this->setSourceValue('escape_quotes', (bool) $escape_quotes);
    }


    /**
     * Returns the log_file for this task
     *
     * @return string
     */
    public function getLogFile(): string
    {
        return $this->getSourceFieldValue('string', 'log_file');
    }


    /**
     * Sets the log_file for this task
     *
     * @param string|null $log_file
     * @return static
     */
    protected function setLogFile(string|null $log_file): static
    {
        return $this->setSourceValue('log_file', $log_file);
    }


    /**
     * Returns the pid_file for this task
     *
     * @return string
     */
    public function getPidFile(): string
    {
        return $this->getSourceFieldValue('string', 'pid_file');
    }


    /**
     * Sets the pid_file for this task
     *
     * @param string|null $pid_file
     * @return static
     */
    protected function setPidFile(string|null $pid_file): static
    {
        return $this->setSourceValue('pid_file', $pid_file);
    }


    /**
     * Returns the sudo string for this task
     *
     * @return string
     */
    public function getSudo(): string
    {
        return $this->getSourceFieldValue('string', 'sudo');
    }


    /**
     * Sets if this task should use sudo
     *
     * @param string|null $sudo
     * @return static
     */
    public function setSudo(string|null $sudo): static
    {
        return $this->setSourceValue('sudo', $sudo);
    }


    /**
     * Returns the term string for this task
     *
     * @return string
     */
    public function getTerm(): string
    {
        return $this->getSourceFieldValue('string', 'term');
    }


    /**
     * Sets if this task should use term
     *
     * @param string|null $term
     * @return static
     */
    public function setTerm(string|null $term): static
    {
        return $this->setSourceValue('term', $term);
    }


    /**
     * Returns where the ouput of this command should be piped to
     *
     * @return string
     */
    public function getPipe(): string
    {
        return $this->getSourceFieldValue('string', 'pipe');
    }


    /**
     * Sets where the ouput of this command should be piped to
     *
     * @param string|null $pipe
     * @return static
     */
    public function setPipe(string|null $pipe): static
    {
        return $this->setSourceValue('pipe', $pipe);
    }


    /**
     * Returns where the input should be redirected from
     *
     * @return string
     */
    public function getInputRedirect(): string
    {
        return $this->getSourceFieldValue('string', 'input_redirect');
    }


    /**
     * Sets where the input should be redirected from
     *
     * @param string|null $input_redirect
     * @return static
     */
    public function setInputRedirect(string|null $input_redirect): static
    {
        return $this->setSourceValue('input_redirect', $input_redirect);
    }


    /**
     * Returns where the output should be redirected from
     *
     * @return string
     */
    public function getOutputRedirect(): string
    {
        return $this->getSourceFieldValue('string', 'output_redirect');
    }


    /**
     * Sets where the output should be redirected from
     *
     * @param string|null $output_redirect
     * @return static
     */
    public function setOutputRedirect(string|null $output_redirect): static
    {
        return $this->setSourceValue('output_redirect', $output_redirect);
    }


    /**
     * Returns access restrictions for this task
     *
     * @return string
     */
    public function getRestrictions(): string
    {
        return $this->getSourceFieldValue('string', 'restrictions');
    }


    /**
     * Sets access restrictions for this task
     *
     * @param string|null $restrictions
     * @return static
     */
    public function setRestrictions(string|null $restrictions): static
    {
        return $this->setSourceValue('restrictions', $restrictions);
    }


    /**
     * Returns packages required for this task
     *
     * @return string
     */
    public function getPackages(): string
    {
        return $this->getSourceFieldValue('string', 'packages');
    }


    /**
     * Sets packages required for this task
     *
     * @param string|null $packages
     * @return static
     */
    public function setPackages(string|null $packages): static
    {
        return $this->setSourceValue('packages', $packages);
    }


    /**
     * Returns pre_exec for this task
     *
     * @return string
     */
    public function getPreExec(): string
    {
        return $this->getSourceFieldValue('string', 'pre_exec');
    }


    /**
     * Sets pre_exec for this task
     *
     * @param string|null $pre_exec
     * @return static
     */
    public function setPreExec(string|null $pre_exec): static
    {
        return $this->setSourceValue('pre_exec', $pre_exec);
    }


    /**
     * Returns post_exec for this task
     *
     * @return string
     */
    public function getPostExec(): string
    {
        return $this->getSourceFieldValue('string', 'post_exec');
    }


    /**
     * Sets post_exec for this task
     *
     * @param string|null $post_exec
     * @return static
     */
    public function setPostExec(string|null $post_exec): static
    {
        return $this->setSourceValue('post_exec', $post_exec);
    }


    /**
     * Returns comments for this task
     *
     * @return string
     */
    public function getComments(): string
    {
        return $this->getSourceFieldValue('string', 'comments');
    }


    /**
     * Sets comments for this task
     *
     * @param string|null $comments
     * @return static
     */
    public function setComments(string|null $comments): static
    {
        return $this->setSourceValue('comments', $comments);
    }


    /**
     * Returns results for this task
     *
     * @return string
     */
    public function getResults(): string
    {
        return $this->getSourceFieldValue('string', 'results');
    }


    /**
     * Sets results for this task
     *
     * @param string|null $results
     * @return static
     */
    protected function setResults(string|null $results): static
    {
        return $this->setSourceValue('results', $results);
    }


    /**
     * Returns execution_path for this task
     *
     * @return string
     */
    public function getExecutionPath(): string
    {
        return $this->getSourceFieldValue('string', 'execution_path');
    }


    /**
     * Sets execution_path for this task
     *
     * @param string|null $execution_path
     * @return static
     */
    public function setExecutionPath(string|null $execution_path): static
    {
        return $this->setSourceValue('execution_path', $execution_path);
    }


    /**
     * Returns command for this task
     *
     * @return string
     */
    public function getCommand(): string
    {
        return $this->getSourceFieldValue('string', 'command');
    }


    /**
     * Sets command for this task
     *
     * @param string|null $command
     * @return static
     */
    public function setCommand(string|null $command): static
    {
        return $this->setSourceValue('command', $command);
    }


    /**
     * Returns executed_command for this task
     *
     * @return string
     */
    public function getExecutedCommand(): string
    {
        return $this->getSourceFieldValue('string', 'executed_command');
    }


    /**
     * Sets executed_command for this task
     *
     * @param string|null $executed_command
     * @return static
     */
    protected function setExecutedCommand(string|null $executed_command): static
    {
        return $this->setSourceValue('executed_command', $executed_command);
    }


    /**
     * Returns arguments for this task
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->getSourceFieldValue('array', 'arguments');
    }


    /**
     * Sets arguments for this task
     *
     * @param array|null $arguments
     * @return static
     */
    public function setArguments(array|null $arguments): static
    {
        return $this->setSourceValue('arguments', $arguments);
    }


    /**
     * Returns variables for this task
     *
     * @return array
     */
    public function getVariables(): array
    {
        return $this->getSourceFieldValue('array', 'variables');
    }


    /**
     * Sets variables for this task
     *
     * @param array|null $variables
     * @return static
     */
    public function setVariables(array|null $variables): static
    {
        return $this->setSourceValue('variables', $variables);
    }


    /**
     * Returns environment_variables for this task
     *
     * @return array
     */
    public function getEnvironmentVariables(): array
    {
        return $this->getSourceFieldValue('array', 'environment_variables');
    }


    /**
     * Sets environment_variables for this task
     *
     * @param array|null $environment_variables
     * @return static
     */
    public function setEnvironmentVariables(array|null $environment_variables): static
    {
        return $this->setSourceValue('environment_variables', $environment_variables);
    }


    /**
     * Returns accepted_exit_codes for this task
     *
     * @return array
     */
    public function getAcceptedExitCodes(): array
    {
        return $this->getSourceFieldValue('array', 'accepted_exit_codes');
    }


    /**
     * Sets accepted_exit_codes for this task
     *
     * @param array|null $accepted_exit_codes
     * @return static
     */
    public function setAcceptedExitCodes(array|null $accepted_exit_codes): static
    {
        return $this->setSourceValue('accepted_exit_codes', $accepted_exit_codes);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'os_tasks';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Process task');
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
     * Returns the code for this object
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getSourceFieldValue('string', 'code');
    }


    /**
     * Executes this task, and stores all relevant results data in the database
     *
     * @param bool $immediately
     * @return static
     */
    public function execute(): static
    {
        if ($this->getStartedOn()) {
            if ($this->getStoppedOn()) {
                throw new TasksException(tr('Cannot execute task ":id", it has already been executed', [
                    ':id' => $this->getId()
                ]));
            }

            throw new TasksException(tr('Cannot execute task ":id", it is already being executed', [
                ':id' => $this->getId()
            ]));
        }

        if ($this->getExecuteAfter() and ($this->getExecuteAfter() > now())) {
            Log::warning('Not yet executing task ":task" as it should not be executed until after ":date"', [
                ':task' => $this->getLogId(),
                ':date' => $this->getExecuteAfter()
            ]);
        }

        return $this;
    }


    /**
     * Apply the given source
     *
     * @param bool $clear_source
     * @param array|ValidatorInterface|null $source
     * @return $this
     * @throws \Exception
     */
    public function apply(bool $clear_source = true, array|ValidatorInterface|null &$source = null): static
    {
        parent::apply($clear_source, $source);

        $this->generateCode();

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
            ->addDefinition(Definition::new($this, 'notifications_roles_id')
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
                ->setMaxLength(512))
            ->addDefinition(Definition::new($this, 'pid_file')
                ->setReadonly(true)
                ->setLabel('PID file')
                ->setInputType(InputType::text)
                ->setSize(6)
                ->setMaxLength(512))
            ->addDefinition(DefinitionFactory::getComments($this)
                ->setHelpText(tr('A description for this task')));
    }
}
