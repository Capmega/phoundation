<?php

/**
 * Class Audio
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Audio
 */


declare(strict_types=1);

namespace Phoundation\Content\Media\Audio;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataLogLevel;
use Phoundation\Data\Traits\TraitDataSignal;
use Phoundation\Data\Traits\TraitDataTimeout;
use Phoundation\Developer\Project\Project;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Enums\EnumSignal;
use Phoundation\Os\Processes\Commands\Mpg123;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Stringable;


class Audio extends PhoFile
{
    use TraitDataTimeout;
    use TraitDataSignal;
    use TraitDataLogLevel;


    /**
     * Audio class constructor
     *
     * @param Stringable|string|null             $source
     * @param bool|PhoRestrictionsInterface|null $restrictions
     * @param bool|Stringable|string|null        $absolute_prefix
     */
    public function __construct(Stringable|string|null $source = null, bool|PhoRestrictionsInterface|null $restrictions = null, bool|Stringable|string|null $absolute_prefix = false)
    {
        if (!$source instanceof PhoPathInterface) {
            $source = PhoFile::new($source, $restrictions ?? PhoRestrictions::newReadonlyObject(DIRECTORY_CDN . LANGUAGE . '/' . Project::getSeoFullName() . '/audio'), DIRECTORY_CDN . LANGUAGE . '/' . Project::getSeoFullName() . '/audio');
        }

        parent::__construct($source, $restrictions, $absolute_prefix);

        $this->setLogLevel(3)
             ->setSignal(EnumSignal::SIGKILL)
             ->setTimeout(config()->getInteger('media.audio.timeout', 10));
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
        if (config()->getBoolean('audio.local.enabled', true)) {
            if (!defined('NOAUDIO') or !NOAUDIO) {
                $directory = new PhoDirectory(DIRECTORY_CDN . LANGUAGE . '/' . Project::getSeoFullName() . '/audio', PhoRestrictions::newDataObject());

                if (!$directory->exists()) {
                    // No language / project specific audio directory found, fall back to english / phoundation
                    $directory = new PhoDirectory(DIRECTORY_CDN . 'en/' . Project::getSeoFullName() . '/audio', PhoRestrictions::newDataObject());
                }

                try {
                    Mpg123::new($directory)
                          ->setLogLevel($this->log_level)
                          ->setTimeout($this->timeout)
                          ->setSignal($this->signal)
                          ->setFileObject($this->makeAbsolute(DIRECTORY_DATA . 'audio'))
                          ->play($background);

                } catch (FileNotExistException | ProcessesException $e) {
                    if ((defined('NOWARNINGS') and NOWARNINGS) or !config()->getBoolean('debug.exceptions.warnings', true)) {
                        Log::error(ts('Failed to play the requested audio file because of the following exception'));
                        Log::error($e);

                    } else {
                        Log::warning(ts('Failed to play the requested audio file because of the following exception'));
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
        if (config()->getBoolean('audio.remote.enabled', true)) {
            switch (Request::getRequestType()) {
                case EnumRequestTypes::html:
                    Response::addHtmlToPageFooters(\Phoundation\Web\Html\Components\Audio::new()
                                                                                         ->addClasses($class)
                                                                                         ->setFileObject($this)
                                                                                         ->render());
                    break;

                default:
                    // Ignore this request
            }
        }

        return $this;
    }
}
