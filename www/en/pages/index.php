<?php

use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;


// Build the page
$card = Card::new()
    ->setTitle(tr('This is a test!'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam leo nisl, iaculis nec est quis, dapibus commodo mi. Nunc dui metus, ultricies ac vestibulum sit amet, rutrum tristique est. Aenean et consectetur sem. Mauris non scelerisque urna, in efficitur nibh. Nulla facilisi. Ut tempor ligula fringilla nibh commodo, sed scelerisque erat posuere. Aenean lobortis volutpat sem, eu tincidunt neque hendrerit non. Nunc maximus ante et arcu maximus maximus. Ut vitae leo et arcu condimentum pellentesque sed et diam. Mauris ut sapien porttitor, pharetra erat quis, suscipit leo. Vestibulum a libero vitae quam tempor aliquam. Proin ultrices nisl in ante aliquam, at posuere arcu luctus. Nulla iaculis porttitor sem eu dignissim.');


Page::loadJavascript('adminlte/plugins/jquery/jquery');
Page::loadJavascript('adminlte/plugins/jquery-ui/jquery-ui');

Script::new()
    ->setContent('$.widget.bridge("uibutton", $.ui.button)')
    ->setEventWrapper(null)
    ->render();

Page::loadJavascript('adminlte/plugins/bootstrap/js/bootstrap.bundle');
Page::loadJavascript('adminlte/plugins/chart.js/Chart');
Page::loadJavascript('adminlte/plugins/sparklines/sparkline');
Page::loadJavascript('adminlte/plugins/jqvmap/jquery.vmap');
Page::loadJavascript('adminlte/plugins/jqvmap/maps/jquery.vmap.usa');
Page::loadJavascript('adminlte/plugins/jquery-knob/jquery.knob');
Page::loadJavascript('adminlte/plugins/moment/moment');
Page::loadJavascript('adminlte/plugins/daterangepicker/daterangepicker');
Page::loadJavascript('adminlte/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4');
Page::loadJavascript('adminlte/plugins/summernote/summernote-bs4');
Page::loadJavascript('adminlte/plugins/overlayScrollbars/js/jquery.overlayScrollbars');
Page::loadJavascript('adminlte/js/adminlte');
Page::loadJavascript('adminlte/js/pages/dashboard');


// Set page meta data
Page::setPageTitle(tr('Dashboard (under development)'));
Page::setHeaderTitle(tr('Dashboard'));
Page::setHeaderSubTitle(tr('(under development)'));
Page::setDescription(tr(''));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Dashboard')
]));
