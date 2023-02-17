<?php

namespace Templates\AdminLte;

use Phoundation\Core\Config;
use Phoundation\Web\Http\Html\Components\Footer;
use Phoundation\Web\Http\Html\Components\SidePanel;
use Phoundation\Web\Http\Html\Components\TopPanel;
use Phoundation\Web\Http\Html\Modals\SignInModal;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


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
        Page::setFavIcon('img/favicons/project.png');
        Page::setViewport('width=device-width, initial-scale=1');

        // Load basic MDB and fonts CSS
        Page::loadCss([
            'https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback',
            'adminlte/plugins/fontawesome-free/css/all',
            'adminlte/css/adminlte',
            'adminlte/css/phoundation',
            'adminlte/plugins/overlayScrollbars/css/OverlayScrollbars'
        ], true);

        // Load basic MDB amd jQuery javascript libraries
        Page::loadJavascript([
            'adminlte/plugins/jquery/jquery',
            'adminlte/plugins/jquery-ui/jquery-ui',
            'adminlte/plugins/bootstrap/js/bootstrap.bundle',
            'adminlte/plugins/overlayScrollbars/js/jquery.overlayScrollbars',
            'adminlte/js/adminlte'
        ], null, true);

        // Set basic page details
        Page::setPageTitle(tr('Phoundation platform'));
        Page::setFavIcon('favicon/' . Page::getProjectName() . '/project.png');

        return Page::buildHeaders();
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
                        ' . Page::getFlashMessages()->render() . '
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
        return      Footer::new()->render() . '
                </div>';
    }



    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function buildHtmlFooter(): ?string
    {
        if (Page::getBuildBody()) {
            return        Page::buildFooters() . '
                      </body>
                  </html>';
        }

        return     Page::buildFooters() . '
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
        $body = parent::buildBody($target);

        if (Page::getBuildBody()) {
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
        }

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
     * @return string|null
     */
    protected function buildTopPanel(): ?string
    {
        $panel = TopPanel::new();

        $panel->getNotificationsDropDown()
            ->setNotifications(null)
            ->setNotificationsUrl('/notifications/all.html');

        $panel->getMessagesDropDown()
            ->setMessages(null)
            ->setMessagesUrl('/messages/all.html');

        return $panel->render();
    }



    /**
     * Builds and returns the sidebar HTML
     *
     * @return string|null
     */
    protected function buildSidePanel(): ?string
    {
        $sign_in = new SignInModal();
        $sign_in
            ->useForm(true)
            ->getForm()
                ->setId('form-signin')->setMethod('post')->setAction(UrlBuilder::getAjax(Config::get('web.pages.signin', '/system/sign-in.html')));

        $panel = SidePanel::new();
        $panel->setMenu(Page::getMenus()->getPrimaryMenu());
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
        $sub_title = Page::getHeaderSubTitle();

        $html = '   <section class="content-header">
                      <div class="container-fluid">
                        <div class="row mb-2">
                          <div class="col-sm-6">
                            <h1>
                              ' . Page::getHeaderTitle() . '
                              ' . ($sub_title ? '<small>' . $sub_title . '</small>' : '') . '                          
                            </h1>
                          </div>
                          <div class="col-sm-6">
                            ' . Page::getBreadCrumbs()?->render() .  '
                          </div>
                        </div>
                      </div>
                    </section>';

        return $html;
    }
}