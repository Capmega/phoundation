<?php

/**
 * Class Logo
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Core\Core;
use Phoundation\Utils\Config;
use Phoundation\Web\Http\Url;


class Logo extends Img
{
    /**
     * Logo class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct(null);

        $this->src = Url::getCdn('img/logos/' . Core::getProjectSeoName() . '/large.webp');

        $this->getAnchor()->setHref(Url::getWww('index'));
    }


    /**
     * Return the alt text for this logo, default to "Logo for PROJECTNAME"
     *
     * @return string|null
     */
    public function getAlt(): ?string
    {
        return parent::getAlt() ?? tr('Logo for :project', [
            ':project' => Config::getString('project.name', 'Phoundation'),
        ]);
    }
}
