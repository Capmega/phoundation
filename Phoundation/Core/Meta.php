<?php

namespace Phoundation\Core;

use Phoundation\Cli\Script;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Utils\Json;
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
     * The history of this meta entry
     *
     * @var array $history
     */
    protected array $history = [];



    /**
     * Meta constructor
     *
     * @param int|null $id
     * @param bool $load
     */
    public function __construct(?int $id = null, bool $load = false)
    {
        if ($id and $load) {
            $this->load($id);
        } else {
            $retry = 0;

            while ($retry++ < 5) {
                try {
                    $this->id = mt_rand(0, PHP_INT_MAX);

                    sql()->query('INSERT INTO `meta` (`id`)
                                        VALUES             (' . $this->id . ')');

                } catch (SqlException $e) {
                    if ($e->getCode() !== 1062) {
                        // Some different error, keep throwing
                        throw $e;
                    }

                    // Duplicate entry, try with a different random number
                }
            }
        }
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
     * Returns a meta-object for the specified id
     *
     * @param int|null $id
     * @param bool $load
     * @return Meta
     */
    public static function get(?int $id = null, bool $load = false): Meta
    {
        return new Meta($id, $load);
    }



    /**
     * Returns the id for this meta-object
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
     * @param array|null $data
     * @return Meta
     */
    public static function init( ?string $comments = null, ?array $data = null): Meta
    {
        $meta = new Meta();
        $meta->action('created', $comments, $data);

        return $meta;
    }



    /**
     * Creates a new meta entry and returns the database id for it
     *
     * @param string $action
     * @param string|null $comments
     * @param array|null $data
     * @return void
     */
    public function action(string $action, ?string $comments = null, ?array $data = null): void
    {
        // Insert the action in the meta_history table
        sql()->query('INSERT INTO `meta_history` (`meta_id`, `created_by`, `action`, `source`, `comments`, `data`) 
                            VALUES                     (:meta_id , :created_by , :action , :source , :comments , :data )', [
            ':meta_id'    => $this->id,
            ':created_by' => Session::getUser()->getId(),
            ':source'     => (string) (PLATFORM_HTTP ? UrlBuilder::getCurrent() : Script::getCurrent()),
            ':action'     => $action,
            ':comments'   => $comments,
            ':data'       => Json::encode($data)
        ]);
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
        $this->id = $id;
        $this->history = sql()->list('SELECT * FROM `meta_history` WHERE `meta_id` = :meta_id', [
            ':meta_id' => $id
        ]);
    }
}