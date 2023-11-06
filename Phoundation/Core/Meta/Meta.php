<?php

declare(strict_types=1);

namespace Phoundation\Core\Meta;

use DateTime;
use Exception;
use Phoundation\Cli\CliCommand;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Exception\MetaException;
use Phoundation\Core\Meta\Interfaces\MetaInterface;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\Exception\DataEntryNotExistsException;
use Phoundation\Data\Validator\Validate;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Html\Components\HtmlTable;
use Phoundation\Web\Html\Components\Interfaces\HtmlTableInterface;
use Phoundation\Web\Http\UrlBuilder;
use Throwable;


/**
 * Meta class
 *
 * This class keeps track of metadata for database entries throughout phoundation projects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Core
 */
class Meta implements MetaInterface
{
    /**
     * The database entry for this meta id
     *
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * If true will store and process metadata. If false, it won't
     *
     * @var bool $enabled
     */
    protected static bool $enabled = true;

    /**
     * The history of this meta entry
     *
     * @var array $history
     */
    protected array $history = [];

    /**
     * If true will buffer all meta updates until the system shuts down
     *
     * @var bool $buffer
     */
    protected static bool $buffer;

    /**
     * In case buffer mode is enabled, all meta updates will be stored here until flushing
     *
     * @var array $updates
     */
    protected static array $updates = [];

    /**
     * In case buffer mode is enabled, this is the buffer pointer
     *
     * @var int $pointer
     */
    protected static int $pointer = 1;


