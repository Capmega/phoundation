<?php

namespace Templates\AdminLte;

use Phoundation\Web\WebPage;
use Plugins\AdminLte\Components\Footer;



/**
 * AdminLte template class
 *
 * This class is an example template for your website
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
        WebPage::loadCss('adminlte/plugins/fontawesome-free/css/all"');
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
        WebPage::setTitle(tr('Phoundation platform'));
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
        $html = '<body class="sidebar-mini" style="height: auto;">
                    <div class="wrapper">
                        ' . $this->components->buildTopBar($this->navigation_menu) . '
                        ' . $this->components->buildSideBar($this->side_menu) . '
                        <div class="content-wrapper" style="min-height: 1518.06px;">';

        return $html;
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
     * @return string|null
     */
    public function buildBody(): ?string
    {
        // TODO: Implement buildBody() method.
    }



    /**
     * @return string|null
     */
    public function buildProfileImage(): ?string
    {
        // TODO: Implement buildProfileImage() method.
    }
}