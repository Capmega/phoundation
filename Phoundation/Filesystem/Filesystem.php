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
use Phoundation\Filesystem\Interfaces\FilesystemInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;

class Filesystem extends File implements FilesystemInterface
{
    /**
     * Filesystem class constructor
     *
     * @param mixed|null                              $source
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param bool                                    $make_absolute
     */
    public function __construct(mixed $source = null, RestrictionsInterface|array|string|null $restrictions = null, bool $make_absolute = false) {
        parent::__construct($source, $restrictions, $make_absolute);

        if (!$this->path) {
            throw new NoFilesystemSpecifiedException(tr('No filesystem specified'));
        }

        if (!$this->exists()) {
            throw new FilesystemDoesNotExistException(tr('The specified filesystem ":filesystem" does not exist', [
                ':filesystem' => $this->path
            ]));
        }

        $this->followLink(true);

        if (!Filesystems::new(true)->get($this->path, false)) {
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

        return $results->get($this->path, false);
    }
}
