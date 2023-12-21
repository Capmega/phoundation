<?php

declare(strict_types=1);

namespace Phoundation\Databases;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataConnector;
use Phoundation\Data\Traits\DataDebug;
use Phoundation\Data\Traits\DataDriver;
use Phoundation\Data\Traits\DataFile;
use Phoundation\Data\Traits\DataHost;
use Phoundation\Data\Traits\DataPort;
use Phoundation\Data\Traits\DataTimeout;
use Phoundation\Data\Traits\DataUserPass;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Phoundation\Os\Processes\Commands\Databases\MySql;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Enum\Interfaces\EnumExecuteMethodInterface;


/**
 * Class Import
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Database
 */
class Import
{
    use DataTimeout;
    use DataDriver;
    use DataPort;
    use DataHost;
    use DataUserPass;
    use DataDebug;
    use DataFile;
    use DataConnector;
    use DataRestrictions;


    /**
     * The database that will be dumped
     *
     * @var string|null $database
     */
    protected ?string $database = null;

    /**
     * Tracks if the database should be dropped before import
     *
     * @var bool $drop
     */
    protected bool $drop = true;


    /**
     * Exporter class constructor
     *
     * @param RestrictionsInterface|null $restrictions
     */
    public function __construct(?RestrictionsInterface $restrictions = null)
    {
        $this->restrictions = Restrictions::default($restrictions, Restrictions::writable('/', 'Mysql exporter'));
    }


    /**
     * Returns a new Export object
     *
     * @param RestrictionsInterface|null $restrictions
     * @return static
     */
    public static function new(?RestrictionsInterface $restrictions = null): static
    {
        return new static($restrictions);
    }


    /**
     * Sets if the database should be dropped before import
     *
     * @return bool
     */
    public function getDrop(): bool
    {
        return $this->drop;
    }


    /**
     * Sets if the database should be dropped before import
     *
     * @param bool $drop
     * @return static
     */
    public function setDrop(bool $drop): static
    {
        $this->drop = $drop;
        return $this;
    }


    /**
     * Returns the database that will be imported
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->database;
    }


    /**
     * Sets the database that will be imported
     *
     * @param string|null $database
     * @return static
     */
    public function setDatabase(?string $database): static
    {
        $this->database = $database;
        return $this;
    }


    /**
     * Execute the rsync operation and return the PID (background) or -1
     *
     * @param EnumExecuteMethodInterface $method
     * @return static
     */
    public function import(EnumExecuteMethodInterface $method = EnumExecuteMethod::passthru): static
    {
        switch ($this->driver) {
            case 'mysql':
                Log::information(tr('Importing MySQL dump file ":file" to database ":database", this may take a while...', [
                    ':file'     => $this->file,
                    ':database' => $this->database,
                ]));

                MySql::new()
                    ->setConnector($this->connector)
                    ->drop($this->drop ? $this->database : null)
                    ->create($this->drop ? $this->database : null)
                    ->import($this->file, Restrictions::new('/'));

                Log::success(tr('Finished importing MySQL dump file ":file" to database ":database"', [
                    ':file'     => $this->file,
                    ':database' => $this->database,
                ]));

                break;

            case 'redis':
                // no break
            case 'mongo':
                // no break
            case 'mongodb':
                // no break
            case 'elastic':
                // no break
            case 'elasticsearch':
                // no break
                throw new UnderConstructionException();
        }

        return $this;
    }
}
