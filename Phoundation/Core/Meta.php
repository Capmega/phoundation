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
     * Returns a new Meta object
     *
     * @param int|null $id
     * @return Meta
     */
    public static function create(?int $id = null): Meta
    {
        return new Meta($id);
    }



    /**
     * Load data for the specified meta id
     *
     * @param int $id
     * @return void
     */
    protected function load(int $id): void
    {

    }
}