<?php

namespace Phoundation\Filesystem\Requirements;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Filesystem\Requirements\Interfaces\RequirementsInterface;
use Stringable;


/**
 * Class Requirements
 *
 *
 * @note On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */
class Requirements extends DataList implements RequirementsInterface
{
    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'filesystem_requirements';
    }


    /**
     * @inheritDoc
     */
    public static function getEntryClass(): string
    {
        return Requirement::class;
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueColumn(): ?string
    {
        return 'name';
    }


    /**
     * Checks if there are requirements defined for the specified path, and if the path obeys those requirements
     *
     * @param Stringable|string $path
     * @return void
     */
    public static function check(Stringable|string $path): void
    {

    }
}