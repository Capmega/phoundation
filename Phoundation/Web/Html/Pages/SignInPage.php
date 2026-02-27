<?php

/**
 * Class SignInPage
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Pages;

use Phoundation\Developer\Project\Project;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Enums\EnumAnchorRenderRightsFail;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Response;


class SignInPage extends Page
{
    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // This page must build its own body
        // Set page meta-data
        Response::setRenderMainWrapper(false);
        Response::setPageTitle(tr('Please sign in'));

        $this->setSection(Anchor::new(Project::getOwnerUrl())
                                ->setClass('h1')
                                ->setContent(Img::new('logos/large.jpg')
                                                ->setAlt(tr(':owner logo', [':owner' => Project::getOwnerName()])), false)
                                ->setRenderRightsFail(EnumAnchorRenderRightsFail::full), 'card-header')
             ->setSection(Anchor::new(Url::new('lost-password')->makeWww()->addRedirect($this->getGet('redirect'))->addQuery($this->getGet('email'), 'email'))
                                ->setContent(tr('text-center'))
                                ->setContent(tr('I forgot my password'))
                                ->setRenderRightsFail(EnumAnchorRenderRightsFail::full), 'lost-password');

        $this->setEnabled(true, 'email')
             ->setEnabled(true, 'copyright')
             ->setEnabled(true, 'lost-password');

        $this->setUrl(Url::new('backgrounds/signin.jpg')->makeImg(), 'image-background');

        return parent::render();
    }
}
