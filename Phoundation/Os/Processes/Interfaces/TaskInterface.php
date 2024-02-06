<?php

namespace Phoundation\Os\Processes\Interfaces;

use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Date\Interfaces\DateTimeInterface;


/**
 * Interface Task
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
interface TaskInterface
{
    /**
     * Returns the parents_id for this object
     *
     * @return int|null
     */
    public function getParentsId(): ?int;

    /**
     * Sets the parents_id for this object
     *
     * @param int|null $parents_id
     * @return static
     */
    public function setParentsId(?int $parents_id): static;

    /**
     * Returns the datetime after which this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getExecuteAfter(): ?DateTimeInterface;

    /**
     * Sets the datetime after which this task should be executed
     *
     * @param DateTimeInterface|string|null $execute_after
     * @return static
     */
    public function setExecuteAfter(DateTimeInterface|string|null $execute_after): static;

    /**
     * Returns the datetime after which this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getStart(): ?DateTimeInterface;

    /**
     * Sets the datetime after which this task should be executed
     *
     * @param DateTimeInterface|string|null $start
     * @return static
     */
    public function setStart(DateTimeInterface|string|null $start): static;

    /**
     * Returns the datetime after which this task should be executed
     *
     * @return DateTimeInterface|null
     */
    public function getStop(): ?DateTimeInterface;

    /**
     * Sets the datetime after which this task should be executed
     *
     * @param DateTimeInterface|string|null $stop
     * @return static
     */
    public function setStop(DateTimeInterface|string|null $stop): static;

    /**
     * Returns the send_to_id for where this task should be executed
     *
     * @return int|null
     */
    public function getSendToId(): ?int;

    /**
     * Sets the send_to_id for where this task should be executed
     *
     * @param int|null $send_to_id
     * @return static
     */
    public function setSendToId(int|null $send_to_id): static;

    /**
     * Returns the roles_id for where this task should be executed
     *
     * @return int|null
     */
    public function getRolesId(): ?int;

    /**
     * Sets the roles_id for where this task should be executed
     *
     * @param int|null $roles_id
     * @return static
     */
    public function setRolesId(int|null $roles_id): static;

    /**
     * Returns the pid for where this task should be executed
     *
     * @return int|null
     */
    public function getPid(): ?int;

    /**
     * Returns the exit_code for where this task should be executed
     *
     * @return int|null
     */
    public function getExitCode(): ?int;

    /**
     * Returns the nocache for where this task should be executed
     *
     * @return int|null
     */
    public function getNocache(): ?int;

    /**
     * Sets the nocache for where this task should be executed
     *
     * @param int|null $nocache
     * @return static
     */
    public function setNocache(int|null $nocache): static;

    /**
     * Returns the ionice for where this task should be executed
     *
     * @return int|null
     */
    public function getIonice(): ?int;

    /**
     * Sets the ionice for where this task should be executed
     *
     * @param int|null $ionice
     * @return static
     */
    public function setIonice(int|null $ionice): static;

    /**
     * Returns the ionice_level for where this task should be executed
     *
     * @return int|null
     */
    public function getIoniceLevel(): ?int;

    /**
     * Sets the ionice_level for where this task should be executed
     *
     * @param int|null $ionice_level
     * @return static
     */
    public function setIoniceLevel(int|null $ionice_level): static;

    /**
     * Returns the nice for where this task should be executed
     *
     * @return int|null
     */
    public function getNice(): ?int;

    /**
     * Sets the nice for where this task should be executed
     *
     * @param int|null $nice
     * @return static
     */
    public function setNice(int|null $nice): static;

    /**
     * Returns the timeout for where this task should be executed
     *
     * @return int|null
     */
    public function getTimeout(): ?int;

    /**
     * Sets the timeout for where this task should be executed
     *
     * @param int|null $timeout
     * @return static
     */
    public function setTimeout(int|null $timeout): static;

    /**
     * Returns the wait for where this task should be executed
     *
     * @return int|null
     */
    public function getWait(): ?int;

    /**
     * Sets the wait for where this task should be executed
     *
     * @param int|null $wait
     * @return static
     */
    public function setWait(int|null $wait): static;

    /**
     * Returns the background for where this task should be executed
     *
     * @return bool
     */
    public function getBackground(): bool;

    /**
     * Sets the background for where this task should be executed
     *
     * @param bool|null $background
     * @return static
     */
    public function setBackground(int|bool|null $background): static;

    /**
     * Returns the clear_logs for where this task should be executed
     *
     * @return bool
     */
    public function getClearLogs(): bool;

    /**
     * Sets the clear_logs for where this task should be executed
     *
     * @param bool|null $clear_logs
     * @return static
     */
    public function setClearLogs(int|bool|null $clear_logs): static;

    /**
     * Returns if this task should escape quotes in the arguments
     *
     * @return bool
     */
    public function getEscapeQuotes(): bool;

    /**
     * Sets if this task should escape quotes in the arguments
     *
     * @param bool|null $escape_quotes
     * @return static
     */
    public function setEscapeQuotes(int|bool|null $escape_quotes): static;

    /**
     * Returns the log_file for this task
     *
     * @return string|null
     */
    public function getLogFile(): ?string;

    /**
     * Returns the pid_file for this task
     *
     * @return string|null
     */
    public function getPidFile(): ?string;

    /**
     * Returns the sudo string for this task
     *
     * @return string|null
     */
    public function getSudo(): ?string;

    /**
     * Sets if this task should use sudo
     *
     * @param string|null $sudo
     * @return static
     */
    public function setSudo(?string $sudo): static;

    /**
     * Returns the term string for this task
     *
     * @return string|null
     */
    public function getTerm(): ?string;

    /**
     * Sets if this task should use term
     *
     * @param string|null $term
     * @return static
     */
    public function setTerm(?string $term): static;

    /**
     * Returns where the ouput of this command should be piped to
     *
     * @return string|null
     */
    public function getPipe(): ?string;

    /**
     * Sets where the ouput of this command should be piped to
     *
     * @param string|null $pipe
     * @return static
     */
    public function setPipe(?string $pipe): static;

    /**
     * Returns where the input should be redirected from
     *
     * @return string|null
     */
    public function getInputRedirect(): ?string;

    /**
     * Sets where the input should be redirected from
     *
     * @param string|null $input_redirect
     * @return static
     */
    public function setInputRedirect(?string $input_redirect): static;

    /**
     * Returns where the output should be redirected from
     *
     * @return string|null
     */
    public function getOutputRedirect(): ?string;

    /**
     * Sets where the output should be redirected from
     *
     * @param string|null $output_redirect
     * @return static
     */
    public function setOutputRedirect(?string $output_redirect): static;

    /**
     * Returns access restrictions for this task
     *
     * @return string|null
     */
    public function getRestrictions(): ?string;

    /**
     * Sets access restrictions for this task
     *
     * @param string|null $restrictions
     * @return static
     */
    public function setRestrictions(?string $restrictions): static;

    /**
     * Returns packages required for this task
     *
     * @return string|null
     */
    public function getPackages(): ?string;

    /**
     * Sets packages required for this task
     *
     * @param string|null $packages
     * @return static
     */
    public function setPackages(?string $packages): static;

    /**
     * Returns pre_exec for this task
     *
     * @return string|null
     */
    public function getPreExec(): ?string;

    /**
     * Sets pre_exec for this task
     *
     * @param string|null $pre_exec
     * @return static
     */
    public function setPreExec(?string $pre_exec): static;

    /**
     * Returns post_exec for this task
     *
     * @return string|null
     */
    public function getPostExec(): ?string;

    /**
     * Sets post_exec for this task
     *
     * @param string|null $post_exec
     * @return static
     */
    public function setPostExec(?string $post_exec): static;

    /**
     * Returns comments for this task
     *
     * @return string|null
     */
    public function getComments(): ?string;

    /**
     * Sets comments for this task
     *
     * @param string|null $comments
     * @return static
     */
    public function setComments(?string $comments): static;

    /**
     * Returns results for this task
     *
     * @return string
     */
    public function getResults(): string;

    /**
     * Returns execution_directory for this task
     *
     * @return string|null
     */
    public function getExecutionDirectory(): ?string;

    /**
     * Sets execution_directory for this task
     *
     * @param string|null $execution_directory
     * @return static
     */
    public function setExecutionDirectory(?string $execution_directory): static;

    /**
     * Returns command for this task
     *
     * @return string|null
     */
    public function getCommand(): ?string;

    /**
     * Sets command for this task
     *
     * @param string|null $command
     * @return static
     */
    public function setCommand(?string $command): static;

    /**
     * Returns executed_command for this task
     *
     * @return string
     */
    public function getExecutedCommand(): string;

    /**
     * Returns arguments for this task
     *
     * @return array|null
     */
    public function getArguments(): ?array;

    /**
     * Sets arguments for this task
     *
     * @param array|null $arguments
     * @return static
     */
    public function setArguments(?array $arguments): static;

    /**
     * Returns variables for this task
     *
     * @return array|null
     */
    public function getVariables(): ?array;

    /**
     * Sets variables for this task
     *
     * @param array|null $variables
     * @return static
     */
    public function setVariables(array|null $variables): static;

    /**
     * Returns environment_variables for this task
     *
     * @return array|null
     */
    public function getEnvironmentVariables(): ?array;

    /**
     * Sets environment_variables for this task
     *
     * @param array|null $environment_variables
     * @return static
     */
    public function setEnvironmentVariables(array|null $environment_variables): static;

    /**
     * Returns accepted_exit_codes for this task
     *
     * @return array|null
     */
    public function getAcceptedExitCodes(): ?array;

    /**
     * Sets accepted_exit_codes for this task
     *
     * @param array|null $accepted_exit_codes
     * @return static
     */
    public function setAcceptedExitCodes(array|null $accepted_exit_codes): static;

    /**
     * Returns the code for this object
     *
     * @return string|null
     */
    public function getCode(): ?string;

    /**
     * Executes this task, and stores all relevant results data in the database
     *
     * @return static
     */
    public function execute(): static;

    /**
     * Apply the given source
     *
     * @param bool $clear_source
     * @param array|ValidatorInterface|null $source
     * @return $this
     * @throws \Exception
     */
    public function apply(bool $clear_source = true, array|ValidatorInterface|null &$source = null): static;
}