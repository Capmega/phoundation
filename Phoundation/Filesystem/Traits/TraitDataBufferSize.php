<?php

/**
 * Trait TraitDataBufferSize
 *
 * This trait contains methods to track file data buffer size
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Utils\Config;

trait TraitDataBufferSize
{
    /**
     * The buffer size to use
     *
     * @var int|null $buffer_size
     */
    protected ?int $buffer_size = null;


    /**
     * Returns the configured or detected file buffer size
     *
     * @param int|null $buffer_size
     *
     * @return int
     */
    public function getBufferSize(?int $buffer_size = null): int
    {
        // Buffer is by default 4K
        $buffer_size = $buffer_size ?? Config::get('filesystem.buffer.size', 4096);
        if ($buffer_size < 1024) {
            // Minimal buffer size is 1K
            $buffer_size = 1024;
        }
        $this->buffer_size = $buffer_size;

        return $this->buffer_size;
    }
}
