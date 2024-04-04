<?php

declare(strict_types=1);

namespace Phoundation\Web\Html;

use Phoundation\Utils\Arrays;


/**
 * Class Css
 *
 * This class contains various CSS processing methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class Css
{
    /**
     * Register of files that will be sent to the client
     *
     * @var array
     */
    protected static array $files = [];


    /**
     * Loads the specified CSS file(s) into the page payload
     *
     * @param string|array $files
     * @param string|null  $media
     *
     * @return void
     */
    public static function loadFiles(string|array $files, ?string $media = null): void
    {
        foreach (Arrays::force($files) as $file) {
            static::$files[$file] = $media;
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
     * @return string|null The HTML containing <link> tags that is to be included in the <head> tag
     * @see     Js::generateHtml()
     * @see     http_headers()
     * @version 1.26.0: Added documentation
     * @example
     *          code
     *          $result = html_generate_css();
     *          /code
     *
     * @see     Css::loadFiles()
     */
    public static function generateHtml(): ?string
    {
//        if (!empty($_CONFIG['cdn']['css']['post'])) {
//            static::$files['post'] = [
//                'min'   => $_CONFIG['cdn']['min'],
//                'media' => (is_string($_CONFIG['cdn']['css']['post']) ? $_CONFIG['cdn']['css']['post'] : '')
//            ];
//        }
//
//        $return = '';
//        $min    = $_CONFIG['cdn']['min'];
//
//        Bundler::new()->css(static::$files);
//
//        foreach (static::$files as $file => $meta) {
//            if (!$file) continue;
//
//            if (!str_contains(substr($file, 0, 8), '//')) {
//                $file = Cdn::domain((($_CONFIG['whitelabels'] === true) ? $_SESSION['domain'].'/' : '').'css/'.($min ? Strings::until($file, '.min').'.min.css' : $file.'.css'));
//            }
//
//            $this->render = '<link rel="stylesheet" type="text/css" href="'.$file.'">';
//
//            if (str_starts_with($file, 'ie')) {
//                $html = html_iefilter($html, Strings::until(Strings::from($file, 'ie'), '.'));
//            }
//
//            // Hurray, normal stylesheets!
//            $return .= $html."\n";
//        }
//
//        if (Config::get('cdn')) {
//            Html::addToFooter($return);
//        }
//
//        return $return;
    }


    /**
     * Purge all CSS rules from the specified CSS file that are not used in the specified HTML file
     *
     * @param string $html_file
     * @param string $css_file
     *
     * @return string
     */
    public static function purge(string $html_file, string $css_file): string
    {
//        //$purged_css      = 'p-'.$css;
//        //$purged_css_file = DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/css/'.$purged_css.($_CONFIG['cdn']['min'] ? '.min.css' : '.css');
//        //$css_file        = DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/css/'.$css       .($_CONFIG['cdn']['min'] ? '.min.css' : '.css');
//        //
//        //safe_exec(array('commands' => array('cd' , array(DIRECTORY_ROOT.'libs/vendor/purge-css/src/'),
//        //                                    'php', array(DIRECTORY_ROOT.'libs/vendor/purge-css/src/purge.php', 'purge:run', $css_file, $html, $purged_css_file))));
//        //return $purged_css;
//
//        $purged_css      = 'p-' . $css_file;
//        $purged_css_file = DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/css/'.$purged_css.($_CONFIG['cdn']['min'] ? '.min.css' : '.css');
//        $css_file        = DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/css/'.$css       .($_CONFIG['cdn']['min'] ? '.min.css' : '.css');
//        $arguments       = array('--css', $css_file, '--content', $html, '--out', DIRECTORY_TMP);
//
//        // Ensure that any previous version is deleted
//        File::new($purged_css_file)->delete();
//        File::new(DIRECTORY_WEB . LANGUAGE . '/pub/css')->delete();
//
//        // Add list of selectors that should be whitelisted
//        if (!empty($_CONFIG['css']['whitelist'][$core->register['script']])) {
//            // Use the whitelist specifically for this page
//            $whitelist = &$_CONFIG['css']['whitelist'][$core->register['script']];
//
//        } else {
//            // Use the default whitelist
//            $whitelist = &$_CONFIG['css']['whitelist']['default'];
//        }
//
//        if ($whitelist) {
//            $arguments[] = '--whitelist';
//
//            foreach (Arrays::force($whitelist) as $selector) {
//                if ($selector) {
//                    $arguments[] = $selector;
//                }
//            }
//        }
//
//        unset($whitelist);
//
//        // Purge CSS
//        load_libs('node');
//        node_exec('./purgecss', $arguments);
//        rename(DIRECTORY_TMP.basename($css_file), $purged_css_file);
//
//        return $purged_css;
    }
}
