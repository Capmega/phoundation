<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Core\Hooks\Hook;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryName;
use Phoundation\Data\DataEntry\Traits\DataEntryResults;
use Phoundation\Data\DataEntry\Traits\DataEntryRole;
use Phoundation\Data\DataEntry\Traits\DataEntrySpent;
use Phoundation\Data\DataEntry\Traits\DataEntryStart;
use Phoundation\Data\DataEntry\Traits\DataEntryStop;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Exception\TaskAlreadyExecutedException;
use Phoundation\Os\Processes\Exception\TasksException;
use Phoundation\Os\Processes\Interfaces\TaskInterface;
use Phoundation\Os\Processes\Traits\DataEntryTask;
use Phoundation\Os\Processes\Traits\DataEntryWorkers;
use Phoundation\Servers\Traits\DataEntryServer;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\InputElement;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;


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
class Task extends DataEntry implements TaskInterface
{
    use DataEntryRole;
    use DataEntryStart;
    use DataEntryStop;
    use DataEntrySpent;
    use DataEntryServer;
    use DataEntryTask;
    use DataEntryName;
    use DataEntryWorkers;
    use DataEntryResults;
    use DataEntryDescription;


    /**
     * Cached parent Task object
     *
     * @var TaskInterface|null $parent
     */
    protected TaskInterface|null $parent;


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
     * Returns the number of time in seconds spent on this task
     *
     * @param DateTimeInterface|string|null $execute_after
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

        return $this->getStop()->diff($this->getStart())->getTotalMilliSeconds() * 1000;
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
     * @return string|null
     */
    public function getLogFile(): ?string
    {
        return $this->getSourceFieldValue('string', 'log_file');
    }


    /**
     * Sets the log_file for this task
     *
     * @param string|null $log_file
     * @return static
     */
    protected function setLogFile(?string $log_file): static
    {
        return $this->setSourceValue('log_file', $log_file);
    }


    /**
     * Returns the pid_file for this task
     *
     * @return string|null
     */
    public function getPidFile(): ?string
    {
        return $this->getSourceFieldValue('string', 'pid_file');
    }


    /**
     * Sets the pid_file for this task
     *
     * @param string|null $pid_file
     * @return static
     */
    protected function setPidFile(?string $pid_file): static
    {
        return $this->setSourceValue('pid_file', $pid_file);
    }


    /**
     * Returns the sudo string for this task
     *
     * @return string|null
     */
    public function getSudo(): ?string
    {
        return $this->getSourceFieldValue('string', 'sudo');
    }


    /**
     * Sets if this task should use sudo
     *
     * @param string|null $sudo
     * @return static
     */
    public function setSudo(?string $sudo): static
    {
        return $this->setSourceValue('sudo', $sudo);
    }


    /**
     * Returns the term string for this task
     *
     * @return string|null
     */
    public function getTerm(): ?string
    {
        return $this->getSourceFieldValue('string', 'term');
    }


    /**
     * Sets if this task should use term
     *
     * @param string|null $term
     * @return static
     */
    public function setTerm(?string $term): static
    {
        return $this->setSourceValue('term', $term);
    }


    /**
     * Returns where the ouput of this command should be piped to
     *
     * @return string|null
     */
    public function getPipe(): ?string
    {
        return $this->getSourceFieldValue('string', 'pipe');
    }


    /**
     * Sets where the ouput of this command should be piped to
     *
     * @param string|null $pipe
     * @return static
     */
    public function setPipe(?string $pipe): static
    {
        return $this->setSourceValue('pipe', $pipe);
    }


    /**
     * Returns where the input should be redirected from
     *
     * @return string|null
     */
    public function getInputRedirect(): ?string
    {
        return $this->getSourceFieldValue('string', 'input_redirect');
    }


    /**
     * Sets where the input should be redirected from
     *
     * @param string|null $input_redirect
     * @return static
     */
    public function setInputRedirect(?string $input_redirect): static
    {
        return $this->setSourceValue('input_redirect', $input_redirect);
    }


