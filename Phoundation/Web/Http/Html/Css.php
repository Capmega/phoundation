<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;



/**
 * Class Css
 *
 * This class contains various CSS processing methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Css
{
    /**
     * Loads the specified CSS file(s) into the page payload
     *
     * @param string|array $files
     * @param string|null $media
     * @return void
     */
    public static function loadFiles(string|array $files, ?string $media = null): void
    {
        $min = Config::get('web.minify', true);

        foreach (Arrays::force($files) as $file) {
            $core->register['css'][$file] = [
                'min'   => $min,
                'media' => $media
            ];
        }
    }



    /**
     * Generate <script> elements for inclusion at the end of <head> and <body> tags
     *
     * This function will go over the CSS files registered in the $core->register[css] table and generate
     * <link rel="stylesheet" type="text/css" href="..."> elements for each of them. The HTML will be returned
     *
     * This function typically should never have to be called by developers as it is a sub function of html_headers()
     *
     * @see Css::loadFiles()
     * @see Js::generateHtml()
     * @see http_headers()
     * @version 1.26.0: Added documentation
     * @example
     * code
     * $result = html_generate_css();
     * /code
     *
     * @return string The HTML containing <link> tags that is to be included in the <head> tag
     */
    public static function generateHtml(): string
    {
        if (!empty($_CONFIG['cdn']['css']['post'])) {
            $core->register['css']['post'] = array('min'   => $_CONFIG['cdn']['min'],
                'media' => (is_string($_CONFIG['cdn']['css']['post']) ? $_CONFIG['cdn']['css']['post'] : ''));
        }

        $return = '';
        $min    = $_CONFIG['cdn']['min'];

        html_bundler('css');

        foreach ($core->register['css'] as $file => $meta) {
            if (!$file) continue;

            if (!str_contains(substr($file, 0, 8), '//')) {
                $file = cdn_domain((($_CONFIG['whitelabels'] === true) ? $_SESSION['domain'].'/' : '').'css/'.($min ? Strings::until($file, '.min').'.min.css' : $file.'.css'));
            }

            $html = '<link rel="stylesheet" type="text/css" href="'.$file.'">';

            if (substr($file, 0, 2) == 'ie') {
                $html = html_iefilter($html, Strings::until(Strings::from($file, 'ie'), '.'));
            }

            /*
             * Hurray, normal stylesheets!
             */
            $return .= $html."\n";
        }

        if ($_CONFIG['cdn']['css']['load_delayed']) {
            $core->register['footer'] .= $return;
            return null;
        }

        return $return;
    }

}