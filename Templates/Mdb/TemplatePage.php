<?php

namespace Templates\Mdb;

use Phoundation\Core\Config;
use Phoundation\Core\Session;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;
use Templates\Mdb\Components\BreadCrumbs;
use Templates\Mdb\Components\Footer;
use Templates\Mdb\Components\ProfileImage;
use Templates\Mdb\Components\TopPanel;



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
        // Set the WebPage breadcrumbs
        WebPage::setBreadCrumbs(new BreadCrumbs());

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
        WebPage::setContentType('text/html');
        WebPage::setDoctype('html');
    }



    /**
     * Build the HTML header for the page
     *
     * @return string|null
     */
    public function buildHtmlHeader(): ?string
    {
        // Load basic MDB and fonts CSS
        WebPage::loadCss('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
        WebPage::loadCss('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');
        WebPage::loadCss('css/mdb');
        WebPage::loadCss('css/mdb-fix');

        // Load basic MDB amd jQuery javascript libraries
        WebPage::loadJavascript('js/mdb,js/jquery/jquery');

        // Set basic page details
        WebPage::setPageTitle(tr('Phoundation platform'));
        WebPage::setFavIcon('favicon/phoundation.ico');

        return WebPage::buildHeaders();
    }



    /**
     * Build the page header
     *
     * @return string|null
     */
    public function buildPageHeader(): ?string
    {
        $html = '<body class="mdb-skin-custom " data-mdb-spy="scroll" data-mdb-target="#scrollspy" data-mdb-offset="250">
                    <header>
                    ' . $this->buildTopPanel($this->secondary_menu) . '
                    </header>
                    <main class="pt-5 mdb-docs-layout">
                        <div class="container mt-5  mt-5  px-lg-5">
                            <div class="tab-content">';

        return $html;
    }



    /**
     * Build the page footer
     *
     * @return string|null
     */
    public function buildPageFooter(): ?string
    {
        $html = '           </div>
                        </div>
                    </main>' .
                    Footer::new()->render();

        return $html;
    }



    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function buildHtmlFooter(): ?string
    {
        $html  = WebPage::buildFooters() . '
        </body>
        </html>';

        return $html;
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
            ->setMenu(TemplateMenus::getProfileImageMenu())
            ->setUrl(null);

        // Set up the navigation bar
        $navigation_bar = TopPanel::new();
        $navigation_bar
            ->setMenu($this->primary_menu)
            ->setProfileImage($image)
            ->getModals()
                ->get('sign-in')
                    ->getForm()
                        ->setId('form-signin')
                        ->setMethod('post')
                        ->setAction(Url::build(Config::get('web.pages.signin', '/system/sign-in.html'))->ajax());

        return $navigation_bar->render();
    }
}