    /**
     * Returns where the output should be redirected from
     *
     * @return string|null
     */
    public function getOutputRedirect(): ?string
    {
        return $this->getSourceFieldValue('string', 'output_redirect');
    }


    /**
     * Sets where the output should be redirected from
     *
     * @param string|null $output_redirect
     * @return static
     */
    public function setOutputRedirect(?string $output_redirect): static
    {
        return $this->setSourceValue('output_redirect', $output_redirect);
    }


    /**
     * Returns access restrictions for this task
     *
     * @return string|null
     */
    public function getRestrictions(): ?string
    {
        return $this->getSourceFieldValue('string', 'restrictions');
    }


    /**
     * Sets access restrictions for this task
     *
     * @param string|null $restrictions
     * @return static
     */
    public function setRestrictions(?string $restrictions): static
    {
        return $this->setSourceValue('restrictions', $restrictions);
    }


    /**
     * Returns packages required for this task
     *
     * @return string|null
     */
    public function getPackages(): ?string
    {
        return $this->getSourceFieldValue('string', 'packages');
    }


    /**
     * Sets packages required for this task
     *
     * @param string|null $packages
     * @return static
     */
    public function setPackages(?string $packages): static
    {
        return $this->setSourceValue('packages', $packages);
    }


    /**
     * Returns pre_exec for this task
     *
     * @return string|null
     */
    public function getPreExec(): ?string
    {
        return $this->getSourceFieldValue('string', 'pre_exec');
    }


    /**
     * Sets pre_exec for this task
     *
     * @param string|null $pre_exec
     * @return static
     */
    public function setPreExec(?string $pre_exec): static
    {
        return $this->setSourceValue('pre_exec', $pre_exec);
    }


    /**
     * Returns post_exec for this task
     *
     * @return string|null
     */
    public function getPostExec(): ?string
    {
        return $this->getSourceFieldValue('string', 'post_exec');
    }


    /**
     * Sets post_exec for this task
     *
     * @param string|null $post_exec
     * @return static
     */
    public function setPostExec(?string $post_exec): static
    {
        return $this->setSourceValue('post_exec', $post_exec);
    }


    /**
     * Returns comments for this task
     *
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->getSourceFieldValue('string', 'comments');
    }


    /**
     * Sets comments for this task
     *
     * @param string|null $comments
     * @return static
     */
    public function setComments(?string $comments): static
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
    protected function setResults(?string $results): static
    {
        return $this->setSourceValue('results', $results);
    }


    /**
     * Returns execution_directory for this task
     *
     * @return string|null
     */
    public function getExecutionDirectory(): ?string
    {
        return $this->getSourceFieldValue('string', 'execution_directory');
    }


    /**
     * Sets execution_directory for this task
     *
     * @param string|null $execution_directory
     * @return static
     */
    public function setExecutionDirectory(?string $execution_directory): static
    {
        return $this->setSourceValue('execution_directory', $execution_directory);
    }


    /**
     * Returns command for this task
     *
     * @return string|null
     */
    public function getCommand(): ?string
    {
        return $this->getSourceFieldValue('string', 'command');
    }


    /**
     * Sets command for this task
     *
     * @param string|null $command
     * @return static
     */
    public function setCommand(?string $command): static
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
    protected function setExecutedCommand(?string $executed_command): static
    {
        return $this->setSourceValue('executed_command', $executed_command);
    }


    /**
     * Returns arguments for this task
     *
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return $this->getSourceFieldValue('array', 'arguments');
    }


    /**
     * Sets arguments for this task
     *
     * @param array|null $arguments
     * @return static
     */
    public function setArguments(?array $arguments): static
    {
        return $this->setSourceValue('arguments', $arguments);
    }


