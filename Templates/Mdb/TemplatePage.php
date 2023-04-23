<?php

namespace Templates\Mdb;

use Phoundation\Core\Config;
use Phoundation\Core\Session;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Footer;
use Phoundation\Web\Http\Html\Components\ProfileImage;
use Phoundation\Web\Http\Html\Components\TopPanel;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;

/**
 * Mdb template class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class TemplatePage extends \Phoundation\Web\Http\Html\Template\TemplatePage
{
    /**
     * Execute, builds and returns the page output according to the template.
     *
     * Either use the default execution steps from parent::execute($target), or write your own execution steps here.
     * Once the output has been generated it should be returned.
     *
     * @param string $target
     * @return string|null
     */
    public function execute(string $target): ?string
    {
        // Set the Page breadcrumbs
        Page::setBreadCrumbs(new BreadCrumbs());

        return parent::execute($target);
    }


    /**
     * Build the HTTP headers for the page
     *
     * @param string $output
     * @return void
     *
     */
    public function buildHttpHeaders(string $output): void
    {
        Page::setContentType('text/html');
        Page::setDoctype('html');
    }


    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    public function buildHtmlHeader(): ?string
    {
        // Set head meta data
        Page::setViewport('width=device-width, initial-scale=1');

        // Load basic MDB and fonts CSS
        Page::loadCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
        Page::loadCss('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');
        Page::loadCss('css/mdb');
        Page::loadCss('css/mdb-fix');

        // Load basic MDB amd jQuery javascript libraries
        Page::loadJavascript('js/mdb,js/jquery/jquery');

        // Set basic page details
        Page::setPageTitle(tr('Phoundation platform'));
        Page::setFavIcon('img/favicons/project.png');

        return Page::buildHeaders();
    }


    /**
     * Build the page header
     *
     * @return string|null
     */
    public function buildPageHeader(): ?string
    {
        return '<body class="mdb-skin-custom" data-mdb-spy="scroll" data-mdb-target="#scrollspy" data-mdb-offset="250">   
                    <header>
                        ' . $this->buildTopPanel() . '
                    </header>
                    <main class="pt-5 mdb-docs-layout">
                        <div class="container mt-5  mt-5  px-lg-5">
                            <div class="tab-content">';
    }


    /**
     * Build the page footer
     *
     * @return string|null
     */
    public function buildPageFooter(): ?string
    {
        return '            </div>
                                </div>
                            </main>' .
                            Footer::new()->render();
    }


    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function buildHtmlFooter(): ?string
    {
        if (Page::getBuildBody()) {
            return          Page::buildFooters() . '
                        </body>
                    </html>';
        }

        return      Page::buildFooters() . '
                </html>';
    }


    /**
     * Build the HTML menu
     *
     * @return string|null
     */
    public function buildMenu(): ?string
    {
        return null;
    }


    /**
     * Build the HTML body
     *
     * @param string $target
     * @return string|null
     */
    public function buildBody(string $target): ?string
    {
        return parent::buildBody($target);
    }


    /**
     * @return string|null
     */
    public function buildProfileImage(): ?string
    {
        // TODO: Implement buildProfileImage() method.
    }


    /**
     * Builds and returns a navigation bar
     *
     * @return string|null
     */
    protected function buildTopPanel(): ?string
    {
        $image = ProfileImage::new()
            ->setImage(Session::getUser()->getPicture())
            ->setMenu(Page::getMenus()->get('profile_image'))
            ->setUrl(null);

        // Set up the navigation bar
        $navigation_bar = TopPanel::new();
        $navigation_bar
            ->setMenu(Page::getMenus()->getPrimaryMenu())
            ->setProfileImage($image)
            ->getModals()
                ->get('sign-in')
                    ->getForm()
                        ->setId('form-signin')
                        ->setMethod('post')
                        ->setAction(UrlBuilder::getAjax(Config::get('web.pages.signin', '/system/sign-in.html')));

        return $navigation_bar->render();
    }
}