<?php

/**
 * Class Filesystem
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Cache\InstanceCache;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Commands\Df;
use Phoundation\Filesystem\Exception\FilesystemDoesNotExistException;
use Phoundation\Filesystem\Exception\NoFilesystemSpecifiedException;
use Phoundation\Filesystem\Exception\NotAFilesystemException;
use Phoundation\Filesystem\Interfaces\FsFilesystemInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Stringable;

class FsFilesystem extends FsFile implements FsFilesystemInterface
{
    /**
     * Filesystem class constructor
     *
     * @param mixed|null                        $source
     * @param FsRestrictionsInterface|bool|null $restrictions
     * @param Stringable|string|bool|null       $absolute_prefix
     */
    public function __construct(Stringable|string|null $source = null, FsRestrictionsInterface|bool|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false) {
        parent::__construct($source, $restrictions, $absolute_prefix);

        if (!$this->path) {
            throw new NoFilesystemSpecifiedException(tr('No filesystem specified'));
        }

        if (!$this->exists()) {
            throw new FilesystemDoesNotExistException(tr('The specified filesystem ":filesystem" does not exist', [
                ':filesystem' => $this->path
            ]));
        }

        $this->followLink(true);

        if (!FsFilesystems::new()->get($this->path, false)) {
            throw new NotAFilesystemException(tr('The specified value ":filesystem" is not a filesystem', [
                ':filesystem' => $this->path
            ]));
        }
    }


    /**
     * Returns the total space in bytes for this filesystem
     *
     * @return int
     */
    public function getTotalSpace(): int
    {
        return (int) $this->getDfData()['size'];
    }


    /**
     * Returns the available space in bytes for this filesystem
     *
     * @return int
     */
    public function getAvailableSpace(): int
    {
        return (int) $this->getDfData()['available'];
    }


    /**
     * Returns the used space in bytes for this filesystem
     *
     * @return int
     */
    public function getUsedSpace(): int
    {
        return (int) $this->getDfData()['used'];
    }


    /**
     * Returns cached output from df
     *
     * @return array
     */
    protected function getDfData(): array
    {
        $results = InstanceCache::getOrGenerate('df', 'all', function () {
            $results = Df::new()
                         ->executeNoReturn()
                         ->getResults();

            return InstanceCache::set($results, 'df', 'all');
        });

        // Adjust values to be byte precise
        $results = $results->get($this->path, false);

        $results['used']      = (int) floor($results['used'] * 1024);
        $results['size']      = (int) floor($results['size'] * 1024);
        $results['available'] = (int) floor($results['available'] * 1024);

        return $results;
    }


    /**
     * Returns true if this is an encrypted filesystem
     *
     * @return bool
     */
    public function isEncrypted(): bool
    {
return false;
    }
}