    /**
     * Returns variables for this task
     *
     * @return array|null
     */
    public function getVariables(): ?array
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
     * @return array|null
     */
    public function getEnvironmentVariables(): ?array
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
     * @return array|null
     */
    public function getAcceptedExitCodes(): ?array
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
     * Generates the UUID code for this object
     *
     * @return static
     * @throws \Exception
     */
    protected function generateCode(): static
    {
        return $this->setCode(Strings::generateUuid());
    }


    /**
     * Sets the UUID code for this object
     *
     * @param string|null $code
     * @return static
     */
    protected function setCode(?string $code): static
    {
        return $this->setSourceValue('code', $code);
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
                    ':datetime' => $this->getStop()
                ]));
            }

            throw new TaskAlreadyExecutedException(tr('Cannot execute task ":id", it is currently being executed since ":datetime"', [
                ':id'       => $this->getId(),
                ':datetime' => $this->getStart()
            ]));
        }

        // The Task should not yet have started
        if ($this->getStatus()) {
            throw new TaskAlreadyExecutedException(tr('Cannot execute task ":id", it must have status NULL to execute but has status ":status" instead', [
                ':id'     => $this->getId(),
                ':status' => $this->getStatus()
            ]));
        }

        // Task should be executed immediately (execute_after will be NULL) or after now()
        if ($this->getExecuteAfter() and ($this->getExecuteAfter() > now())) {
            Log::warning(tr('Not yet executing task ":task" as it should not be executed until after ":date"', [
                ':task' => $this->getLogId(),
                ':date' => $this->getExecuteAfter()
            ]));

            return $this;
        }

        // Task should have its parent task finished
        if ($this->getParentsId()) {
            if (!$this->getParent()->isFinished()) {
                Log::warning(tr('Not yet executing task ":task" as its parent ":parent" has not finished yet', [
                    ':task'   => $this->getLogId(),
                    ':parent' => $this->getParent()->getCode()
                ]));

                return $this;
            }
        }

        return $this->doExecute();
    }


    /**
     * Does the actual execution part of executing the task
     *
     * @return $this
     */
    protected function doExecute(): static
    {
        // Execute hook
        Hook::new('tasks')->execute('pre-execute' , ['task' => $this]);

        // Execute the command
        $worker = Workers::new($this->getCommand(), $this->getRestrictions())
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
                ':time' => $worker->getExecutionTimeHumanReadable()
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
                    ->setUrl('/tasks/task-' . $this->getId() . '.html')
                    ->setMode(DisplayMode::info)
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
            Hook::new('tasks')->execute('post-execute', [
                'task'   => $this,
                'worker' => $worker
            ]);

        } catch (ProcessFailedException $e) {
            Log::warning(tr('Task ":task" failed execution with ":e"', [
                ':task' => $this->getCode(),
                ':e'    => $e->getMessage()
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
                    ->setUrl('/tasks/task-' . $this->getId() . '.html')
                    ->setMode(DisplayMode::info)
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
            Hook::new('tasks')->execute('execution-failed', [
                'task'   => $this,
                'worker' => $worker
            ]);
        }

        return $this;
    }


    /**
     * Save this task to disk
     *
     * @param bool $force
     * @param string|null $comments
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
            return $this
                ->ensureValidation()
                ->generateCode()
                ->write($comments);
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
            ->addDefinition(DefinitionFactory::getCode($this)
                ->setReadonly(true)
                ->setOptional(true)
                ->setLabel(tr('Code'))
                ->setSize(4)
                ->setMaxlength(36)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isCode();
                }))
            ->addDefinition(DefinitionFactory::getName($this))
            ->addDefinition(DefinitionFactory::getSeoName($this))
            ->addDefinition(Definition::new($this, 'parents_id')
                ->setOptional(true)
                ->setInputType(InputType::select)
                ->setLabel('Parent task')
                ->setSource('SELECT `id` FROM `os_tasks` WHERE (`status` IS NULL OR `status` NOT IN ("deleted"))')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'execute_after')
                ->setOptional(true)
                ->setInputType(InputType::datetime_local)
                ->setLabel('Execute after')
                ->setCliField('[--execute-after DATETIME]')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new($this, 'start')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::datetime_local)
                ->setLabel('Execution started on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new($this, 'stop')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::datetime_local)
                ->setLabel('Execution finished on')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDateTime();
                }))
            ->addDefinition(Definition::new($this, 'spent')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::float)
                ->setLabel('Time spent on task execution')
                ->setSize(4)
                ->setMin(0)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isFloat();
                }))
            ->addDefinition(Definition::new($this, 'send_to')
                ->setOptional(true)
                ->setVirtual(true)
                ->setMaxlength(128)
                ->setLabel('Send to user')
                ->setCliField('[--send-to EMAIL]')
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isEmail();
                }))
            ->addDefinition(Definition::new($this, 'send_to_id')
                ->setOptional(true)
                ->setVisible(false)
                ->setInputType(InputType::select)
                ->setSource('SELECT `id`, CONCAT(`email`, " <", `first_names`, " ", `last_names`, ">") FROM `accounts_users` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'server')
                ->setOptional(true)
                ->setVirtual(true)
                ->setMaxlength(255)
                ->setLabel('Execute on server')
                ->setCliField('[-s,--server HOSTNAME]')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->orField('servers_id')->isName()->setColumnFromQuery('servers_id', 'SELECT `id` FROM `servers` WHERE `hostname` = :hostname AND `status` IS NULL', [':hostname' => '$server']);
                }))
            ->addDefinition(Definition::new($this, 'servers_id')
                ->setOptional(true)
                ->setVisible(false)
                ->setInputType(InputType::select)
                ->setSource('SELECT `id` FROM `servers` WHERE `status` IS NULL')
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->orField('server')->isDbId()->isQueryResult('SELECT `id` FROM `servers` WHERE `id` = :id AND `status` IS NULL', [':id' => '$servers_id']);
                }))
            ->addDefinition(Definition::new($this, 'roles_id')
                ->setOptional(true)
                ->setInputType(InputType::select)
                ->setLabel('Notify roles')
                ->setCliField('[-r,--roles "ROLE,ROLE,..."]')
                ->setSource('SELECT `id` FROM `accounts_roles` WHERE `status` IS NULL')
                ->setSize(4)
                ->setMaxlength(17)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'execution_directory')
                ->setOptional(true)
                ->setInputType(InputType::text)
                ->setLabel('Execution path')
                ->setCliField('[-d,--execution-directory PATH]')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDirectory('/', Restrictions::writable('/'));
                }))
            ->addDefinition(Definition::new($this, 'command')
                ->setInputType(InputType::text)
                ->setLabel('Command')
                ->setCliField('[-c,--command COMMAND]')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'executed_command')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::text)
                ->setLabel('Command')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'arguments')
                ->setOptional(true)
                ->setInputType(InputTypeExtended::array_json)
                ->setLabel('Arguments')
                ->setCliField('[-a,--arguments ARGUMENTS]')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'variables')
                ->setOptional(true)
                ->setInputType(InputTypeExtended::array_json)
                ->setLabel('Argument variables')
                ->setCliField('[-v,--variables VARIABLES]')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'environment_variables')
                ->setOptional(true)
                ->setInputType(InputTypeExtended::array_json)
                ->setLabel('Environment variables')
                ->setCliField('[-e,--environment-variables VARIABLES]')
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'clear_logs')
                ->setOptional(true, false)
                ->setInputType(InputType::checkbox)
                ->setLabel('Clear logs')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'escape_quotes')
                ->setOptional(true, false)
                ->setInputType(InputType::checkbox)
                ->setLabel('Escape quotes')
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isBoolean();
                }))
            ->addDefinition(Definition::new($this, 'nocache')
                ->setOptional(true)
                ->setInputType(InputType::select)
                ->setLabel('No cache mode')
                ->setSource([

                ])
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                }))
            ->addDefinition(Definition::new($this, 'ionice')
                ->setOptional(true)
                ->setInputType(InputType::select)
                ->setLabel('IO nice')
                ->setCliField('[-i,--ionice CLASSNUMBER]')
                ->setSource([
                    0 => 'none',
                    1 => 'realtime',
                    2 => 'best_effort',
                    3 => 'idle',
                ])
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'ionice_level')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setLabel('IO nice level')
                ->setCliField('[-l,--ionice-level LEVEL]')
                ->setMin(0)
                ->setMax(7)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'nice')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setLabel('Nice level')
                ->setCliField('[-n,--nice LEVEL]')
                ->setOptional(true, 0)
                ->setMin(-20)
                ->setMax(20)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'timeout')
                ->setOptional(true, 30)
                ->setInputType(InputType::number)
                ->setLabel('Time limit')
                ->setCliField('[-t,--timeout SECONDS]')
                ->setOptional(true, 0)
                ->setMin(0)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'wait')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setLabel('Start wait')
                ->setCliField('[-w,--wait SECONDS]')
                ->setOptional(true, 0)
                ->setMin(0)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'minimum_workers')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setLabel('Minimum workers')
                ->setCliField('[--minimum-workers AMOUNT]')
                ->setOptional(true, 0)
                ->setMin(0)
                ->setMax(10_000)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'maximum_workers')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setLabel('Maximum workers')
                ->setCliField('[--maximum-workers AMOUNT]')
                ->setOptional(true, 0)
                ->setMin(0)
                ->setMax(10_000)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'sudo')
                ->setOptional(true, false)
                ->setLabel('Sudo required / command')
                ->setCliField('[-s,--sudo "string"]')
                ->setSize(6)
                ->setMaxlength(32))
            ->addDefinition(Definition::new($this, 'term')
                ->setOptional(true)
                ->setLabel('Terminal command')
                ->setCliField('[-t,--term "command"]')
                ->setSize(6)
                ->setMaxlength(32))
            ->addDefinition(Definition::new($this, 'pipe')
                ->setOptional(true)
                ->setLabel('Pipe to')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'input_redirect')
                ->setOptional(true)
                ->setLabel('Input redirect')
                ->setSize(6)
                ->setMaxlength(64))
            ->addDefinition(Definition::new($this, 'output_redirect')
                ->setOptional(true)
                ->setLabel('Output redirect')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'restrictions')
                ->setOptional(true)
                ->setLabel('Restrictions')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'packages')
                ->setOptional(true)
                ->setLabel('Packages')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'pre_exec')
                ->setOptional(true)
                ->setLabel('Pre execute')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'post_exec')
                ->setOptional(true)
                ->setLabel('Post execute')
                ->setSize(6)
                ->setMaxlength(510))
            ->addDefinition(Definition::new($this, 'accepted_exit_codes')
                ->setOptional(true, [0])
                ->setLabel('Accepted Exit Codes')
                ->setInputType(InputTypeExtended::array_json)
                ->setSize(6)
                ->setMaxlength(64))
            ->addDefinition(Definition::new($this, 'results')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel('Results')
                ->setElement(InputElement::textarea)
                ->setSize(12)
                ->setMaxlength(16_777_215)
                ->setReadonly(true))
            ->addDefinition(Definition::new($this, 'pid')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setLabel('Process ID')
                ->setDisabled(true)
                ->setSize(4)
                ->addValidationFunction(function(ValidatorInterface $validator) {
                    $validator->isDbId();
                }))
            ->addDefinition(Definition::new($this, 'exit_code')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel('Exit code')
                ->setInputType(InputType::number)
                ->setSize(2)
                ->setMin(0)
                ->setMax(255))
            ->addDefinition(Definition::new($this, 'log_file')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel('Log file')
                ->setInputType(InputType::text)
                ->setSize(6)
                ->setMaxLength(512))
            ->addDefinition(Definition::new($this, 'pid_file')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel('PID file')
                ->setInputType(InputType::text)
                ->setSize(6)
                ->setMaxLength(512))
            ->addDefinition(DefinitionFactory::getComments($this)
                ->setHelpText(tr('A description for this task')));
    }
}
