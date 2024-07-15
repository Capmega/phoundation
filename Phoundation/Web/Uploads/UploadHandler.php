<?php

/**
 * Class UploadHandler
 *
 * This request subclass handles upload functionalities
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Uploads;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Uploads\Interfaces\UploadHandlerInterface;

class UploadHandler implements UploadHandlerInterface
{
    /**
     * Contains a list of all HTML element id's that can receive drag/drop file uploads as keys and callback methods as
     * the handlers
     *
     * @var IteratorInterface
     */
    protected IteratorInterface $handlers;

    /**
     * The maximum amount of files that may be uploaded
     *
     * @var int $max_files
     */
    protected int $max_files = 1;


    /**
     * Returns a list of all upload handlers
     *
     * @return IteratorInterface
     */
    public function getHandlersObject(): IteratorInterface
    {
        return $this->handlers;
    }


    /**
     * Returns the maximum number of files that will be allowed to be uploaded
     *
     * @return int
     */
    public function getMaxFiles(): int
    {
        return $this->max_files;
    }


    /**
     * Sets the maximum number of files that will be allowed to be uploaded
     *
     * @param int $max_files
     * @return static
     */
    public function setMaxFiles(int $max_files): static
    {
        if ($max_files < 1) {
            throw new OutOfBoundsException(tr('The max_files parameter cannot be lower than 1'));
        }

        $this->max_files = $max_files;

        return $this;
    }
}