<?php

namespace Phoundation\Audio;

use Phoundation\Filesystem\File;
use Phoundation\Processes\Process;


/**
 * Class Audio
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Audio
 */
class Audio extends File
{
    /**
     * Play this audio file
     *
     * @return static
     */
    public function play(): static
    {
        try {
            Process::new('aplay')
                ->addArgument($this->file)
                ->executeNoReturn();
        } catch (Throwable $e) {
showdie($e);
        }

        return $this;
    }
}