    /**
     * Meta constructor
     *
     * @param int|null $id
     * @param bool $load
     */
    public function __construct(?int $id = null, bool $load = true)
    {
        if (!isset(static::$buffer)) {
            static::$buffer = Config::getBoolean('meta.buffer', false);
        }

        if ($id) {
            if ($load) {
                // Load the specified metadata
                $this->load($id);
            } else {
                // We're assuming this ID exists in the meta system
                $this->id = $id;
            }

        } else {
            if ($id === 0) {
                // if specified $id is 0 then just return an empty object
                $this->id = 0;

            } else {
                // create a new metadata entry
                $retry = 0;

                while ($retry++ < 5) {
                    try {
                        $this->id = random_int(0, PHP_INT_MAX);

                        sql()->query('INSERT INTO `meta` (`id`)
                                            VALUES             (' . $this->id . ')');
                        return;

                    } catch (SqlException $e) {
                        if ($e->getCode() !== 1062) {
                            // Some different error occurred, keep throwing
                            throw $e;
                        }

                        // If we got here we have a duplicate entry, try with a different random number
                    }
                }

                throw new MetaException(tr('Failed to create meta record after 5 retries, see previous exception why'), $e);
            }
        }
    }


    /**
     * Returns the Meta id
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->id;
    }


    /**
     * Returns a new Meta object
     *
     * @param bool $load
     * @return static
     * @throws Exception
     */
    public static function new(bool $load = true): static
    {
        return new static(null, $load);
    }


    /**
     * Returns if meta system is enabled or not
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return static::$enabled;
    }


    /**
     * Enable meta data processing
     *
     * @return void
     */
    public static function enable(): void
    {
        static::$enabled = true;
    }


    /**
     * Disable meta data processing
     *
     * @return void
     */
    public static function disable(): void
    {
        static::$enabled = false;
    }


    /**
     * Returns a metadata object for the specified id
     *
     * @param int|null $id
     * @param bool $load
     * @return static
     */
    public static function get(?int $id = null, bool $load = true): static
    {
        return new static($id, $load);
    }


    /**
     * Returns the id for this metadata object
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }


    /**
     * Erases all meta history for the specified meta ids
     *
     * @param array|string|int $ids
     * @return void
     */
    public static function eraseEntries(array|string|int $ids): void
    {
        // Erase the meta entry, the history will cascade
        $ids = Sql::in(Arrays::force($ids));
        sql()->query('DELETE FROM `meta` WHERE `id` IN (' . Sql::inColumns($ids) . ')', $ids);
    }


    /**
     * Erases all meta history for this meta id
     *
     * @return void
     */
    public function erase(): void
    {
        if (empty($this->id)) {
            throw new OutOfBoundsException(tr('Cannot erase this meta object, it does not yet exist in the database'));
        }

        static::eraseEntries($this->id);
    }


    /**
     * Erases all meta history for entries from before the $before date
     *
     * @param DateTime|string $before
     * @param int $limit
     * @return void
     */
    public static function purge(DateTime|string $before, int $limit = 10_000): void
    {
        Validate::new($limit)->isMoreThan(0);

        $before = \Phoundation\Date\DateTime::new($before)->format('mysql');

        sql()->list('DELETE FROM `meta_history` WHERE `created_on` < :created_on' . ($limit ? ' LIMIT ' . $limit : null), [
            ':created_on' => $before,
        ]);
    }


    /**
     * Erases all meta entries that have no database entries using them.
     *
     * @param int $limit
     * @return int
     */
    public static function deorphan(int $limit = 1_000_000): int
    {
throw new UnderConstructionException();
        Validate::new($limit)->isMoreThan(0);

        $ids = [];

        if ($ids) {
            static::eraseEntries($ids);
        }

        return count($ids);
    }


    /**
     * Creates a new meta entry and returns the database id for it
     *
     * @param string|null $comments
     * @param string|null $data
     * @return Meta
     */
    public static function init(?string $comments = null, ?string $data = null): Meta
    {
        if (static::$enabled) {
            $meta = new Meta();
            $meta->action('created', $comments, $data);

            return $meta;
        }

        // Return an empty meta-object that won't store any actions
        return new Meta(0);
    }


    /**
     * Creates a new meta entry and returns the database id for it
     *
     * @param string $action
     * @param string|null $comments
     * @param string|null $data
     * @return static
     */
    public function action(string $action, ?string $comments = null, ?string $data = null): static
    {
        if (static::$enabled and $this->id) {
            if (static::$buffer) {
                static::$updates[++static::$pointer] = [
                    ':meta_id_' . static::$pointer    => $this->id,
                    ':created_by_' . static::$pointer => Session::getUser()->getId(),
                    ':source_' . static::$pointer     => (string) (PLATFORM_HTTP ? UrlBuilder::getCurrent() : CliCommand::getCurrent()),
                    ':action_' . static::$pointer     => $action,
                    ':comments_' . static::$pointer   => $comments,
                    ':data_' . static::$pointer       => $data
                ];
            } else {
                // Insert the action in the meta_history table
                sql()->query('INSERT INTO `meta_history` (`meta_id`, `created_by`, `action`, `source`, `comments`, `data`) 
                                VALUES                     (:meta_id , :created_by , :action , :source , :comments , :data )', [
                    ':meta_id'    => $this->id,
                    ':created_by' => Session::getUser()->getId(),
                    ':source'     => (string) (PLATFORM_HTTP ? UrlBuilder::getCurrent() : CliCommand::getCurrent()),
                    ':action'     => $action,
                    ':comments'   => $comments,
                    ':data'       => $data
                ]);
            }
        }

        return $this;
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     * @return HtmlTableInterface
     */
    public function getHtmlTable(array|string|null $columns = null): HtmlTableInterface
    {
        // Create and return the table
        return HtmlTable::new()->setSourceQuery('SELECT * FROM `meta_history` WHERE `meta_id` = :meta_id', [':meta_id' => $this->id]);
    }


    /**
     * Flush the entire meta buffer to the database.
     *
     * @return void
     */
    public static function flush(): void
    {
        try {
            if (static::$updates) {
                $values  = ' (:meta_id_:ID , :created_by_:ID , :action_:ID , :source_:ID , :comments_:ID , :data_:ID)';
                $execute = [];

                // Build query and execute arrays
                foreach (static::$updates as $pointer => $update) {
                    $query[] = str_replace(':ID', (string) $pointer, $values);
                    $execute = array_merge($execute, $update);
                }

                // Complete query
                $query = 'INSERT INTO `meta_history` (`meta_id`, `created_by`, `action`, `source`, `comments`, `data`) VALUES ' . implode(', ', $query);

                // Flush!
                sql()->query($query, $execute);
            }

        } catch (Throwable $e) {
            Log::error(tr('Failed to flush ":count" meta entries with following exception', [
                ':count' => count(static::$updates)
            ]));

            Log::error($e);
        }
    }


    /**
     * Load data for the specified meta id
     *
     * @param int $id
     * @return void
     */
    protected function load(int $id): void
    {
        $this->id = sql()->getInteger('SELECT `id` FROM `meta` WHERE `id` = :id', [':id' => $id]);

        if (!$this->id) {
            throw new DataEntryNotExistsException(tr('The specified meta id ":id" does not exist', [
                ':id' => $id
            ]));
        }
    }
}
