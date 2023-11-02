<?php

declare(strict_types=1);

namespace Phoundation\Audio;

use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumRequestTypes;
use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Mplayer;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Phoundation\Web\Page;


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
     * Play this audio file on the local computer
     *
     * @param bool $background
     * @return static
     */
    public function playLocal(bool $background): static
    {
        if (!defined('NOAUDIO') or !NOAUDIO) {
            try {
                Mplayer::new(Restrictions::new(DIRECTORY_DATA . 'audio', true))
                    ->setFile(Filesystem::absolute($this->file, DIRECTORY_DATA . 'audio'))
                    ->play($background);

            } catch (FileNotExistException|ProcessesException $e) {
                Log::warning(tr('Failed to play the requested audio file because of the following exception'));
                Log::warning($e->getMessage());
            }
        }

        return $this;
    }


    /**
     * Play this audio file on the remote client
     *
     * @note This method will attach the specified audio file to the body of the web page
     * @note This method is only available on HTML web pages
     * @param string|null $class
     * @return static
     */
    public function playRemote(?string $class = null): static
    {
        switch (Core::getRequestType()) {
            case EnumRequestTypes::html:
                // no break
            case EnumRequestTypes::admin:
                Page::addToFooter('html', \Phoundation\Web\Http\Html\Components\Audio::new()
                    ->addClass($class)
                    ->setFile($this->file)
                    ->render());
                break;

            default:
                // Ignore this request
        }

        return $this;
    }
}
