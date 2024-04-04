<?php

declare(strict_types=1);

namespace Phoundation\Web\Html;

use Phoundation\Notifications\Notification;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Exception\WebException;
use Phoundation\Web\Html\Enums\EnumDisplayMode;


/**
 * Class Js
 *
 * This class contains various CSS processing methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class Js
{
    /**
     * Add specified javascript files to the $core->register[js_header] or $core->register[js_footer] tables
     *
     * This function will add the specified list of javascript files to the $core register "js_header" and / or
     * "js_footer" sections. These files will later be added as <script> tags in the <head> and <body> tags. For each
     * file it is possible to specify independantly if it has to be loaded in the <head> tag (prefix it with "<") or
     * "body" tag (prefix it with ">"). If the file has no prefix, the default will be used, configured in
     * $_CONFIG[cdn][js][load_delayed]
     *
     * When the page is generated, html_headers() will call html_generate_js() for both the required <script> tags
     * inside the <head> and <body> tags
     *
     * @param string|array $files The javascript files that should be loaded by the client for this page
     * @param string       $list  What javascript file list it should be added to. Typical valid options are "" and
     *                            "page". The "" list will be loaded before the "page" list
     *
     * @return void
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category  Function reference
     * @package   html
     * @see       html_generate_js()
     * @see       html_load_css()
     * @see       html_headers()
     * @version   1.26.0: Added documentation
     * @example
     *            code
     *            html_load_js();
     *            /code
     *
     */
    public static function loadFiles(string|array $files, $list = 'page'): void
    {
        if (!isset($core->register['js_header'])) {
            throw new WebException(tr('Cannot load javascript file(s) ":files", the files list have already been sent to the client by html_header()', [
                ':files' => $files,
            ]));
        }

        $config = &$_CONFIG['cdn']['js'];

        foreach (Arrays::force($files) as $file) {
            if (str_contains($file, '://')) {
                // Compatibility code: ALL LOCAL JS FILES SHOULD ALWAYS BE SPECIFIED WITHOUT .js OR .min.js!!
                if (str_ends_with($file, '.js')) {
                    $file = substr($file, 0, -3);

                    Notification::new()
                                ->setUrl('developer/incidents.html')
                                ->setMode(EnumDisplayMode::exception)
                                ->setCode('not-exists')
                                ->setRoles('developer')
                                ->setTitle(tr('html_load_js() issue detected'))
                                ->setMessage(tr('File ":file" was specified with ".js"', [':file' => $file]))
                                ->send();

                } elseif (str_ends_with($file, '.min.js')) {
                    $file = substr($file, 0, -7);

                    Notification::new()
                                ->setMode(EnumDisplayMode::exception)
                                ->setUrl('developer/incidents.html')
                                ->setCode('not-exists')
                                ->setRoles('developer')
                                ->setTitle(tr('html_load_js() issue detected'))
                                ->setMessage(tr('File ":file" was specified with ".min.js', [':file' => $file]))
                                ->send();
                }
            }

            // Determine if this file should be delayed loaded or not
            switch (substr($file, 0, 1)) {
                case '<':
                    $file    = substr($file, 1);
                    $delayed = false;
                    break;

                case '>':
                    $file    = substr($file, 1);
                    $delayed = true;
                    break;

                default:
                    $delayed = $config['load_delayed'];
            }

            // Determine if this file should be async or not
            $async = match (substr($file, -1, 1)) {
                '&'     => true,
                default => false,
            };

            // Register the file to be loaded
            if ($delayed) {
                $core->register['js_footer' . ($list ? '_' . $list : '')][$file] = $async;

            } else {
                $core->register['js_header' . ($list ? '_' . $list : '')][$file] = $async;
            }
        }

        unset($config);
    }


    /**
     * Generate <script> elements for inclusion at the end of <head> and <body> tags
     *
     * This function will go over the javascript files registered in the $core->register[js_headers] and
     * $core->register[js_headers] tables and generate <script> elements for each of them. The javascript files in the
     * js_headers table will be returned while the javascript files in the js_footer table will be aded to the
     * $core->register[footer] string
     *
     * This function typically should never have to be called by developers as it is a sub function of html_headers()
     *
     * @return string The HTML containing <script> tags that is to be included in the <head> tag
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category  Function reference
     * @package   html
     * @see       html_load_js()
     * @see       html_generate_css()
     * @see       html_headers()
     * @version   1.26.0: Added documentation
     * @example
     *            code
     *            $result = html_generate_js();
     *            /code
     *
     */
    public static function generateHtml($lists = null): string
    {
//        // Shortcut to JS configuration
//        $count  = 0;
//        $js     = &$_CONFIG['cdn']['js'];
//        $min    = ($_CONFIG['cdn']['min'] ? '.min' : '');
//        $return = '';
//        $footer = '';
//        $lists  = array('js_header', 'js_header_page', 'js_footer', 'js_footer_page', 'js_footer_scripts');
//
//        // Merge all body file lists into one
//        foreach ($lists as $key => $section) {
//            switch ($section) {
//                case 'js_header':
//                    // no-break
//                case 'js_footer':
//                    continue 2;
//
//                default:
//                    $main = Strings::untilReverse($section, '_');
//
//                    /*
//                     * If the sub list is empty then ignore it and continue
//                     */
//                    if (empty($core->register[$section])) {
//                        unset($lists[$key]);
//                        continue 2;
//                    }
//
//                    /*
//                     * Merge the sublist in the main list
//                     */
//                    $core->register[$main] = array_merge($core->register[$main], $core->register[$section]);
//                    unset($lists[$key]);
//                    unset($core->register[$section]);
//            }
//        }
//
//        /*
//         * Loop over header and body javascript file lists to generate the HTML
//         * that will load javascript files to client
//         */
//        foreach ($lists as $section) {
//            /*
//             * Bundle all files for this list into one?
//             */
//            html_bundler($section);
//
//            /*
//             * Generate HTML that will load javascript files to client
//             */
//            foreach ($core->register[$section] as $file => $async) {
//                if (!$file) {
//                    /*
//                     * We should never have empty files
//                     */
//                    notify(array('code'    => 'empty',
//                        'groups'  => 'developer',
//                        'title'   => tr('Empty file specified'),
//                        'message' => tr('html_generate_js(): Found empty string file specified in html_load_js()')));
//                    continue;
//                }
//
//                if (strstr($file, '://')) {
//                    /*
//                     * These are external scripts, hosted by somebody else
//                     */
//                    $this->render = '<script id="script-'.$count++.'" '.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.$file.'"'.($async ? ' async' : '').'></script>';
//
//                } else {
//                    /*
//                     * These are local scripts, hosted by us
//                     */
//                    $this->render = '<script id="script-'.$count++.'" '.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.cdn_domain((($_CONFIG['whitelabels'] === true) ? $_SESSION['domain'].'/' : '').'js/'.($min ? $file.$min : Strings::until($file, '.min').$min).'.js').'"'.($async ? ' async' : '').'></script>';
//                }
//
//                if ($section === 'js_header') {
//                    /*
//                     * Add this script in the header
//                     */
//                    $return .= $html;
//
//                } else {
//                    /*
//                     * Add this script in the footer of the body tag
//                     */
//                    $footer .= $html;
//                }
//            }
//
//            $core->register[$section] = array();
//        }
//
//        /*
//         * If we have footer data, add it to the footer register, which will
//         * automatically be added to the end of the <body> tag
//         */
//        if (!empty($footer)) {
//            $core->register['footer'] .= $footer.$core->register['footer'] . Core::readRegister('system', 'script_delayed');
//            unset($core->register['script_delayed']);
//        }
//
//        unset($core->register['js_header']);
//        unset($core->register['js_footer']);
//
//        return $return;
    }
}
