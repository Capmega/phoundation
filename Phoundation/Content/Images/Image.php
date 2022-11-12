<?php

namespace Phoundation\Content\Images;

use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Command;
use Phoundation\Servers\Server;



/**
 * Class Image
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Content
 */
class Image extends Command
{
    /**
     * The name of the image file
     *
     * @var string|null
     */
    protected ?string $file = null;



    /**
     * Image class constructor
     */
    public function __construct(Server|string|null $server = null)
    {
        parent::__construct($server);
    }



    /**
     * Returns a new Image object
     *
     * @param Server|string|null $server
     * @return static
     */
    public static function new(Server|string|null $server = null): static
    {
        return new Image($server);
    }



    /**
     * Returns the file for this image object
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }



    /**
     * Sets the file for this image object
     *
     * @param string|null $file
     * @return static
     */
    public function setFile(?string $file): static
    {
        $this->file = $file;
        return $this;
    }



    /**
     * Returns a Convert class to convert the specified image
     *
     * @return Convert
     */
    public function convert(): Convert
    {
        return new Convert($this);
    }
}