<?php

declare(strict_types=1);

namespace Phoundation\Audio;

use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Processes\Exception\ProcessesException;
use Phoundation\Processes\Process;
use Throwable;

/**
 * Class Audio
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Audio
 */
class Audio extends File
{
    /**
     * Play this audio file
     *
     * @param bool $background
     * @return static
     */
    public function play(bool $background = false): static
    {
        try {
            $this->file = Filesystem::absolute($this->file, PATH_ROOT . 'data/audio');
            $process    = Process::new('mplayer')->addArgument($this->file);

            if ($background) {
                $process->executeBackground();
            } else {
                $process->executeNoReturn();
            }

        } catch (FileNotExistException|ProcessesException $e) {
            Log::warning(tr('Failed to play the requested audio file because of the following exception'));
            Log::warning($e->getMessage());
        }

        return $this;
    }
}