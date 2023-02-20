<?php

namespace Phoundation\Core\Meta;

use Phoundation\Cli\Script;
use Phoundation\Core\Session;
use Phoundation\Data\Exception\DataEntryNotExistsException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Web\Http\Html\Components\Table;
use Phoundation\Web\Http\UrlBuilder;



/**
 * Meta class
 *
 * This class keeps track of metadata for database entries throughout phoundation projects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Core
 */
class Meta
{
    /**
     * The database entry for this meta id
     *
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * If true will store and process meta data. If false, it won't
     *
     * @var bool $enbabled
     */
    protected static bool $enbabled = true;

    /**
     * The history of this meta entry
     *
     * @var array $history
     */
    protected array $history = [];



    /**
     * Meta constructor
     *
     * @param int|null $id
     */
    public function __construct(?int $id = null)
    {
        if ($id) {
            // Load the specified metadata
            $this->load($id);
        } else {
            if ($id === 0) {
                // if specified $id is 0 then just return an empty object
                $this->id = 0;
            } else {
                // create a new metadata entry
                $retry = 0;

                while ($retry++ < 5) {
                    try {
                        $this->id = mt_rand(0, PHP_INT_MAX);

                        sql()->query('INSERT INTO `meta` (`id`)
                                            VALUES             (' . $this->id . ')');

                    } catch (SqlException $e) {
                        if ($e->getCode() !== 1062) {
                            // Some different error occurred, keep throwing
                            throw $e;
                        }

                        // If we got here we have a duplicate entry, try with a different random number
                    }
                }
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
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Enable meta data processing
     *
     * @return void
     */
    public static function enable(): void
    {
        self::$enbabled = true;
    }



    /**
     * Disable meta data processing
     *
     * @return void
     */
    public static function disable(): void
    {
        self::$enbabled = false;
    }



    /**
     * Returns a metadata object for the specified id
     *
     * @param int|null $id
     * @return Meta
     */
    public static function get(?int $id = null): Meta
    {
        return new Meta($id);
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
     * Creates a new meta entry and returns the database id for it
     *
     * @param string|null $comments
     * @param string|null $data
     * @return Meta
     */
    public static function init(?string $comments = null, ?string $data = null): Meta
    {
        if (self::$enbabled) {
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
        if (self::$enbabled and $this->id) {
            // Insert the action in the meta_history table
            sql()->query('INSERT INTO `meta_history` (`meta_id`, `created_by`, `action`, `source`, `comments`, `data`) 
                            VALUES                     (:meta_id , :created_by , :action , :source , :comments , :data )', [
                ':meta_id'    => $this->id,
                ':created_by' => Session::getUser()->getId(),
                ':source'     => (string) (PLATFORM_HTTP ? UrlBuilder::getCurrent() : Script::getCurrent()),
                ':action'     => $action,
                ':comments'   => $comments,
                ':data'       => $data
            ]);
        }

        return $this;
    }



    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return Table
     */
    public function getHtmlTable(): Table
    {
        // Create and return the table
        return Table::new()->setSourceQuery('SELECT * FROM `meta_history` WHERE `meta_id` = :meta_id', [':meta_id' => $this->id]);
    }



    /**
     * Load data for the specified meta id
     *
     * @param int $id
     * @return void
     */
    protected function load(int $id): void
    {
        $this->id = sql()->getColumn('SELECT `id` FROM `meta` WHERE `id` = :id', [':id' => $id]);

        if (!$this->id) {
            throw new DataEntryNotExistsException(tr('The specified meta id ":id" does not exist', [
                ':id' => $id
            ]));
        }
    }
}