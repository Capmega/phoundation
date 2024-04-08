<?php

namespace Phoundation\Web\Html\Traits;

use Phoundation\Core\Sessions\Config;
use Phoundation\Web\Html\Enums\EnumBootstrapColor;
use Phoundation\Web\Html\Enums\Interfaces\EnumBootstrapColorInterface;

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
trait TraitBootstrapColor
{
    /**
     * @var EnumBootstrapColorInterface|EnumBootstrapColor $foreground_color
     */
    protected EnumBootstrapColorInterface $foreground_color;

    /**
     * @var EnumBootstrapColorInterface|EnumBootstrapColor $background_color
     */
    protected EnumBootstrapColorInterface $background_color;


    /**
     * Returns the foreground color
     *
     * @return EnumBootstrapColorInterface
     */
    public function getBootstrapForegroundColor(): EnumBootstrapColorInterface
    {
        if (empty($this->foreground_color)) {
            $this->foreground_color = EnumBootstrapColor::from(Config::get('web.bootstrap.theme.colors.foreground', EnumBootstrapColor::light->value));
        }

        return $this->foreground_color;
    }


    /**
     * Returns the background color
     *
     * @param EnumBootstrapColorInterface $color
     *
     * @return static
     */
    public function setBootstrapForegroundColor(EnumBootstrapColorInterface $color): static
    {
        // Configurable theming allows colors to be remapped from configuration
        $this->foreground_color = EnumBootstrapColor::from(Config::get('web.bootstrap.theme.colors.' . $color->value, $color->value));

        return $this;
    }


    /**
     * Returns the foreground color
     *
     * @return EnumBootstrapColorInterface
     */
    public function getBootstrapBackgroundColor(): EnumBootstrapColorInterface
    {
        if (empty($this->background_color)) {
            $this->background_color = EnumBootstrapColor::from(Config::get('web.bootstrap.theme.colors.background', EnumBootstrapColor::dark->value));
        }

        return $this->background_color;
    }


    /**
     * Returns the background color
     *
     * @param EnumBootstrapColorInterface $color
     *
     * @return static
     */
    public function setBootstrapBackgroundColor(EnumBootstrapColorInterface $color): static
    {
        // Configurable theming allows colors to be remapped from configuration
        $this->background_color = EnumBootstrapColor::from(Config::get('web.bootstrap.theme.colors.' . $color->value, $color->value));

        return $this;
    }
}