<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataDebug;
use Phoundation\Data\Traits\DataNetworkConnection;
use Phoundation\Data\Traits\DataSource;
use Phoundation\Data\Traits\DataSourceServer;
use Phoundation\Data\Traits\DataTarget;
use Phoundation\Data\Traits\DataTargetServer;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;
use Phoundation\Utils\Arrays;


/**
 * Class Rsync
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
class Rsync extends Command
{
    use DataDebug;
    use DataNetworkConnection;
    use DataSource;
    use DataSourceServer;
    use DataTarget;
    use DataTargetServer;


    /**
     * Show progress of larger files
     *
     * @var bool $progress
     */
    protected bool $progress = false;

    /**
     * Archive mode is -rlptgoD (no -A,-X,-U,-N,-H)
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
     * Tracks if destination files should be deleted if not existing on source
     *
     * @var bool $delete
     */
    protected bool $delete = false;


    /**
     * Returns if destination files should be deleted if not existing on source
     *
     * @return bool
     */
    public function getDelete(): bool
    {
        return $this->delete;
    }


    /**
     * Sets if destination files should be deleted if not existing on source
     *
     * @param bool $delete
     * @return static
     */
    public function setDelete(bool $delete): static
    {
        $this->delete = $delete;
        return $this;
    }


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
     * Returns the paths that will be ignored
     *
     * @return array
     */
    public function getExclude(): array
    {
        return $this->exclude;
    }


    /**
     * Clears the "exclude path" list
     *
     * @return static
     */
    public function clearExclude(): static
    {
        $this->exclude = [];
        return $this;
    }


    /**
     * Adds the specified paths to the list that will be excluded
     *
     * @param array|string $directories
     * @return static
     */
    public function addExclude(array|string $directories): static
    {
        foreach (Arrays::force($directories) as $directory) {
            $this->exclude[] = $directory;
        }

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
     * Returns if rsync should be executed using sudo on the remote host
     *
     * @return bool
     */
    public function getRemoteSudo(): bool
    {
        return $this->rsync_path === 'sudo rsync';
    }


    /**
     * Returns if rsync should be executed using sudo on the remote host
     *
     * @param bool $sudo
     * @return static
     */
    public function setRemoteSudo(bool $sudo): static
    {
        $this->rsync_path = 'sudo rsync';
        return $this;
    }


    /**
     * Returns the remote rsync command
     *
     * @return string|null
     */
    public function getRsyncPath(): ?string
    {
        return $this->rsync_path;
    }


    /**
     * Sets the remote rsync command
     *
     * @param string|null $rsync_path
     * @return static
     */
    public function setRsyncPath(?string $rsync_path): static
    {
        $this->rsync_path = $rsync_path;
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
     * @param EnumExecuteMethodInterface $method
     * @return string|int|bool|array|null
     */
    public function execute(EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): string|int|bool|array|null
    {
        // If port is a non-default SSH port, then generate the RSH variable
        if (empty($this->rsh)) {
            if ($this->source_server) {
                $this->port = $this->source_server->getPort();

            } elseif ($this->target_server) {
                $this->port = $this->target_server->getPort();
            }

            if ($this->port) {
                $this->rsh = 'ssh -p ' . $this->port;
            }
        }

        // Build the process parameters, then execute
        $this->setCommand('rsync')
             ->addArgument($this->progress   ? '--progress'   : null)
             ->addArgument($this->archive    ? '-a'           : null)
             ->addArgument($this->quiet      ? '-q'           : null)
             ->addArgument($this->verbose    ? '-v'           : null)
             ->addArgument($this->compress   ? '-z'           : null)
             ->addArgument($this->safe_links ? '--safe-links' : null)
             ->addArgument($this->delete     ? '--delete'     : null)
             ->addArgument($this->rsh        ? '-e'           : null)
             ->addArgument($this->rsh)
             ->addArgument($this->ssh_key    ? '-i'           : null)
             ->addArgument($this->ssh_key)
             ->addArgument($this->rsync_path ? '--rsync-path=' . escapeshellarg($this->rsync_path) : null, false)
             ->addArgument($this->source)
             ->addArgument($this->target);

        foreach ($this->exclude as $exclude) {
            $this->addArgument('--exclude=' . escapeshellarg($exclude), false);
        }

        $results = parent::execute($method);

        if ($this->debug) {
            Log::information(tr('Output of the rsync command:'), 4);
            Log::notice($results, 4);
        }

        return $results;
    }
}
