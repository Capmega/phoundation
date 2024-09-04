<?php

/**
 * Class Requirements
 *
 *
 * @note      On Ubuntu requires packages nfs-utils cifs-utils psmisc
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Requirements;

use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Filesystem\Requirements\Interfaces\RequirementsInterface;
use Stringable;


class Requirements extends DataIterator implements RequirementsInterface
{
    /**
     * @inheritDoc
     */
    public static function getTable(): ?string
    {
        return 'filesystem_requirements';
    }


    /**
     * @inheritDoc
     */
    public static function getDefaultContentDataType(): ?string
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
     *
     * @return void
     */
    public function check(Stringable|string $path): void
    {
        $this->load(only_if_empty: true);
        foreach ($this->source as $restriction) {
            $restriction->check($path);
        }
    }
}
