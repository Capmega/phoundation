<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Core\Hooks\Hook;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryKey;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryName;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryResults;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryRole;
use Phoundation\Data\DataEntry\Traits\TraitDataEntrySpent;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryStart;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryStop;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryValues;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Exception\TaskAlreadyExecutedException;
use Phoundation\Os\Processes\Exception\TasksException;
use Phoundation\Os\Processes\Interfaces\TaskInterface;
use Phoundation\Os\Processes\Traits\TraitDataEntryTask;
use Phoundation\Os\Processes\Traits\TraitDataEntryWorkers;
use Phoundation\Servers\Traits\TraitDataEntryServer;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;

/**
 * Class Task
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
class Task extends DataEntry implements TaskInterface
{
    use TraitDataEntryDescription;
    use TraitDataEntryKey;
    use TraitDataEntryName;
    use TraitDataEntryResults;
    use TraitDataEntryRole;
    use TraitDataEntryServer;
    use TraitDataEntrySpent;
    use TraitDataEntryStart;
    use TraitDataEntryStop;
    use TraitDataEntryTask;
    use TraitDataEntryValues;
    use TraitDataEntryWorkers;

    /**
     * Cached parent Task object
     *
     * @var TaskInterface|null $parent
     */
    protected TaskInterface|null $parent;


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
    public static function getUniqueColumn(): ?string
    {
        return 'code';
    }


    /**
     * Sets the parents_id for this object
     *
     * @param int|null $parents_id
     *
     * @return static
     */
    public function setParentsId(?int $parents_id): static
    {
        return $this->setValue('parents_id', $parents_id);
    }


    /**
     * Sets the datetime after which this task should be executed
     *
     * @param DateTimeInterface|string|null $execute_after
     *
     * @return static
     */
    public function setExecuteAfter(DateTimeInterface|string|null $execute_after): static
    {
        return $this->setValue('execute_after', $execute_after ? new DateTime($execute_after, 'system') : null);
    }


    /**
     * Returns the number of time in seconds spent on this task
     *
     * @param DateTimeInterface|string|null $execute_after
     *
     * @return float
     */
    public function getTimeSpent(DateTimeInterface|string|null $execute_after): float
    {
        if (!$this->getStart()) {
            throw new TasksException(tr('Cannot calculate time spent on task, it has not yet started'));
        }
        if (!$this->getStop()) {
            throw new TasksException(tr('Cannot calculate time spent on task, it has not yet finished'));
        }

        return $this->getStop()
                    ->diff($this->getStart())
                    ->getTotalMilliSeconds() * 1000;
    }


    /**
     * Returns the datetime after which this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getStart(): ?DateTimeInterface
    {
        return $this->getValueTypesafe('datetime', 'start');
    }


    /**
     * Returns the datetime after which this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getStop(): ?DateTimeInterface
    {
        return $this->getValueTypesafe('datetime', 'stop');
    }


    /**
     * Returns the send_to_id for where this task should be executed
     *
     * @return int|null
     */
    public function getSendToId(): ?int
    {
        return $this->getValueTypesafe('int', 'send_to_id');
    }


    /**
     * Sets the send_to_id for where this task should be executed
     *
     * @param int|null $send_to_id
     *
     * @return static
     */
    public function setSendToId(int|null $send_to_id): static
    {
        return $this->setValue('send_to_id', get_null($send_to_id));
    }


    /**
     * Sets the ionice for where this task should be executed
     *
     * @param int|null $ionice
     *
     * @return static
     */
    public function setIonice(int|null $ionice): static
    {
        return $this->setValue('ionice', get_null($ionice));
    }


    /**
     * Returns the background for where this task should be executed
     *
     * @return bool
     */
    public function getBackground(): bool
    {
        return $this->getValueTypesafe('bool', 'background');
    }


    /**
     * Sets the background for where this task should be executed
     *
     * @param bool|null $background
     *
     * @return static
     */
    public function setBackground(int|bool|null $background): static
    {
        return $this->setValue('background', (bool) $background);
    }


    /**
     * Returns the clear_logs for where this task should be executed
     *
     * @return bool
     */
    public function getClearLogs(): bool
    {
        return $this->getValueTypesafe('bool', 'clear_logs');
    }


    /**
     * Sets the clear_logs for where this task should be executed
     *
     * @param bool|null $clear_logs
     *
     * @return static
     */
    public function setClearLogs(int|bool|null $clear_logs): static
    {
        return $this->setValue('clear_logs', (bool) $clear_logs);
    }


    /**
     * Returns if this task should escape quotes in the arguments
     *
     * @return bool
     */
    public function getEscapeQuotes(): bool
    {
        return $this->getValueTypesafe('bool', 'escape_quotes');
    }


    /**
     * Sets if this task should escape quotes in the arguments
     *
     * @param bool|null $escape_quotes
     *
     * @return static
     */
    public function setEscapeQuotes(int|bool|null $escape_quotes): static
    {
        return $this->setValue('escape_quotes', (bool) $escape_quotes);
    }


    /**
     * Returns the pid_file for this task
     *
     * @return string|null
     */
    public function getPidFile(): ?string
    {
        return $this->getValueTypesafe('string', 'pid_file');
    }


    /**
     * Returns where the ouput of this command should be piped to
     *
     * @return string|null
     */
    public function getPipe(): ?string
    {
        return $this->getValueTypesafe('string', 'pipe');
    }


    /**
     * Sets where the ouput of this command should be piped to
     *
     * @param string|null $pipe
     *
     * @return static
     */
    public function setPipe(?string $pipe): static
    {
        return $this->setValue('pipe', $pipe);
    }


    /**
     * Sets access restrictions for this task
     *
     * @param string|null $restrictions
     *
     * @return static
     */
    public function setRestrictions(?string $restrictions): static
    {
        return $this->setValue('restrictions', $restrictions);
    }


    /**
     * Returns packages required for this task
     *
     * @return string|null
     */
    public function getPackages(): ?string
    {
        return $this->getValueTypesafe('string', 'packages');
    }


    /**
     * Sets packages required for this task
     *
     * @param string|null $packages
     *
     * @return static
     */
    public function setPackages(?string $packages): static
    {
        return $this->setValue('packages', $packages);
    }


    /**
     * Returns pre_exec for this task
     *
     * @return string|null
     */
    public function getPreExec(): ?string
    {
        return $this->getValueTypesafe('string', 'pre_exec');
    }


    /**
     * Sets pre_exec for this task
     *
     * @param string|null $pre_exec
     *
     * @return static
     */
    public function setPreExec(?string $pre_exec): static
    {
        return $this->setValue('pre_exec', $pre_exec);
    }


    /**
     * Returns post_exec for this task
     *
     * @return string|null
     */
    public function getPostExec(): ?string
    {
        return $this->getValueTypesafe('string', 'post_exec');
    }


    /**
     * Sets post_exec for this task
     *
     * @param string|null $post_exec
     *
     * @return static
     */
    public function setPostExec(?string $post_exec): static
    {
        return $this->setValue('post_exec', $post_exec);
    }


    /**
     * Sets comments for this task
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function setComments(?string $comments): static
    {
        return $this->setValue('comments', $comments);
    }


    /**
     * Returns results for this task
     *
     * @return string
     */
    public function getResults(): string
    {
        return $this->getValueTypesafe('string', 'results');
    }


    /**
     * Sets command for this task
     *
     * @param string|null $command
     *
     * @return static
     */
    public function setCommand(?string $command): static
    {
        return $this->setValue('command', $command);
    }


    /**
     * Returns executed_command for this task
     *
     * @return string
     */
    public function getExecutedCommand(): string
    {
        return $this->getValueTypesafe('string', 'executed_command');
    }


    /**
     * Executes this task, and stores all relevant results data in the database
     *
     * @return static
     */
    public function execute(): static
    {
        // The Task should not yet have started
        if ($this->getStart()) {
            // This task has already started
            if ($this->getStop()) {
                // This task has already stopped
                throw new TaskAlreadyExecutedException(tr('Cannot execute task ":id", it has already finished execution at ":datetime"', [
                    ':id'       => $this->getId(),
                    ':datetime' => $this->getStop(),
                ]));
            }
            throw new TaskAlreadyExecutedException(tr('Cannot execute task ":id", it is currently being executed since ":datetime"', [
                ':id'       => $this->getId(),
                ':datetime' => $this->getStart(),
            ]));
        }
        // The Task should not yet have started
        if ($this->getStatus()) {
            throw new TaskAlreadyExecutedException(tr('Cannot execute task ":id", it must have status NULL to execute but has status ":status" instead', [
                ':id'     => $this->getId(),
                ':status' => $this->getStatus(),
            ]));
        }
        // Task should be executed immediately (execute_after will be NULL) or after now()
        if ($this->getExecuteAfter() and ($this->getExecuteAfter() > now())) {
            Log::warning(tr('Not yet executing task ":task" as it should not be executed until after ":date"', [
                ':task' => $this->getLogId(),
                ':date' => $this->getExecuteAfter(),
            ]));

            return $this;
        }
        // Task should have its parent task finished
        if ($this->getParentsId()) {
            if (
                !$this->getParent()
                      ->isFinished()
            ) {
                Log::warning(tr('Not yet executing task ":task" as its parent ":parent" has not finished yet', [
                    ':task'   => $this->getLogId(),
                    ':parent' => $this->getParent()
                                      ->getCode(),
                ]));

                return $this;
            }
        }

        return $this->doExecute();
    }


    /**
     * Returns the datetime after which this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getExecuteAfter(): ?DateTimeInterface
    {
        return $this->getValueTypesafe('int', 'execute_after');
    }


    /**
     * Returns the parents_id for this object
     *
     * @return int|null
     */
    public function getParentsId(): ?int
    {
        return $this->getValueTypesafe('int', 'parents_id');
    }


    /**
     * Returns true if this task has a non-NULL stop time, false if it has started, but not yet stopped, and NULL if it
     * has not yet started
     *
     * @return bool|null
     */
    public function isFinished(): ?bool
    {
        if ($this->getStop()) {
            // Task has stopped
            return true;
        }
        if ($this->getStart()) {
            // Task has started
            return false;
        }

        return null;
    }


    /**
     * Returns a parent task for this task or NULL if none is available
     *
     * @return static|null
     */
    public function getParent(): ?static
    {
        if (!isset($this->parent)) {
            $this->parent = static::getOrNull($this->getParentsId());
        }

        return $this->parent;
    }


    /**
     * Returns the code for this object
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getValueTypesafe('string', 'code');
    }


    /**
     * Does the actual execution part of executing the task
     *
     * @return $this
     */
    protected function doExecute(): static
    {
        // Execute hook
        Hook::new('tasks')
            ->execute('pre-execute', ['task' => $this]);
        // Execute the command
        $worker = ProcessWorker::new($this->getCommand(), $this->getRestrictions())
                               ->setServer($this->getServer())
                               ->setArguments($this->getArguments())
                               ->setVariables($this->getVariables())
                               ->setExecutionDirectory($this->getExecutionDirectory())
                               ->setEnvironmentVariables($this->getEnvironmentVariables())
                               ->setAcceptedExitCodes($this->getAcceptedExitCodes())
                               ->setTimeout($this->getTimeout())
                               ->setWait($this->getWait())
                               ->setNice($this->getNice())
                               ->setIoNiceClass($this->getIonice())
                               ->setIoNiceLevel($this->getIoniceLevel())
                               ->setNoCache($this->getNocache())
                               ->setSudo($this->getSudo())
                               ->setTerm($this->getTerm())
                               ->setInputRedirect($this->getInputRedirect())
                               ->setOutputRedirect($this->getOutputRedirect())
                               ->setMinimumWorkers($this->getMinimumWorkers())
                               ->setMaximumWorkers($this->getMaximumWorkers());
        // Update task in database
        $this->setStart(now())
             ->setStatus('executing')
             ->setExecutedCommand($worker->getFullCommandLine())
             ->save();
        // Execute the task
        try {
            $results = $worker->executeReturnString();
            Log::success(tr('Task ":task" finished execution in ":time"', [
                ':task' => $this->getCode(),
                ':time' => $worker->getExecutionTimeHumanReadable(),
            ]));
            // Update task in database
            $this->setStop(now())
                 ->setSpent($worker->getExecutionTime())
                 ->setStatus('executed')
                 ->setPid($worker->getPid())
                 ->setLogFile($worker->getLogFile())
                 ->setExitCode($worker->getExitCode())
                 ->setResults($results)
                 ->save();
            // Notify the specified role?
            if ($this->getRolesId()) {
                // Notify the specified role!
                Notification::new()
                            ->setUrl('/tasks/task+' . $this->getId() . '.html')
                            ->setMode(EnumDisplayMode::info)
                            ->setRoles($this->getRolesId())
                            ->setTitle(tr('A task has been completed'))
                            ->setMessage(tr('Task ":task" has been completed successfully'))
                            ->setDetails([
                                'tasks_id'  => $this->getId(),
                                'code'      => $this->getCode(),
                                'command'   => $this->getCommand(),
                                'arguments' => $this->getArguments(),
                            ])
                            ->send();

            }
            // Execute hook
            Hook::new('tasks')
                ->execute('post-execute', [
                    'task'   => $this,
                    'worker' => $worker,
                ]);

        } catch (ProcessFailedException $e) {
            Log::warning(tr('Task ":task" failed execution with ":e"', [
                ':task' => $this->getCode(),
                ':e'    => $e->getMessage(),
            ]));
            Log::warning($e);
            // Update task in database
            $this->setStop(now())
                 ->setSpent($worker->getExecutionTime())
                 ->setStatus('failed')
                 ->setPid($worker->getPid())
                 ->setLogFile($worker->getLogFile())
                 ->setExitCode($worker->getExitCode())
                 ->setResults(Json::encode($e->getDataKey('output')))
                 ->save();
            // Notify the specified role?
            if ($this->getRolesId()) {
                // Notify the specified role!
                Notification::new()
                            ->setUrl('/tasks/task+' . $this->getId() . '.html')
                            ->setMode(EnumDisplayMode::info)
                            ->setRoles($this->getRolesId())
                            ->setTitle(tr('A task has failed'))
                            ->setMessage(tr('Task ":task" failed to complete successfully'))
                            ->setDetails([
                                'tasks_id'  => $this->getId(),
                                'code'      => $this->getCode(),
                                'command'   => $this->getCommand(),
                                'arguments' => $this->getArguments(),
                                'exception' => $e,
                            ])
                            ->send();

            }
            // Execute hook
            Hook::new('tasks')
                ->execute('execution-failed', [
                    'task'   => $this,
                    'worker' => $worker,
                ]);
        }

        return $this;
    }


    /**
     * Sets where the output should be redirected from
     *
     * @param string|null $output_redirect
     *
     * @return static
     */
    public function setOutputRedirect(?string $output_redirect): static
    {
        return $this->setValue('output_redirect', $output_redirect);
    }


    /**
     * Sets where the input should be redirected from
     *
     * @param string|null $input_redirect
     *
     * @return static
     */
    public function setInputRedirect(?string $input_redirect): static
    {
        return $this->setValue('input_redirect', $input_redirect);
    }


    /**
     * Sets if this task should use term
     *
     * @param string|null $term
     *
     * @return static
     */
    public function setTerm(?string $term): static
    {
        return $this->setValue('term', $term);
    }


    /**
     * Sets if this task should use sudo
     *
     * @param string|null $sudo
     *
     * @return static
     */
    public function setSudo(?string $sudo): static
    {
        return $this->setValue('sudo', $sudo);
    }


    /**
     * Sets the nocache for where this task should be executed
     *
     * @param int|null $nocache
     *
     * @return static
     */
    public function setNocache(int|null $nocache): static
    {
        return $this->setValue('nocache', get_null($nocache));
    }


    /**
     * Sets the ionice_level for where this task should be executed
     *
     * @param int|null $ionice_level
     *
     * @return static
     */
    public function setIoniceLevel(int|null $ionice_level): static
    {
        return $this->setValue('ionice_level', get_null($ionice_level));
    }


    /**
     * Sets the nice for where this task should be executed
     *
     * @param int|null $nice
     *
     * @return static
     */
    public function setNice(int|null $nice): static
    {
        return $this->setValue('nice', get_null($nice));
    }


    /**
     * Sets the wait for where this task should be executed
     *
     * @param int|null $wait
     *
     * @return static
     */
    public function setWait(int|null $wait): static
    {
        return $this->setValue('wait', get_null($wait));
    }


    /**
     * Sets the timeout for where this task should be executed
     *
     * @param int|null $timeout
     *
     * @return static
     */
    public function setTimeout(int|null $timeout): static
    {
        return $this->setValue('timeout', get_null($timeout));
    }


    /**
     * Sets accepted_exit_codes for this task
     *
     * @param array|null $accepted_exit_codes
     *
     * @return static
     */
    public function setAcceptedExitCodes(array|null $accepted_exit_codes): static
    {
        return $this->setValue('accepted_exit_codes', $accepted_exit_codes);
    }


    /**
     * Sets environment_variables for this task
     *
     * @param array|null $environment_variables
     *
     * @return static
     */
    public function setEnvironmentVariables(array|null $environment_variables): static
    {
        return $this->setValue('environment_variables', $environment_variables);
    }


    /**
     * Sets execution_directory for this task
     *
     * @param string|null $execution_directory
     *
     * @return static
     */
    public function setExecutionDirectory(?string $execution_directory): static
    {
        return $this->setValue('execution_directory', $execution_directory);
    }


    /**
     * Sets variables for this task
     *
     * @param array|null $variables
     *
     * @return static
     */
    public function setVariables(array|null $variables): static
    {
        // Ensure that variables are valid
        if ($variables) {
            foreach ($variables as $key => $value) {
                if (!preg_match('/^:[A-Z0-9]+[A-Z0-9-]*[A-Z0-9]+$/', $key)) {
                    throw new OutOfBoundsException(tr('Specified variable key ":key" is invalid, it should match regex "/^:[A-Z0-9]+[A-Z0-9-]*[A-Z0-9]+$/", so a : symbol, and then at least 2 characters that can be only uppercase letters, or numbers, or dash, and cannot begin or end with a dash', [
                        ':key' => $key,
                    ]));
                }
            }
        }

        return $this->setValue('variables', $variables);
    }


    /**
     * Sets arguments for this task
     *
     * @param array|null $arguments
     *
     * @return static
     */
    public function setArguments(?array $arguments): static
    {
        return $this->setValue('arguments', $arguments);
    }


    /**
     * Returns command for this task
     *
     * @return string|null
     */
    public function getCommand(): ?string
    {
        return $this->getValueTypesafe('string', 'command');
    }


    /**
     * Returns access restrictions for this task
     *
     * @return string|null
     */
    public function getRestrictions(): ?string
    {
        return $this->getValueTypesafe('string', 'restrictions');
    }


    /**
     * Returns arguments for this task
     *
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return $this->getValueTypesafe('array', 'arguments');
    }


    /**
     * Returns variables for this task
     *
     * @return array|null
     */
    public function getVariables(): ?array
    {
        return $this->getValueTypesafe('array', 'variables');
    }


    /**
     * Returns execution_directory for this task
     *
     * @return string|null
     */
    public function getExecutionDirectory(): ?string
    {
        return $this->getValueTypesafe('string', 'execution_directory');
    }


    /**
     * Returns environment_variables for this task
     *
     * @return array|null
     */
    public function getEnvironmentVariables(): ?array
    {
        return $this->getValueTypesafe('array', 'environment_variables');
    }


    /**
     * Returns accepted_exit_codes for this task
     *
     * @return array|null
     */
    public function getAcceptedExitCodes(): ?array
    {
        return $this->getValueTypesafe('array', 'accepted_exit_codes');
    }


    /**
     * Returns the timeout for where this task should be executed
     *
     * @return int|null
     */
    public function getTimeout(): ?int
    {
        return $this->getValueTypesafe('int', 'timeout');
    }


    /**
     * Returns the wait for where this task should be executed
     *
     * @return int|null
     */
    public function getWait(): ?int
    {
        return $this->getValueTypesafe('int', 'wait');
    }


    /**
     * Returns the nice for where this task should be executed
     *
     * @return int|null
     */
    public function getNice(): ?int
    {
        return $this->getValueTypesafe('int', 'nice');
    }


    /**
     * Returns the ionice for where this task should be executed
     *
     * @return int|null
     */
    public function getIonice(): ?int
    {
        return $this->getValueTypesafe('int', 'ionice');
    }


    /**
     * Returns the ionice_level for where this task should be executed
     *
     * @return int|null
     */
    public function getIoniceLevel(): ?int
    {
        return $this->getValueTypesafe('int', 'ionice_level');
    }


    /**
     * Returns the nocache for where this task should be executed
     *
     * @return int|null
     */
    public function getNocache(): ?int
    {
        return $this->getValueTypesafe('int', 'nocache');
    }


    /**
     * Returns the sudo string for this task
     *
     * @return string|null
     */
    public function getSudo(): ?string
    {
        return $this->getValueTypesafe('string', 'sudo');
    }


    /**
     * Returns the term string for this task
     *
     * @return string|null
     */
    public function getTerm(): ?string
    {
        return $this->getValueTypesafe('string', 'term');
    }


    /**
     * Returns where the input should be redirected from
     *
     * @return string|null
     */
    public function getInputRedirect(): ?string
    {
        return $this->getValueTypesafe('string', 'input_redirect');
    }


    /**
     * Returns where the output should be redirected from
     *
     * @return string|null
     */
    public function getOutputRedirect(): ?string
    {
        return $this->getValueTypesafe('string', 'output_redirect');
    }


    /**
     * Save this task to disk
     *
     * @param bool        $force
     * @param string|null $comments
     *
     * @return $this
     */
    public function save(bool $force = false, ?string $comments = null): static
    {
        if ($this->saveBecauseModified($force)) {
            if (!$this->isNew()) {
                // This is not a new entry, save as normal
                return parent::save();
            }

            // Validate data, generate a new code, and write it to database
            return $this->ensureValidation()
                        ->generateCode()
                        ->write($comments);
        }

        return $this;
    }


    /**
     * Generates the UUID code for this object
     *
     * @return static
     * @throws \Exception
     */
    protected function generateCode(): static
    {
        return $this->setCode(Strings::getUuid());
    }


    /**
     * Sets the UUID code for this object
     *
     * @param string|null $code
     *
     * @return static
     */
    protected function setCode(?string $code): static
    {
        return $this->setValue('code', $code);
    }


    /**
     * Sets executed_command for this task
     *
     * @param string|null $executed_command
     *
     * @return static
     */
    protected function setExecutedCommand(?string $executed_command): static
    {
        return $this->setValue('executed_command', $executed_command);
    }


    /**
     * Sets the datetime after which this task should be executed
     *
     * @param DateTimeInterface|string|null $start
     *
     * @return static
     */
    public function setStart(DateTimeInterface|string|null $start): static
    {
        return $this->setValue('start', $start ? new DateTime($start, 'system') : null);
    }


    /**
     * Sets results for this task
     *
     * @param string|null $results
     *
     * @return static
     */
    protected function setResults(?string $results): static
    {
        return $this->setValue('results', $results);
    }


    /**
     * Sets the exit_code for where this task should be executed
     *
     * @param int|null $exit_code
     *
     * @return static
     */
    protected function setExitCode(int|null $exit_code): static
    {
        return $this->setValue('exit_code', get_null($exit_code));
    }


    /**
     * Sets the log_file for this task
     *
     * @param string|null $log_file
     *
     * @return static
     */
    protected function setLogFile(?string $log_file): static
    {
        return $this->setValue('log_file', $log_file);
    }


    /**
     * Sets the pid for where this task should be executed
     *
     * @param int|null $pid
     *
     * @return static
     */
    protected function setPid(int|null $pid): static
    {
        return $this->setValue('pid', get_null($pid));
    }


    /**
     * Sets the datetime after which this task should be executed
     *
     * @param DateTimeInterface|string|null $stop
     *
     * @return static
     */
    public function setStop(DateTimeInterface|string|null $stop): static
    {
        return $this->setValue('stop', $stop ? new DateTime($stop, 'system') : null);
    }


    /**
     * Returns the pid for where this task should be executed
     *
     * @return int|null
     */
    public function getPid(): ?int
    {
        return $this->getValueTypesafe('int', 'pid');
    }


    /**
     * Returns the log_file for this task
     *
     * @return string|null
     */
    public function getLogFile(): ?string
    {
        return $this->getValueTypesafe('string', 'log_file');
    }


    /**
     * Returns the exit_code for where this task should be executed
     *
     * @return int|null
     */
    public function getExitCode(): ?int
    {
        return $this->getValueTypesafe('int', 'exit_code');
    }


    /**
     * Sets the pid_file for this task
     *
     * @param string|null $pid_file
     *
     * @return static
     */
    protected function setPidFile(?string $pid_file): static
    {
        return $this->setValue('pid_file', $pid_file);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::getCode($this)
                                           ->setReadonly(true)
                                           ->setOptional(true)
                                           ->setLabel(tr('Code'))
                                           ->setSize(4)
                                           ->setMaxlength(36)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isCode();
                                           }))
                    ->add(DefinitionFactory::getName($this))
                    ->add(DefinitionFactory::getSeoName($this))
                    ->add(Definition::new($this, 'parents_id')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::select)
                                    ->setLabel('Parent task')
                                    ->setDataSource('SELECT `id` FROM `os_tasks` WHERE (`status` IS NULL OR `status` NOT IN ("deleted"))')
                                    ->setSize(4)
                                    ->setMaxlength(17)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDbId();
                                    }))
                    ->add(Definition::new($this, 'execute_after')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::datetime_local)
                                    ->setLabel('Execute after')
                                    ->setCliColumn('[--execute-after DATETIME]')
                                    ->setSize(4)
                                    ->setMaxlength(17)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDateTime();
                                    }))
                    ->add(Definition::new($this, 'start')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::datetime_local)
                                    ->setLabel('Execution started on')
                                    ->setSize(4)
                                    ->setMaxlength(17)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDateTime();
                                    }))
                    ->add(Definition::new($this, 'stop')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::datetime_local)
                                    ->setLabel('Execution finished on')
                                    ->setSize(4)
                                    ->setMaxlength(17)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDateTime();
                                    }))
                    ->add(Definition::new($this, 'spent')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::float)
                                    ->setLabel('Time spent on task execution')
                                    ->setSize(4)
                                    ->setMin(0)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isFloat();
                                    }))
                    ->add(Definition::new($this, 'send_to')
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setMaxlength(128)
                                    ->setLabel('Send to user')
                                    ->setCliColumn('[--send-to EMAIL]')
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isEmail();
                                    }))
                    ->add(Definition::new($this, 'send_to_id')
                                    ->setOptional(true)
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::select)
                                    ->setDataSource('SELECT `id`, CONCAT(`email`, " <", `first_names`, " ", `last_names`, ">") FROM `accounts_users` WHERE `status` IS NULL')
                                    ->setSize(4)
                                    ->setMaxlength(17)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDbId();
                                    }))
                    ->add(Definition::new($this, 'server')
                                    ->setOptional(true)
                                    ->setVirtual(true)
                                    ->setMaxlength(255)
                                    ->setLabel('Execute on server')
                                    ->setCliColumn('[-s,--server HOSTNAME]')
                                    ->setSize(4)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->orColumn('servers_id')
                                                  ->isName()
                                                  ->setColumnFromQuery('servers_id', 'SELECT `id` FROM `servers` WHERE `hostname` = :hostname AND `status` IS NULL', [':hostname' => '$server']);
                                    }))
                    ->add(Definition::new($this, 'servers_id')
                                    ->setOptional(true)
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::select)
                                    ->setDataSource('SELECT `id` FROM `servers` WHERE `status` IS NULL')
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->orColumn('server')
                                                  ->isDbId()
                                                  ->isQueryResult('SELECT `id` FROM `servers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$servers_id']);
                                    }))
                    ->add(Definition::new($this, 'roles_id')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::select)
                                    ->setLabel('Notify roles')
                                    ->setCliColumn('[-r,--roles "ROLE,ROLE,..."]')
                                    ->setDataSource('SELECT `id` FROM `accounts_roles` WHERE `status` IS NULL')
                                    ->setSize(4)
                                    ->setMaxlength(17)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDbId();
                                    }))
                    ->add(Definition::new($this, 'execution_directory')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::text)
                                    ->setLabel('Execution path')
                                    ->setCliColumn('[-d,--execution-directory PATH]')
                                    ->setSize(4)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDirectory('/', Restrictions::writable('/'));
                                    }))
                    ->add(Definition::new($this, 'command')
                                    ->setInputType(EnumInputType::text)
                                    ->setLabel('Command')
                                    ->setCliColumn('[-c,--command COMMAND]')
                                    ->setSize(4))
                    ->add(Definition::new($this, 'executed_command')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::text)
                                    ->setLabel('Command')
                                    ->setSize(4))
                    ->add(Definition::new($this, 'arguments')
                                    ->setOptional(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setInputType(EnumInputType::array_json)
                                    ->setLabel('Arguments')
                                    ->setCliColumn('[-a,--arguments ARGUMENTS]')
                                    ->setSize(4))
                    ->add(Definition::new($this, 'variables')
                                    ->setOptional(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setInputType(EnumInputType::array_json)
                                    ->setLabel('Argument variables')
                                    ->setCliColumn('[-v,--variables VARIABLES]')
                                    ->setSize(4))
                    ->add(Definition::new($this, 'environment_variables')
                                    ->setOptional(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setInputType(EnumInputType::array_json)
                                    ->setLabel('Environment variables')
                                    ->setCliColumn('[-e,--environment-variables VARIABLES]')
                                    ->setSize(4))
                    ->add(Definition::new($this, 'clear_logs')
                                    ->setOptional(true, false)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setLabel('Clear logs')
                                    ->setSize(4)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isBoolean();
                                    }))
                    ->add(Definition::new($this, 'escape_quotes')
                                    ->setOptional(true, false)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setLabel('Escape quotes')
                                    ->setSize(4)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isBoolean();
                                    }))
                    ->add(Definition::new($this, 'nocache')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::select)
                                    ->setLabel('No cache mode')
                                    ->setDataSource([])
                                    ->setSize(4)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {}))
                    ->add(Definition::new($this, 'ionice')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::select)
                                    ->setLabel('IO nice')
                                    ->setCliColumn('[-i,--ionice CLASSNUMBER]')
                                    ->setDataSource([
                                        0 => 'none',
                                        1 => 'realtime',
                                        2 => 'best_effort',
                                        3 => 'idle',
                                    ])
                                    ->setSize(4))
                    ->add(Definition::new($this, 'ionice_level')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setLabel('IO nice level')
                                    ->setCliColumn('[-l,--ionice-level LEVEL]')
                                    ->setMin(0)
                                    ->setMax(7)
                                    ->setSize(4))
                    ->add(Definition::new($this, 'nice')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setLabel('Nice level')
                                    ->setCliColumn('[-n,--nice LEVEL]')
                                    ->setOptional(true, 0)
                                    ->setMin(-20)
                                    ->setMax(20)
                                    ->setSize(4))
                    ->add(Definition::new($this, 'timeout')
                                    ->setOptional(true, 30)
                                    ->setInputType(EnumInputType::number)
                                    ->setLabel('Time limit')
                                    ->setCliColumn('[-t,--timeout SECONDS]')
                                    ->setOptional(true, 0)
                                    ->setMin(0)
                                    ->setSize(4))
                    ->add(Definition::new($this, 'wait')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setLabel('Start wait')
                                    ->setCliColumn('[-w,--wait SECONDS]')
                                    ->setOptional(true, 0)
                                    ->setMin(0)
                                    ->setSize(4))
                    ->add(Definition::new($this, 'minimum_workers')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setLabel('Minimum workers')
                                    ->setCliColumn('[--minimum-workers AMOUNT]')
                                    ->setOptional(true, 0)
                                    ->setMin(0)
                                    ->setMax(10_000)
                                    ->setSize(4))
                    ->add(Definition::new($this, 'maximum_workers')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setLabel('Maximum workers')
                                    ->setCliColumn('[--maximum-workers AMOUNT]')
                                    ->setOptional(true, 0)
                                    ->setMin(0)
                                    ->setMax(10_000)
                                    ->setSize(4))
                    ->add(Definition::new($this, 'sudo')
                                    ->setOptional(true, false)
                                    ->setLabel('Sudo required / command')
                                    ->setCliColumn('[-s,--sudo "string"]')
                                    ->setSize(6)
                                    ->setMaxlength(32))
                    ->add(Definition::new($this, 'term')
                                    ->setOptional(true)
                                    ->setLabel('Terminal command')
                                    ->setCliColumn('[-t,--term "command"]')
                                    ->setSize(6)
                                    ->setMaxlength(32))
                    ->add(Definition::new($this, 'pipe')
                                    ->setOptional(true)
                                    ->setLabel('Pipe to')
                                    ->setSize(6)
                                    ->setMaxlength(510))
                    ->add(Definition::new($this, 'input_redirect')
                                    ->setOptional(true)
                                    ->setLabel('Input redirect')
                                    ->setSize(6)
                                    ->setMaxlength(64))
                    ->add(Definition::new($this, 'output_redirect')
                                    ->setOptional(true)
                                    ->setLabel('Output redirect')
                                    ->setSize(6)
                                    ->setMaxlength(510))
                    ->add(Definition::new($this, 'restrictions')
                                    ->setOptional(true)
                                    ->setLabel('Restrictions')
                                    ->setSize(6)
                                    ->setMaxlength(510))
                    ->add(Definition::new($this, 'packages')
                                    ->setOptional(true)
                                    ->setLabel('Packages')
                                    ->setSize(6)
                                    ->setMaxlength(510))
                    ->add(Definition::new($this, 'pre_exec')
                                    ->setOptional(true)
                                    ->setLabel('Pre execute')
                                    ->setSize(6)
                                    ->setMaxlength(510))
                    ->add(Definition::new($this, 'post_exec')
                                    ->setOptional(true)
                                    ->setLabel('Post execute')
                                    ->setSize(6)
                                    ->setMaxlength(510))
                    ->add(Definition::new($this, 'accepted_exit_codes')
                                    ->setOptional(true, [0])
                                    ->setLabel('Accepted Exit Codes')
                                    ->setElement(EnumElement::textarea)
                                    ->setInputType(EnumInputType::array_json)
                                    ->setSize(6)
                                    ->setMaxlength(64))
                    ->add(Definition::new($this, 'results')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setLabel('Results')
                                    ->setElement(EnumElement::textarea)
                                    ->setSize(12)
                                    ->setMaxlength(16_777_215)
                                    ->setReadonly(true))
                    ->add(Definition::new($this, 'pid')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setLabel('Process ID')
                                    ->setDisabled(true)
                                    ->setSize(4)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDbId();
                                    }))
                    ->add(Definition::new($this, 'exit_code')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setLabel('Exit code')
                                    ->setInputType(EnumInputType::number)
                                    ->setSize(2)
                                    ->setMin(0)
                                    ->setMax(255))
                    ->add(Definition::new($this, 'log_file')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setLabel('Log file')
                                    ->setInputType(EnumInputType::text)
                                    ->setSize(6)
                                    ->setMaxLength(512))
                    ->add(Definition::new($this, 'pid_file')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setLabel('PID file')
                                    ->setInputType(EnumInputType::text)
                                    ->setSize(6)
                                    ->setMaxLength(512))
                    ->add(DefinitionFactory::getComments($this)
                                           ->setHelpText(tr('A description for this task')));
    }


    /**
     * Returns comments for this task
     *
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->getValueTypesafe('string', 'comments');
    }
}
