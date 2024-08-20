<?php

/**
 * Class Rsync
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands\Interfaces;

use Phoundation\Os\Processes\Enum\EnumExecuteMethod;

interface RsyncInterface
{
    /**
     * Returns if destination files should be deleted if not existing on source
     *
     * @return bool
     */
    public function getDelete(): bool;


    /**
     * Sets if destination files should be deleted if not existing on source
     *
     * @param bool $delete
     *
     * @return static
     */
    public function setDelete(bool $delete): static;


    /**
     * Returns if file progress should be displayed or not
     *
     * @return bool
     */
    public function getProgress(): bool;


    /**
     * Sets if file progress should be displayed or not
     *
     * @param bool $progress
     *
     * @return static
     */
    public function setProgress(bool $progress): static;


    /**
     * Returns the paths that will be ignored
     *
     * @return array
     */
    public function getExclude(): array;


    /**
     * Clears the "exclude path" list
     *
     * @return static
     */
    public function clearExclude(): static;


    /**
     * Adds the specified paths to the list that will be excluded
     *
     * @param array|string $paths
     *
     * @return static
     */
    public function addExclude(array|string $paths): static;


    /**
     * Sets the specified paths to the list that will be excluded
     *
     * @param array|string $paths
     *
     * @return static
     */
    public function setExclude(array|string $paths): static;


    /**
     * Returns if archive mode should be used
     *
     * @return bool
     */
    public function getArchive(): bool;


    /**
     * Sets if archive mode should be used
     *
     * @param bool $archive
     *
     * @return static
     */
    public function setArchive(bool $archive): static;


    /**
     * Returns if output should be more verbose
     *
     * @return bool
     */
    public function getVerbose(): bool;


    /**
     * Sets if output should be more verbose
     *
     * @param bool $verbose
     *
     * @return static
     */
    public function setVerbose(bool $verbose): static;


    /**
     * Returns if non-error messages should be  suppressed
     *
     * @return bool
     */
    public function getQuiet(): bool;


    /**
     * Sets if non-error messages should be  suppressed
     *
     * @param bool $quiet
     *
     * @return static
     */
    public function setQuiet(bool $quiet): static;


    /**
     * Returns if rsync should be executed using sudo on the remote host
     *
     * @return bool
     */
    public function getRemoteSudo(): bool;


    /**
     * Returns if rsync should be executed using sudo on the remote host
     *
     * @param string|bool|null $sudo
     *
     * @return static
     */
    public function setRemoteSudo(string|bool|null $sudo): static;


    /**
     * Returns the remote rsync command
     *
     * @return string|null
     */
    public function getRsyncPath(): ?string;


    /**
     * Sets the remote rsync command
     *
     * @param string|null $rsync_path
     *
     * @return static
     */
    public function setRsyncPath(?string $rsync_path): static;


    /**
     * Returns if rsync will ignore symlinks that point outside the tree
     *
     * @return bool
     */
    public function getSafeLink(): bool;


    /**
     * Sets if rsync will ignore symlinks that point outside the tree
     *
     * @param bool $safe_links
     *
     * @return static
     */
    public function setSafeLink(bool $safe_links): static;


    /**
     * Returns if compression should be used during the transfer
     *
     * @return bool
     */
    public function getCompress(): bool;


    /**
     * Sets if compression should be used during the transfer
     *
     * @param bool $compress
     *
     * @return static
     */
    public function setCompress(bool $compress): static;


    /**
     * Returns the full command line
     *
     * @param bool $background
     *
     * @return string
     */
    public function getFullCommandLine(bool $background = false): string;


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethod $method
     *
     * @return string|int|bool|array|null
     */
    public function execute(EnumExecuteMethod $method = EnumExecuteMethod::passthru): string|int|bool|array|null;
}
