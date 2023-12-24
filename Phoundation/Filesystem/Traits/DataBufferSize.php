<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Utils\Config;


/**
 * Trait DataBufferSize
 *
 * This trait contains methods to track file data buffer size
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
trait DataBufferSize
{
    /**
     * The buffer size to use
     *
     * @var int|null $buffer_size
     */
    protected ?int $buffer_size;


    /**
     * Returns the configured file buffer size
     *
     * @param int|null $requested_buffer_size
     * @return int
     */
    public function getBufferSize(?int $requested_buffer_size = null): int
    {
        $required  = $requested_buffer_size ?? Config::get('filesystem.buffer.size', $this->buffer_size ?? 4096);
        $available = Core::getMemoryAvailable();

        if ($required > $available) {
            // The required file buffer is larger than the available memory, oops...
            if (Config::get('filesystem.buffer.auto', false)) {
                throw new FilesystemException(tr('Failed to set file buffer of ":required", only ":available" memory available', [
                    ':required'  => $required,
                    ':available' => $available
                ]));
            }

            // Auto adjusts to half of the available memory
            Log::warning(tr('File buffer of ":required" requested but only ":available" memory available. Created buffer of ":size" instead', [
                ':required'  => $required,
                ':available' => $available,
                ':size'      => floor($available * .5)
            ]));

            $required = floor($available * .5);
        }

        $this->buffer_size = $required;

        return $required;
    }


    /**
     * Sets the configured file buffer size
     *
     * @param int|null $buffer_size
     * @return static
     */
    public function setBufferSize(?int $buffer_size): static
    {
        $this->buffer_size = $buffer_size;
        return $this;
    }
}
