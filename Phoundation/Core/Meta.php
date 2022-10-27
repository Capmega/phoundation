<?php

namespace Phoundation\Core;



/**
 * Meta class
 *
 * This class keeps track of meta data for database entries throughout phoundation projects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Init
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
     */
    public function __construct(?int $id = null)
    {
        if ($id) {
            $this->load($id);
        }
    }



    /**
     * Creates a new meta entry and returns the database id for it
     *
     * @return int
     */
    public static function init(): int
    {
        $meta = new Meta();
        $meta->addAction('created');
        return $meta->id;
    }



    /**
     * Adds the specified action to the meta history
     *
     * @param string $action
     * @param array|null $data
     * @return void
     */
    public function addAction(string $action, ?array $data = null): void
    {
        // Insert the action in the meta_history table
        sql()->insert('meta_history', [
            'meta_id'    => $this->id,
            'created_by' => Session::currentUser()->getId(),
            'action'     => $action,
            'data'       => $data
        ]);
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
        $this->history = sql()->list('SELECT * FROM `meta_history` WHERE `meta_id` = :meta_id', [':meta_id' => $id]);
    }
}