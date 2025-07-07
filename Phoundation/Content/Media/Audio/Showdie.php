<?php

/**
 * Class Showdie
 *
 * Audio type class that will always play data/audio/showdie.mp3
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Audio
 */


declare(strict_types=1);

namespace Phoundation\Content\Media\Audio;

use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Stringable;

class Showdie extends Audio
{
    /**
     * Showdie class constructor
     *
     * @param Stringable|string|null             $source
     * @param bool|PhoRestrictionsInterface|null $restrictions
     * @param bool|Stringable|string|null        $absolute_prefix
     */
    public function __construct(Stringable|string|null $source = 'showdie.mp3', bool|PhoRestrictionsInterface|null $restrictions = null, bool|Stringable|string|null $absolute_prefix = false)
    {
        parent::__construct('showdie.mp3', $restrictions, $absolute_prefix);
        $this->setTimeout(5);
    }


    /**
     * Returns a new class object with the specified restrictions
     *
     * @param Stringable|string                  $source
     * @param PhoRestrictionsInterface|bool|null $restrictions
     * @param Stringable|string|bool|null        $absolute_prefix
     *
     * @return static
     */
    public static function new(Stringable|string $source = 'showdie.mp3', PhoRestrictionsInterface|bool|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false): static
    {
        return new static('showdie.mp3', $restrictions, $absolute_prefix);
    }
}
