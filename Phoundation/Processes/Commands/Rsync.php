<?php

namespace Phoundation\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataNetworkConnection;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataTarget;



/**
 * Class Rsync
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Processes
 */
class Rsync extends Command
{
    use DataSource;
    use DataTarget;
    use DataNetworkConnection;



    /**
     * Show progress of larger files
     *
     * @var bool $progress
     */
    protected bool $progress = false;

    /**
     * Archive mode, is -rlptgoD (no -A,-X,-U,-N,-H)
     *
     * @var bool
     */
    protected bool $archive = true;

    /**
     * If output should be more verbose
     *
     * @var bool $verbose
     */
    protected bool $verbose = true;

    /**
     * Suppress non-error messages
     *
     * @var bool $quiet
     */
    protected bool $quiet = false;

    /**
     * If compression should be used during the transfer
     *
     * @var bool $compress
     */
    protected bool $compress = false;

    /**
     * If set, will ignore symlinks that point outside the tree
     *
     * @var bool $safe_links
     */
    protected bool $safe_links = true;

    /**
     * What files to exclude
     *
     * @var array $exclude
     */
    protected array $exclude = [];

    /**
     * The remote rsync command
     *
     * @var string|null $rsync_path
     */
    protected ?string $rsync_path = null;

    /**
     * Should parameters be human-readable
     *
     * @var bool $human_readable
     */
    protected bool $human_readable = false;

    /**
     * The command for the remote shell connection
     *
     * @var string|null $rsh
     */
    protected ?string $rsh = null;

    /**
     * The SSH key
     *
     * @var string|null $ssh_key
     */
    protected ?string $ssh_key = null;



    /**
     * Returns if file progress should be displayed or not
     *
     * @return bool
     */
    public function getProgress(): bool
    {
        return $this->progress;
    }



    /**
     * Sets if file progress should be displayed or not
     *
     * @param bool $progress
     * @return static
     */
    public function setProgress(bool $progress): static
    {
        $this->progress = $progress;
        return $this;
    }



    /**
     * Returns if archive mode should be used
     *
     * @return bool
     */
    public function getArchive(): bool
    {
        return $this->archive;
    }



    /**
     * Sets if archive mode should be used
     *
     * @param bool $archive
     * @return static
     */
    public function setArchive(bool $archive): static
    {
        $this->archive = $archive;
        return $this;
    }



    /**
     * Returns if output should be more verbose
     *
     * @return bool
     */
    public function getVerbose(): bool
    {
        return $this->verbose;
    }



    /**
     * Sets if output should be more verbose
     *
     * @param bool $verbose
     * @return static
     */
    public function setVerbose(bool $verbose): static
    {
        $this->verbose = $verbose;
        return $this;
    }



    /**
     * Returns if non-error messages should be  suppressed
     *
     * @return bool
     */
    public function getQuiet(): bool
    {
        return $this->quiet;
    }



    /**
     * Sets if non-error messages should be  suppressed
     *
     * @param bool $quiet
     * @return static
     */
    public function setQuiet(bool $quiet): static
    {
        $this->quiet = $quiet;
        return $this;
    }



    /**
     * Returns if rsync will ignore symlinks that point outside the tree
     *
     * @return bool
     */
    public function getSafeLink(): bool
    {
        return $this->safe_links;
    }



    /**
     * Sets if rsync will ignore symlinks that point outside the tree
     *
     * @param bool $safe_links
     * @return static
     */
    public function setSafeLink(bool $safe_links): static
    {
        $this->safe_links = $safe_links;
        return $this;
    }



    /**
     * Returns if compression should be used during the transfer
     *
     * @return bool
     */
    public function getCompress(): bool
    {
        return $this->compress;
    }



    /**
     * Sets if compression should be used during the transfer
     *
     * @param bool $compress
     * @return static
     */
    public function setCompress(bool $compress): static
    {
        $this->compress = $compress;
        return $this;
    }



    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param bool $background
     * @return int|null
     */
    public function execute(bool $background = false): ?int
    {
        // Build the process parameters, then execute
        $this->process
            ->clearArguments()
            ->setCommand('rsync')
            ->addArgument($this->progress ? '--progress' : null)
            ->addArgument($this->archive ? '-a' : null)
            ->addArgument($this->quiet ? '-q' : null)
            ->addArgument($this->verbose ? '-v' : null)
            ->addArgument($this->compress ? '-z' : null)
            ->addArgument($this->safe_links ? '--safe-links' : null)
            ->addArgument($this->rsh ? '-e' : null)
            ->addArgument($this->rsh)
            ->addArgument($this->ssh_key ? '-i' : null)
            ->addArgument($this->ssh_key)
            ->addArgument($this->rsync_path ? '--rsync-path' : null)
            ->addArgument($this->rsync_path)
            ->addArgument($this->source)
            ->addArgument($this->target);

        if ($background) {
            $pid = $this->process->executeBackground();

            Log::success(tr('Executed rsync as a background process with PID ":pid"', [
                ':pid' => $pid
            ]), 4);

            return $pid;

        }

        $results = $this->process->executeReturnArray();

        Log::notice($results, 4);
        return null;
    }
}