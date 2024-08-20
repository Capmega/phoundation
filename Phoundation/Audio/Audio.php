<?php

/**
 * Class Audio
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Audio
 */


declare(strict_types=1);

namespace Phoundation\Audio;

use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Os\Processes\Commands\Mpg123;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Phoundation\Utils\Config;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Stringable;


class Audio extends FsFile
{
    public function __construct(Stringable|string|null $source = null, bool|FsRestrictionsInterface|null $restrictions = null, bool|Stringable|string|null $absolute_prefix = false)
    {
        if (!$source instanceof FsPathInterface) {
            $restrictions = $restrictions ?? FsRestrictions::getReadonly(DIRECTORY_DATA . 'audio');
        }

        parent::__construct($source, $restrictions, $absolute_prefix);
    }


    /**
     * Play this audio file on the local computer
     *
     * @param bool $background
     *
     * @return static
     */
    public function playLocal(bool $background): static
    {
        if (Config::getBoolean('audio.local.enabled', true)) {
            if (!defined('NOAUDIO') or !NOAUDIO) {
                try {
                    Mpg123::new(new FsDirectory(DIRECTORY_DATA . 'audio', FsRestrictions::getData()))
                          ->setFile($this->makeAbsolute(DIRECTORY_DATA . 'audio'))
                          ->play($background);

                } catch (FileNotExistException | ProcessesException $e) {
                    if ((defined('NOWARNINGS') and NOWARNINGS) or !Config::getBoolean('debug.exceptions.warnings', true)) {
                        Log::error(tr('Failed to play the requested audio file because of the following exception'));
                        Log::error($e);

                    } else {
                        Log::warning(tr('Failed to play the requested audio file because of the following exception'));
                        Log::warning($e->getMessage());
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Play this audio file on the remote client
     *
     * @note This method will attach the specified audio file to the body of the web page
     * @note This method is only available on HTML web pages
     *
     * @param string|null $class
     *
     * @return static
     */
    public function playRemote(?string $class = null): static
    {
        if (Config::getBoolean('audio.remote.enabled', true)) {
            switch (Request::getRequestType()) {
                case EnumRequestTypes::html:
                    // no break
                case EnumRequestTypes::admin:
                    Response::addToFooter(\Phoundation\Web\Html\Components\Audio::new()
                                                                                ->addClasses($class)
                                                                                ->setFile($this)
                                                                                ->render());
                    break;
                default:
                    // Ignore this request
            }
        }

        return $this;
    }
}
