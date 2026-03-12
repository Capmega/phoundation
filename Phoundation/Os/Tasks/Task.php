<?php

/**
 * Class Task
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Os\Tasks;

use Phoundation\Core\Hooks\Hook;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryKey;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryName;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryResults;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryRole;
use Phoundation\Data\DataEntries\Traits\TraitDataEntrySessionsCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntrySpent;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStart;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryStop;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryValues;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Traits\TraitDataEntryRestrictions;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Notifications\Notification;
use Phoundation\Os\Processes\Commands\Pho;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Exception\TaskAlreadyExecutedException;
use Phoundation\Os\Processes\Exception\TasksException;
use Phoundation\Os\Processes\Interfaces\TaskInterface;
use Phoundation\Os\Processes\Traits\TraitDataEntryTask;
use Phoundation\Os\Processes\Traits\TraitDataEntryWorkers;
use Phoundation\Os\Workers\Worker;
use Phoundation\Servers\Traits\TraitDataEntryServer;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Http\Url;
use Stringable;


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
    use TraitDataEntryRestrictions;
    use TraitDataEntryCode;
    use TraitDataEntrySessionsCode;


    /**
     * Cached parent Task object
     *
     * @var TaskInterface|null $parent
     */
    protected TaskInterface|null $parent = null;

    /**
     * Tracks if this task should automatically start the tasks executioner if it currently is not running
     *
     * @var bool $auto_start_executioner
     */
    protected bool $auto_start_executioner = true;


    /**
     * Task class constructor
     *
     * @param IdentifierInterface|false|array|int|string|null $identifier
     * @param EnumLoadParameters|null                         $on_null_identifier
     * @param EnumLoadParameters|null                         $on_not_exists
     */
    public function __construct(IdentifierInterface|false|array|int|string|null $identifier = false, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null) {
        parent::__construct($identifier, $on_null_identifier, $on_not_exists);
        $this->setRestrictionsObject(PhoRestrictions::newRoot());
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'os_tasks';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
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
        return $this->set($parents_id, 'parents_id');
    }


    /**
     * Returns if this task should automatically start the tasks executioner if it currently is not running
     *
     * @return bool
     */
    public function getAutoStartExecutioner(): bool
    {
        return $this->auto_start_executioner;
    }


    /**
     * Sets if this task should automatically start the tasks executioner if it currently is not running
     *
     * @param bool $auto_start_executioner
     *
     * @return $this
     */
    public function setAutoStartExecutioner(bool $auto_start_executioner): static
    {
        $this->auto_start_executioner = $auto_start_executioner;
        return $this;
    }


    /**
     * Sets the datetime after which this task should be executed
     *
     * @param PhoDateTimeInterface|string|null $execute_after
     *
     * @return static
     */
    public function setExecuteAfter(PhoDateTimeInterface|string|null $execute_after): static
    {
        return $this->set($execute_after ? new PhoDateTime($execute_after, 'system') : null, 'execute_after');
    }


    /**
     * Returns the amount of time in seconds spent on this task
     *
     * @return float
     */
    public function getTimeSpent(): float
    {
        if (!$this->getStart()) {
            throw new TasksException(tr('Cannot calculate time spent on task, it has not yet started'));
        }

        if (!$this->getStop()) {
            throw new TasksException(tr('Cannot calculate time spent on task, it has not yet finished'));
        }

        return $this->getStop()
                    ->diff($this->getStart()->getDateTimeObject())
                    ->getTotalMilliSeconds() * 1_000;
    }


    /**
     * Returns the datetime after which this task should be executed
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStart(): ?PhoDateTimeInterface
    {
        return PhoDateTime::newOrNull($this->getTypesafe('string', 'start'));
    }


    /**
     * Returns the datetime after which this task should be executed
     *
     * @return PhoDateTimeInterface|null
     */
    public function getStop(): ?PhoDateTimeInterface
    {
        return PhoDateTime::newOrNull($this->getTypesafe('string', 'stop'));
    }


    /**
     * Returns the send_to_id for where this task should be executed
     *
     * @return int|null
     */
    public function getSendToId(): ?int
    {
        return $this->getTypesafe('int', 'send_to_id');
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
        return $this->set(get_null($send_to_id), 'send_to_id');
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
        return $this->set(get_null($ionice), 'ionice');
    }


    /**
     * Returns the asymmetric key used to encrypt the process output results, or null if the process results were not encrypted
     *
     * @return string|null
     */
    public function getResultsEncryptionKey(): ?string
    {
        return $this->getTypesafe('bool', 'results_encryption_key');
    }


    /**
     * Sets the asymmetric key used to encrypt the process output results, or null if the results should not be encrypted
     *
     * @param string|null $results_encryption_key
     *
     * @return static
     */
    public function setResultsEncryptionKey(string|null $results_encryption_key): static
    {
        return $this->set($results_encryption_key, 'results_encryption_key');
    }


    /**
     * Returns the background for where this task should be executed
     *
     * @return bool
     */
    public function getBackground(): bool
    {
        return $this->getTypesafe('bool', 'background');
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
        return $this->set((bool) $background, 'background');
    }


    /**
     * Returns the clear_logs for where this task should be executed
     *
     * @return bool
     */
    public function getClearLogs(): bool
    {
        return $this->getTypesafe('bool', 'clear_logs');
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
        return $this->set((bool) $clear_logs, 'clear_logs');
    }


    /**
     * Returns if this task should escape quotes in the arguments
     *
     * @return bool
     */
    public function getEscapeQuotes(): bool
    {
        return $this->getTypesafe('bool', 'escape_quotes');
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
        return $this->set((bool) $escape_quotes, 'escape_quotes');
    }


    /**
     * Returns the pid_file for this task
     *
     * @return string|null
     */
    public function getPidFile(): ?string
    {
        return $this->getTypesafe('string', 'pid_file');
    }


    /**
     * Returns where the ouput of this command should be piped to
     *
     * @return string|null
     */
    public function getPipe(): ?string
    {
        return $this->getTypesafe('string', 'pipe');
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
        return $this->set($pipe, 'pipe');
    }


    /**
     * Returns packages required for this task
     *
     * @return string|null
     */
    public function getPackages(): ?string
    {
        return $this->getTypesafe('string', 'packages');
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
        return $this->set($packages, 'packages');
    }


    /**
     * Returns the pre-execution hook for this task
     *
     * @return string|null
     */
    public function getPreExecutionHook(): ?string
    {
        return $this->getTypesafe('string', 'pre_execution_hook');
    }


    /**
     * Sets the pre-execution hook for this task
     *
     * @param string|null $hook            The hook file to execute before this task executes
     * @param bool        $require [false] If true, the hook file must exist
     *
     * @return static
     */
    public function setPreExecutionHook(?string $hook, bool $require = false): static
    {
        Hook::checkExists($hook, null, $require);

        return $this->set($hook, 'pre_execution_hook');
    }


    /**
     * Returns the post-execution hook for this task
     *
     * @return string|null
     */
    public function getPostExecutionHook(): ?string
    {
        return $this->getTypesafe('string', 'post_execution_hook');
    }


    /**
     * Sets post-execution hook for this task
     *
     * @param string|null $hook            The hook file to execute after this task executes
     * @param bool        $require [false] If true, the hook file must exist
     *
     * @return static
     */
    public function setPostExecutionHook(?string $hook, bool $require = false): static
    {
        return $this->set($hook, 'post_execution_hook');
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
        return $this->set($comments, 'comments');
    }


    /**
     * Returns results for this task
     *
     * @return string|null
     */
    public function getResults(): ?string
    {
        return $this->getTypesafe('string', 'results');
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
        return $this->set($command, 'command');
    }


    /**
     * Returns executed_command for this task
     *
     * @return string|null
     */
    public function getExecutedCommand(): ?string
    {
        return $this->getTypesafe('string', 'executed_command');
    }


    /**
     * Starts the task executioner job as a background task if it is not yet running
     *
     * @return static
     */
    public function startExecutioner(): static
    {
        Pho::new()
           ->setPhoCommands('tasks execute')
           ->appendArgument('-a')
           ->executeBackground();

        return $this;
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
                    ':id'       => $this->getLogId(),
                    ':datetime' => $this->getStop(),
                ]));
            }

            throw new TaskAlreadyExecutedException(tr('Cannot execute task ":id", it is currently being executed since ":datetime"', [
                ':id'       => $this->getLogId(),
                ':datetime' => $this->getStart(),
            ]));
        }

        // The Task should not yet have started
        if ($this->getStatus()) {
            throw new TaskAlreadyExecutedException(tr('Cannot execute task ":id", it must have status NULL to execute but has status ":status" instead', [
                ':id'     => $this->getLogId(),
                ':status' => $this->getStatus(),
            ]));
        }

        // Task should be executed immediately (execute_after will be NULL) or after now()
        if ($this->getExecuteAfter() and ($this->getExecuteAfter() > now())) {
            Log::warning(ts('Not yet executing task ":task" as it should not be executed until after ":date"', [
                ':task' => $this->getLogId(),
                ':date' => $this->getExecuteAfter(),
            ]));

            return $this;
        }

        // Task should have its parent task finished
        if ($this->getParentsId()) {
            if (!$this->getParent()->isFinished()) {
                Log::warning(ts('Not yet executing task ":task" as its parent ":parent" has not finished yet', [
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
     * @return PhoDateTimeInterface|null
     */
    public function getExecuteAfter(): ?PhoDateTimeInterface
    {
        return $this->getTypesafe('int', 'execute_after');
    }


    /**
     * Returns the parents_id for this object
     *
     * @return int|null
     */
    public function getParentsId(): ?int
    {
        return $this->getTypesafe('int', 'parents_id');
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
        if (empty($this->parent)) {
            $this->parent = static::new($this->getParentsId())->loadNull();
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
        return $this->getTypesafe('string', 'code');
    }


    /**
     * Does the actual execution part of executing the task
     *
     * @return static
     */
    protected function doExecute(): static
    {
        // Execute pre-execution hook
        Hook::new()->execute($this->getPreExecutionHook(), ['task' => $this]);

        // Execute the command
        $worker = Worker::new($this->getCommand(), $this->getExecutionDirectory())
                        ->setServerObject($this->getServer())
                        ->setArguments($this->getArguments())
                        ->setVariables($this->getVariables())
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

            Log::success(ts('Task ":task" finished execution in ":time"', [
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
                            ->setUrl(Url::new('/tasks/task+' . $this->getId() . '.html')->makeWww())
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

        } catch (ProcessFailedException $e) {
            Log::warning(ts('Task ":task" failed execution with ":e"', [
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
                            ->setUrl(Url::new('/tasks/task+' . $this->getId() . '.html')->makeWww())
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
        }

        // Execute post execution hook
        Hook::new()->execute($this->getPostExecutionHook(), ['task' => $this]);
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
        return $this->set($output_redirect, 'output_redirect');
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
        return $this->set($input_redirect, 'input_redirect');
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
        return $this->set($term, 'term');
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
        return $this->set($sudo, 'sudo');
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
        return $this->set(get_null($nocache), 'nocache');
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
        return $this->set(get_null($ionice_level), 'ionice_level');
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
        return $this->set(get_null($nice), 'nice');
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
        return $this->set(get_null($wait), 'wait');
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
        return $this->set(get_null($timeout), 'timeout');
    }


    /**
     * Returns accepted_exit_codes for this task
     *
     * @return array|null
     */
    public function getAcceptedExitCodes(): ?array
    {
        return Json::decode($this->getTypesafe('string', 'accepted_exit_codes'));
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
        return $this->set(Json::encode($accepted_exit_codes), 'accepted_exit_codes');
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
        return $this->set($environment_variables, 'environment_variables');
    }


    /**
     * Sets execution_directory for this task
     *
     * @param PhoDirectoryInterface|null $execution_directory
     *
     * @return static
     */
    public function setExecutionDirectory(?PhoDirectoryInterface $execution_directory): static
    {
        return $this->set($execution_directory, 'execution_directory');
    }


    /**
     * Returns full command including sudo and the arguments for this task
     *
     * @return string|null
     */
    public function getFullCommand(): ?string
    {
        return ($this->getSudo() ? 'sudo' : '') . ' ' .
                $this->getCommand()             . ' ' .
                trim(Strings::removeCharacters($this->getArguments(), '"[]'));
    }


    /**
     * Returns command for this task
     *
     * @return string|null
     */
    public function getCommand(): ?string
    {
        return $this->getTypesafe('string', 'command');
    }


    /**
     * Returns variables for this task
     *
     * @return array|null
     */
    public function getVariables(): ?array
    {
        return Json::decode($this->getTypesafe('array', 'variables'));
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
                    throw new OutOfBoundsException(tr('Specified variable key ":key" is invalid, it should match pattern "/^:[A-Z0-9]+[A-Z0-9-]*[A-Z0-9]+$/", so a : symbol, and then at least 2 characters that can be only uppercase letters, or numbers, or dash, and cannot begin or end with a dash', [
                        ':key' => $key,
                    ]));
                }
            }
        }

        return $this->set(get_null(Json::encode($variables)), 'variables');
    }


    /**
     * Returns arguments for this task
     *
     * @return array|null
     */
    public function getArguments(): ?array
    {
        return Json::decode($this->getTypesafe('string', 'arguments'));
    }


    /**
     * Sets arguments for this task
     *
     * @param array|string|null $arguments
     *
     * @return static
     */
    public function setArguments(array|string|null $arguments): static
    {
        return $this->set(Json::encode($arguments), 'arguments');
    }


    /**
     * Adds multiple arguments to the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|int|float|null $arguments
     * @param bool                                   $escape_arguments
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function appendArguments(Stringable|array|string|int|float|null $arguments, bool $escape_arguments = true, bool $escape_quotes = true): static
    {
        if ($arguments) {
            if (is_array($arguments)) {
                foreach (Arrays::force($arguments, null) as $argument) {
                    if (!$argument) {
                        if ($argument !== 0) {
                            // Ignore empty arguments
                            continue;
                        }
                    }

                    // Add multiple arguments
                    $this->appendArguments($argument, $escape_arguments, $escape_quotes);
                }

            } else {
                // Add a single argument
                $this->appendArgument($arguments, $escape_arguments, $escape_quotes);
            }
        }

        return $this;
    }


    /**
     * Adds multiple arguments to the beginning of the (existing list of) arguments for the command that will be executed
     *
     * @param Stringable|array|string|int|float|null $arguments
     * @param bool                                   $escape_arguments
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function prependArguments(Stringable|array|string|int|float|null $arguments, bool $escape_arguments = true, bool $escape_quotes = true): static
    {
        if ($arguments) {
            if (is_array($arguments)) {
                // Since we are prepending, reverse the array!
                $arguments = array_reverse($arguments);

                foreach ($arguments as $argument) {
                    if (!$argument) {
                        if ($argument !== 0) {
                            // Ignore empty arguments
                            continue;
                        }
                    }

                    // Add multiple arguments
                    $this->prependArguments($argument, $escape_arguments, $escape_quotes);
                }

            } else {
                // Add a single argument
                $this->prependArgument($arguments, $escape_arguments, $escape_quotes);
            }
        }

        return $this;
    }


    /**
     * Adds an argument to the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|float|int|null $argument
     * @param bool                                   $escape_argument
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function appendArgument(Stringable|array|string|float|int|null $argument, bool $escape_argument = true, bool $escape_quotes = true): static
    {
        if ($argument !== null) {
            if (is_array($argument)) {
                return $this->appendArguments($argument, $escape_argument, $escape_quotes);
            }

            // TODO This is wildly inefficient as each update requires a JSON encode and decode. Improve this somehow
            $arguments   = $this->getArguments();
            $arguments[] = [
                'escape_argument' => $escape_argument,
                'escape_quotes'   => $escape_quotes,
                'argument'        => (string) $argument,
            ];

            $this->setArguments($arguments);
        }

        return $this;
    }


    /**
     * Adds an argument to the beginning of the existing list of arguments for the command that will be executed
     *
     * @param Stringable|array|string|float|int|null $argument
     * @param bool                                   $escape_argument
     * @param bool                                   $escape_quotes
     *
     * @return static This process so that multiple methods can be chained
     */
    public function prependArgument(Stringable|array|string|float|int|null $argument, bool $escape_argument = true, bool $escape_quotes = true): static
    {
        if ($argument !== null) {
            if (is_array($argument)) {
                return $this->prependArguments($argument, $escape_argument, $escape_quotes);
            }

            // TODO This is wildly inefficient as each update requires a JSON encode and decode. Improve this somehow
            $arguments = $this->getArguments();

            array_unshift($arguments,  [
                'escape_argument' => $escape_argument,
                'escape_quotes'   => $escape_quotes,
                'argument'        => (string) $argument,
            ]);

            $this->setArguments($arguments);
        }

        return $this;
    }


    /**
     * Returns execution_directory for this task
     *
     * @return PhoDirectoryInterface|null
     */
    public function getExecutionDirectory(): ?PhoDirectoryInterface
    {
        return PhoDirectory::newOrNull($this->getTypesafe('string', 'execution_directory'), $this->getRestrictionsObject());
    }


    /**
     * Returns environment_variables for this task
     *
     * @return array|null
     */
    public function getEnvironmentVariables(): ?array
    {
        return $this->getTypesafe('array', 'environment_variables');
    }


    /**
     * Returns the timeout for where this task should be executed
     *
     * @return int|null
     */
    public function getTimeout(): ?int
    {
        return $this->getTypesafe('int', 'timeout');
    }


    /**
     * Returns the wait for where this task should be executed
     *
     * @return int|null
     */
    public function getWait(): ?int
    {
        return $this->getTypesafe('int', 'wait');
    }


    /**
     * Returns the nice for where this task should be executed
     *
     * @return int|null
     */
    public function getNice(): ?int
    {
        return $this->getTypesafe('int', 'nice');
    }


    /**
     * Returns the ionice for where this task should be executed
     *
     * @return int|null
     */
    public function getIonice(): ?int
    {
        return $this->getTypesafe('int', 'ionice');
    }


    /**
     * Returns the ionice_level for where this task should be executed
     *
     * @return int|null
     */
    public function getIoniceLevel(): ?int
    {
        return $this->getTypesafe('int', 'ionice_level');
    }


    /**
     * Returns the nocache for where this task should be executed
     *
     * @return int|null
     */
    public function getNocache(): ?int
    {
        return $this->getTypesafe('int', 'nocache');
    }


    /**
     * Returns the sudo string for this task
     *
     * @return string|null
     */
    public function getSudo(): ?string
    {
        return $this->getTypesafe('string', 'sudo');
    }


    /**
     * Returns the term string for this task
     *
     * @return string|null
     */
    public function getTerm(): ?string
    {
        return $this->getTypesafe('string', 'term');
    }


    /**
     * Returns where the input should be redirected from
     *
     * @return string|null
     */
    public function getInputRedirect(): ?string
    {
        return $this->getTypesafe('string', 'input_redirect');
    }


    /**
     * Returns where the output should be redirected from
     *
     * @return string|null
     */
    public function getOutputRedirect(): ?string
    {
        return $this->getTypesafe('string', 'output_redirect');
    }


    /**
     * Save this task to disk
     *
     * @param bool        $force           [false] If true, will save even if the Task object has not been modified
     * @param bool        $skip_validation [false] If true, will skip validation, even when it should be necessary
     * @param string|null $comments        [null]  Meta comment on this Task, stating why it was saved
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        if ($this->saveBecauseModified($force)) {
            if (!$this->isNew()) {
                // This is not a new entry, save as normal
                parent::save();

            } else {
                // Validate data, generate a new code, and write it to the database
                $this->validate()
                     ->generateCode()
                     ->write($force, $comments);
            }
        }

        if ($this->getAutoStartExecutioner()) {
            $this->startExecutioner();
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
        return $this->set($code, 'code');
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
        return $this->set($executed_command, 'executed_command');
    }


    /**
     * Sets the datetime after which this task should be executed
     *
     * @param PhoDateTimeInterface|string|null $start
     *
     * @return static
     */
    public function setStart(PhoDateTimeInterface|string|null $start): static
    {
        return $this->set(PhoDateTime::newOrNull($start, 'system')?->format('system_datetime'), 'stop');
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
        return $this->set($results, 'results');
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
        return $this->set(get_null($exit_code), 'exit_code');
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
        return $this->set($log_file, 'log_file');
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
        return $this->set(get_null($pid), 'pid');
    }


    /**
     * Sets the datetime after which this task should be executed
     *
     * @param PhoDateTimeInterface|string|null $stop
     *
     * @return static
     */
    public function setStop(PhoDateTimeInterface|string|null $stop): static
    {
        return $this->set(PhoDateTime::newOrNull($stop, 'system')?->format('system_datetime'), 'stop');
    }


    /**
     * Returns the pid for where this task should be executed
     *
     * @return int|null
     */
    public function getPid(): ?int
    {
        return $this->getTypesafe('int', 'pid');
    }


    /**
     * Returns the log_file for this task
     *
     * @return string|null
     */
    public function getLogFile(): ?string
    {
        return $this->getTypesafe('string', 'log_file');
    }


    /**
     * Returns the exit_code for where this task should be executed
     *
     * @return int|null
     */
    public function getExitCode(): ?int
    {
        return $this->getTypesafe('int', 'exit_code');
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
        return $this->set($pid_file, 'pid_file');
    }


    /**
     * Returns comments for this task
     *
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->getTypesafe('string', 'comments');
    }


    /**
     * Returns the workers for this object
     *
     * @return int
     */
    public static function getDefaultMaximumWorkers(): int
    {
        return config()->getInteger('tasks.workers.maximum', 25);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $_definitions): static
    {
        $_definitions->add(DefinitionFactory::newCode()
                                            ->setReadonly(true)
                                            ->setOptional(true)
                                            ->setLabel(tr('Code'))
                                            ->setSize(4)
                                            ->setMaxLength(36)
                                            ->addValidationFunction(function (ValidatorInterface $_validator) {
                                                $_validator->isCode(max_characters: 36, min_characters: 36);
                                            }))

                     ->add(DefinitionFactory::newCode('sessions_code')
                                            ->setReadonly(true)
                                            ->setOptional(true)
                                            ->setLabel(tr('Session Code'))
                                            ->setSize(4)
                                            ->setMaxLength(64)
                                            ->addValidationFunction(function (ValidatorInterface $_validator) {
                                                $_validator->isCode();
                                            }))

                     ->add(DefinitionFactory::newName())

                     ->add(DefinitionFactory::newSeoName())

                     ->add(Definition::new('parents_id')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::select)
                                     ->setLabel('Parent task')
                                     ->setSource('SELECT `id` FROM `os_tasks` WHERE (`status` IS NULL OR `status` NOT LIKE "deleted%")')
                                     ->setSize(4)
                                     ->setMaxLength(17)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isDbId();
                                     }))

                     ->add(Definition::new('execute_after')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::datetime_local)
                                     ->setLabel('Execute after')
                                     ->setCliColumn('[--execute-after DATETIME]')
                                     ->setSize(4)
                                     ->setMaxLength(17)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isDateTime();
                                     }))

                     ->add(Definition::new('start')
                                     ->setOptional(true)
                                     ->setReadonly(true)
                                     ->setInputType(EnumInputType::datetime_local)
                                     ->setLabel('Execution started on')
                                     ->setSize(4)
                                     ->setMaxLength(17)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isDateTime();
                                     }))

                     ->add(Definition::new('stop')
                                     ->setOptional(true)
                                     ->setReadonly(true)
                                     ->setInputType(EnumInputType::datetime_local)
                                     ->setLabel('Execution finished on')
                                     ->setSize(4)
                                     ->setMaxLength(17)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isDateTime();
                                     }))

                     ->add(Definition::new('spent')
                                     ->setOptional(true)
                                     ->setReadonly(true)
                                     ->setInputType(EnumInputType::float)
                                     ->setLabel('Time spent on task execution')
                                     ->setSize(4)
                                     ->setMin(0)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isFloat();
                                     }))

                     ->add(Definition::new('send_to')
                                     ->setOptional(true)
                                     ->setVirtual(true)
                                     ->setMaxLength(128)
                                     ->setLabel('Send to user')
                                     ->setCliColumn('[--send-to EMAIL]')
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isEmail();
                                     }))

                     ->add(Definition::new('send_to_id')
                                     ->setOptional(true)
                                     ->setRender(false)
                                     ->setInputType(EnumInputType::select)
                                     ->setSource('SELECT `id`, CONCAT(`email`, " <", `first_names`, " ", `last_names`, ">") FROM `accounts_users` WHERE `status` IS NULL')
                                     ->setSize(4)
                                     ->setMaxLength(17)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isDbId();
                                     }))

                     ->add(Definition::new('server')
                                     ->setOptional(true)
                                     ->setVirtual(true)
                                     ->setMaxLength(255)
                                     ->setLabel('Execute on server')
                                     ->setCliColumn('[-s,--server HOSTNAME]')
                                     ->setSize(4)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->orColumn('servers_id')
                                                   ->isName()
                                                   ->setColumnFromQuery('servers_id', 'SELECT `id` 
                                                                                       FROM   `servers` 
                                                                                       WHERE  `hostname` = :hostname 
                                                                                       AND   (`status` IS NULL OR `status` NOT LIKE "deleted%")', [
                                                                                           ':hostname' => '$server'
                                                   ]);
                                     }))

                     ->add(Definition::new('servers_id')
                                     ->setOptional(true)
                                     ->setRender(false)
                                     ->setInputType(EnumInputType::select)
                                     ->setSource('SELECT `id` FROM `servers` WHERE `status` IS NULL')
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->orColumn('server')
                                                   ->isDbId()
                                                   ->isQueryResult('SELECT `id` 
                                                                    FROM   `servers` 
                                                                    WHERE  `id` = :id 
                                                                    AND   (`status` IS NULL OR `status` NOT LIKE "deleted%")', [
                                                                        ':id' => '$servers_id'
                                                   ]);
                                     }))

                     ->add(Definition::new('roles_id')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::select)
                                     ->setLabel('Notify roles')
                                     ->setCliColumn('[-r,--roles "ROLE,ROLE,..."]')
                                     ->setSource('SELECT `id` FROM `accounts_roles` WHERE `status` IS NULL')
                                     ->setSize(4)
                                     ->setMaxLength(17)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isDbId();
                                     }))

                     ->add(Definition::new('execution_directory')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::text)
                                     ->setLabel('Execution path')
                                     ->setCliColumn('[-d,--execution-directory PATH]')
                                     ->setSize(4)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isDirectory(PhoDirectory::newFilesystemRoot());
                                     }))

                     ->add(Definition::new('command')
                                     ->setInputType(EnumInputType::text)
                                     ->setLabel('Command')
                                     ->setCliColumn('[-c,--command COMMAND]')
                                     ->setSize(4))

                     ->add(Definition::new('executed_command')
                                     ->setOptional(true)
                                     ->setReadonly(true)
                                     ->setInputType(EnumInputType::text)
                                     ->setLabel('Command')
                                     ->setSize(4))

                     ->add(Definition::new('arguments')
                                     ->setOptional(true)
                                     ->setElement(EnumElement::textarea)
                                     ->setInputType(EnumInputType::array_json)
                                     ->setLabel('Arguments')
                                     ->setCliColumn('[-a,--arguments ARGUMENTS]')
                                     ->setSize(4)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isPrintable();
                                         $_validator->skipValidation();
                                     }))

                     ->add(Definition::new('variables')
                                     ->setOptional(true)
                                     ->setElement(EnumElement::textarea)
                                     ->setInputType(EnumInputType::array_json)
                                     ->setLabel('Argument variables')
                                     ->setCliColumn('[-v,--variables VARIABLES]')
                                     ->setSize(4)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isPrintable();
                                         $_validator->skipValidation();
                                     }))

                     ->add(Definition::new('environment_variables')
                                     ->setOptional(true)
                                     ->setElement(EnumElement::textarea)
                                     ->setInputType(EnumInputType::array_json)
                                     ->setLabel('Environment variables')
                                     ->setCliColumn('[-e,--environment-variables VARIABLES]')
                                     ->setSize(4))

                     ->add(Definition::new('clear_logs')
                                     ->setOptional(true, false)
                                     ->setInputType(EnumInputType::checkbox)
                                     ->setLabel('Clear logs')
                                     ->setSize(4)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isBoolean();
                                     }))

                     ->add(Definition::new('escape_quotes')
                                     ->setOptional(true, false)
                                     ->setInputType(EnumInputType::checkbox)
                                     ->setLabel('Escape quotes')
                                     ->setSize(4)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isBoolean();
                                     }))

                     ->add(Definition::new('nocache')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::select)
                                     ->setLabel('No cache mode')
                                     ->setSource([])
                                     ->setSize(4)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {}))

                     ->add(Definition::new('ionice')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::select)
                                     ->setLabel('IO nice')
                                     ->setCliColumn('[-i,--ionice CLASSNUMBER]')
                                     ->setSource([
                                         0 => 'none',
                                         1 => 'realtime',
                                         2 => 'best_effort',
                                         3 => 'idle',
                                     ])
                                     ->setSize(4))

                     ->add(Definition::new('ionice_level')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::number)
                                     ->setLabel('IO nice level')
                                     ->setCliColumn('[-l,--ionice-level LEVEL]')
                                     ->setMin(0)
                                     ->setMax(7)
                                     ->setSize(4))

                     ->add(Definition::new('nice')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::number)
                                     ->setLabel('Nice level')
                                     ->setCliColumn('[-n,--nice LEVEL]')
                                     ->setOptional(true, 0)
                                     ->setMin(-20)
                                     ->setMax(20)
                                     ->setSize(4))

                     ->add(Definition::new('timeout')
                                     ->setOptional(true, 30)
                                     ->setInputType(EnumInputType::number)
                                     ->setLabel('Time limit')
                                     ->setCliColumn('[-t,--timeout SECONDS]')
                                     ->setOptional(true, 0)
                                     ->setMin(0)
                                     ->setSize(4))

                     ->add(Definition::new('wait')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::number)
                                     ->setLabel('Start wait')
                                     ->setCliColumn('[-w,--wait SECONDS]')
                                     ->setOptional(true, 0)
                                     ->setMin(0)
                                     ->setSize(4))

                     ->add(Definition::new('minimum_workers')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::number)
                                     ->setLabel('Minimum workers')
                                     ->setCliColumn('[--minimum-workers AMOUNT]')
                                     ->setOptional(true, 0)
                                     ->setMin(0)
                                     ->setMax(10_000)
                                     ->setSize(4))

                     ->add(Definition::new('maximum_workers')
                                     ->setOptional(true)
                                     ->setInputType(EnumInputType::number)
                                     ->setLabel('Maximum workers')
                                     ->setCliColumn('[--maximum-workers AMOUNT]')
                                     ->setOptional(true, 0)
                                     ->setMin(0)
                                     ->setMax(10_000)
                                     ->setSize(4))

                     ->add(Definition::new('sudo')
                                     ->setOptional(true)
                                     ->setLabel('Sudo required / command')
                                     ->setCliColumn('[-s,--sudo "string"]')
                                     ->setSize(6)
                                     ->setMaxLength(32))

                     ->add(Definition::new('term')
                                     ->setOptional(true)
                                     ->setLabel('Terminal command')
                                     ->setCliColumn('[-t,--term "command"]')
                                     ->setSize(6)
                                     ->setMaxLength(32))

                     ->add(Definition::new('pipe')
                                     ->setOptional(true)
                                     ->setLabel('Pipe to')
                                     ->setSize(6)
                                     ->setMaxLength(510))

                     ->add(Definition::new('input_redirect')
                                     ->setOptional(true)
                                     ->setLabel('Input redirect')
                                     ->setSize(6)
                                     ->setMaxLength(64))

                     ->add(Definition::new('output_redirect')
                                     ->setOptional(true)
                                     ->setLabel('Output redirect')
                                     ->setSize(6)
                                     ->setMaxLength(510))

                     ->add(Definition::new('restrictions')
                                     ->setOptional(true)
                                     ->setLabel('FsRestrictions')
                                     ->setSize(6)
                                     ->setMaxLength(510))

                     ->add(Definition::new('packages')
                                     ->setOptional(true)
                                     ->setLabel('Packages')
                                     ->setSize(6)
                                     ->setMaxLength(510))

                     ->add(Definition::new('pre_exec')
                                     ->setOptional(true)
                                     ->setLabel('Pre execute')
                                     ->setSize(6)
                                     ->setMaxLength(510))

                     ->add(Definition::new('post_exec')
                                     ->setOptional(true)
                                     ->setLabel('Post execute')
                                     ->setSize(6)
                                     ->setMaxLength(510))

                     ->add(Definition::new('accepted_exit_codes')
                                     ->setOptional(true, [0])
                                     ->setLabel('Accepted Exit Codes')
                                     ->setElement(EnumElement::textarea)
                                     ->setInputType(EnumInputType::array_json)
                                     ->setSize(6)
                                     ->setMaxLength(64))

                     ->add(Definition::new('results')
                                     ->setOptional(true)
                                     ->setReadonly(true)
                                     ->setLabel('Results')
                                     ->setElement(EnumElement::textarea)
                                     ->setSize(12)
                                     ->setMaxLength(16_777_215)
                                     ->setReadonly(true))

                     ->add(Definition::new('pid')
                                     ->setOptional(true)
                                     ->setReadonly(true)
                                     ->setInputType(EnumInputType::number)
                                     ->setLabel('Process ID')
                                     ->setDisabled(true)
                                     ->setSize(4)
                                     ->addValidationFunction(function (ValidatorInterface $_validator) {
                                         $_validator->isDbId();
                                     }))

                     ->add(Definition::new('exit_code')
                                     ->setOptional(true)
                                     ->setReadonly(true)
                                     ->setLabel('Exit code')
                                     ->setInputType(EnumInputType::number)
                                     ->setSize(2)
                                     ->setMin(0)
                                     ->setMax(255))

                     ->add(Definition::new('log_file')
                                     ->setOptional(true)
                                     ->setReadonly(true)
                                     ->setLabel('Log file')
                                     ->setInputType(EnumInputType::text)
                                     ->setSize(6)
                                     ->setMaxLength(512))

                     ->add(Definition::new('pid_file')
                                     ->setOptional(true)
                                     ->setReadonly(true)
                                     ->setLabel('PID file')
                                     ->setInputType(EnumInputType::text)
                                     ->setSize(6)
                                     ->setMaxLength(512))

                     ->add(DefinitionFactory::newComments()
                                            ->setHelpText(tr('A description for this task')));

        return $this;
     }
}
