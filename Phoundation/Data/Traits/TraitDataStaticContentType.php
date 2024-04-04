<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataStaticContentType
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataStaticContentType
{
    /**
     * Content-type that was requested
     *
     * @var string|null $content_type
     */
    protected static ?string $content_type = null;


    /**
     * Returns the requested mimetype / content type
     *
     * @return string|null
     */
    public static function getContentType(): ?string
    {
        return static::$content_type;
    }


    /**
     * Sets the mimetype / content type
     *
     * @param string $content_type
     *
     * @return void
     */
    public static function setContentType(string $content_type): void
    {
        // Validate status code
        // TODO implement

        static::$content_type = $content_type;
    }
}
