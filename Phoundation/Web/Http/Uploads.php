<?php

namespace Phoundation\Web\Http;

use Iterator;



/**
 * Class Uploads
 *
 * This class represents the $_FILES array in PHP with all required functionalities added. Each uploaded file will be
 * available as an Upload class object
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Uploads implements Iterator
{
    /**
     * The $_FILES array data
     *
     * @var array $files
     */
    protected array $files;



    /**
     * Uploads class constructor
     */
    public function __construct()
    {
        global $_FILES;

        // Move $_FILES data internally
        $this->files = $_FILES;
        $_FILES      = [];
    }



    /**
     * @return Upload
     */
    public function current(): Upload
    {
        // TODO: Implement current() method.
    }

    public function next(): void
    {
        // TODO: Implement next() method.
    }

    public function key(): Upload
    {
        // TODO: Implement key() method.
    }

    public function valid(): bool
    {
        // TODO: Implement valid() method.
    }

    public function rewind(): void
    {
        // TODO: Implement rewind() method.
    }
}