<?php

namespace Templates\AdminLte;

use Phoundation\Core\Config;
use Phoundation\Web\Http\Html\Components\Footer;
use Phoundation\Web\Http\Html\Components\SidePanel;
use Phoundation\Web\Http\Html\Components\TopPanel;
use Phoundation\Web\Http\Html\Modals\SignInModal;
use Phoundation\Web\Http\Url;
use Phoundation\Web\WebPage;



/**
 * AdminLte template class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
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
        WebPage::loadCss('https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback');
        WebPage::loadCss('adminlte/plugins/fontawesome-free/css/all');
        WebPage::loadCss('adminlte/css/adminlte');
        WebPage::loadCss('adminlte/plugins/overlayScrollbars/css/OverlayScrollbars');

        // Load basic MDB amd jQuery javascript libraries
        WebPage::loadJavascript([
            'adminlte/plugins/jquery/jquery',
            'adminlte/plugins/jquery-ui/jquery-ui',
            'adminlte/plugins/bootstrap/js/bootstrap.bundle',
            'adminlte/plugins/overlayScrollbars/js/jquery.overlayScrollbars',
            'adminlte/js/adminlte'
        ]);

        // Set basic page details
        WebPage::setPageTitle(tr('Phoundation platform'));
        WebPage::setFavIcon('favicon/phoundation.png');

        return WebPage::buildHeaders();
    }



    /**
     * Build the page header
     *
     * @return string|null
     */
    public function buildPageHeader(): ?string
    {
        return '<body class="sidebar-mini" style="height: auto;">
                    <div class="wrapper">
                        ' . $this->buildTopPanel() . '
                        ' . $this->buildSidePanel();
    }



    /**
     * Build the page footer
     *
     * @return string|null
     */
    public function buildPageFooter(): ?string
    {
        $html =     Footer::new()->render() . '
                </div>';

        return $this->render;
    }



    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function buildHtmlFooter(): ?string
    {
        $html  =          WebPage::buildFooters() . '
                      </body>
                  </html>';

        return $this->render;
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
        $body = parent::buildBody($target);
        $body = '   <div class="content-wrapper" style="min-height: 1518.06px;">                   
                       ' . $this->buildBodyHeader() . '
                        <section class="content">
                            <div class="container-fluid">
                                <div class="row">
                                    <div class="col-md-12">
                                        ' . $body . '
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>';

        return $body;
    }



    /**
     * @return string|null
     */
    public function buildProfileImage(): ?string
    {
        // TODO: Implement buildProfileImage() method.
    }



    /**
     * Builds and returns the top panel HTML
     *
     * @return string
     */
    protected function buildTopPanel(): string
    {
        return TopPanel::new()->render();
    }



    /**
     * Builds and returns the sidebar HTML
     *
     * @return string
     */
    protected function buildSidePanel(): string
    {
        $sign_in = new SignInModal();
        $sign_in->getForm()
            ->setId('form-signin')
            ->setMethod('post')
            ->setAction(Url::build(Config::get('web.pages.signin', '/system/sign-in.html'))->ajax());

        $panel = SidePanel::new();
        $panel->setMenu($this->primary_menu);
        $panel->getModals()
            ->add('sign-in', $sign_in);

        return $panel->render();
    }



    /**
     * Builds the body header
     *
     * @return string
     */
    protected function buildBodyHeader(): string
    {
        $sub_title = WebPage::getHeaderSubTitle();

        $this->render = '   <section class="content-header">
                      <div class="container-fluid">
                        <div class="row mb-2">
                          <div class="col-sm-6">
                            <h1>
                              ' . WebPage::getHeaderTitle() . '
                              ' . ($sub_title ? '<small>' . $sub_title . '</small>' : '') . '                          
                            </h1>
                          </div>
                          <div class="col-sm-6">
                            ' . WebPage::getBreadCrumbs()?->render() .  '
                          </div>
                        </div>
                      </div>
                    </section>';

        return $this->render;
    }
}