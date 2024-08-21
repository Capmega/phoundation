<?php

/**
 * Trait TraitBootstrapColor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Templates\AdminLte
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Core\Sessions\SessionConfig;
use Phoundation\Web\Html\Enums\EnumBootstrapColor;


trait TraitBootstrapColor
{
    /**
     * @var EnumBootstrapColor $foreground_color
     */
    protected EnumBootstrapColor $foreground_color;

    /**
     * @var EnumBootstrapColor $background_color
     */
    protected EnumBootstrapColor $background_color;


    /**
     * Returns the foreground color
     *
     * @return EnumBootstrapColor
     */
    public function getBootstrapForegroundColor(): EnumBootstrapColor
    {
        if (empty($this->foreground_color)) {
            $this->foreground_color = EnumBootstrapColor::from(SessionConfig::get('web.bootstrap.theme.colors.foreground', EnumBootstrapColor::light->value));
        }

        return $this->foreground_color;
    }


    /**
     * Returns the background color
     *
     * @param EnumBootstrapColor $color
     *
     * @return static
     */
    public function setBootstrapForegroundColor(EnumBootstrapColor $color): static
    {
        // Configurable theming allows colors to be remapped from configuration
        $this->foreground_color = EnumBootstrapColor::from(SessionConfig::get('web.bootstrap.theme.colors.' . $color->value, $color->value));

        return $this;
    }


    /**
     * Returns the foreground color
     *
     * @return EnumBootstrapColor
     */
    public function getBootstrapBackgroundColor(): EnumBootstrapColor
    {
        if (empty($this->background_color)) {
            $this->background_color = EnumBootstrapColor::from(SessionConfig::get('web.bootstrap.theme.colors.background', EnumBootstrapColor::dark->value));
        }

        return $this->background_color;
    }


    /**
     * Returns the background color
     *
     * @param EnumBootstrapColor $color
     *
     * @return static
     */
    public function setBootstrapBackgroundColor(EnumBootstrapColor $color): static
    {
        // Configurable theming allows colors to be remapped from configuration
        $this->background_color = EnumBootstrapColor::from(SessionConfig::get('web.bootstrap.theme.colors.' . $color->value, $color->value));

        return $this;
    }
}
