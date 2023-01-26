<?php

namespace Phoundation\Web\Http\Html;

use Minify_HTML;
use Phoundation\Core\Log\Log;


/**
 * Class Minifier
 *
 * This class continas required functionalities to minify Html, Css and Javascript
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Minifier
{
    /**
     * Return the specified HTML minified
     *
     * @param string $html
     * @return string
     */
    public static function html(string $html): string
    {
// TODO Remove the lines below, autoload should fix this for us
//        include_once(PATH_ROOT.'libs/vendor/mrclay/minify/lib/Minify/HTML.php');
//        include_once(PATH_ROOT.'libs/vendor/mrclay/minify/lib/Minify/CSS.php');
//        include_once(PATH_ROOT.'libs/vendor/mrclay/jsmin-php/src/JSMin/JSMin.php');
//        include_once(PATH_ROOT.'libs/vendor/mrclay/minify/lib/Minify/CSS/Compressor.php');
//        include_once(PATH_ROOT.'libs/vendor/mrclay/minify/lib/Minify/CommentPreserver.php');

        $html = Minify_HTML::minify($html, [
            'cssMinifier' => ['Minify_CSS'  , 'minify'],
            'jsMinifier'  => ['\JSMin\JSMin', 'minify']
        ]);

// :FIX: This is a temp fix because the minifier appears to use \n as a space?
        $html = str_replace("\n", ' ', $html);

        return $html;
    }



    /**
     * Minify the specified CSS file
     *
     * @param string $file
     * @return string The filename of the minified file
     */
    public static function css(string $file): string
    {
        Log::warning('The Minifier::css() function is not yet implemented!');
        return $file;
    }



    /**
     * Minify the specified Javascript file
     *
     * @param string $file
     * @return string The filename of the minified file
     */
    public static function js(string $file): string
    {
        Log::warning('The Minifier::js() function is not yet implemented!');
        return $file;
    }
}