<?php

/**
 * Class Badge
 *
 * This HTML widget component object can render the HTML required to display a progress bar
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use Phoundation\Data\Traits\TraitDataBackgroundColor;
use Phoundation\Data\Traits\TraitDataCurrentFloat;
use Phoundation\Data\Traits\TraitDataLabel;
use Phoundation\Data\Traits\TraitDataMaximumInteger;
use Phoundation\Data\Traits\TraitDataMinimumInteger;
use Phoundation\Web\Html\Components\Widgets\Interfaces\ProgressBarInterface;


class Badge extends Widget
{
    use TraitDataBackgroundColor;
    use TraitDataLabel;


    /**
     * ProgressBar class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setBackgroundColor('primary');
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        return  '<span class="badge badge-' . $this->background_color . '">' . $this->label . '</span>';
    }
}
