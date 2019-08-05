<?php
require_once(__DIR__.'/libs/startup.php');

load_libs('base');

html_load_css('style,ie,ie6', 'print');

$html = html_flash().'<br>'.tr('works').'<br>'.tr("also works");

$params = array('title'       => 'Welcome to base');
$meta   = array('description' => 'base',
                'keywords'    => 'base');

echo html_header($params, $meta),
     $html.
     html_footer();
?>
