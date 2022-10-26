<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Core\Arrays;



/**
 * Class Html
 *
 * This class contains various HTML processing methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
Class Html {
    /**
     * Keeps track of the tab index
     *
     * @var int $tabindex
     */
    protected static int $tabindex = 0;



    /**
     * Returns the current tab index and automatically increments it
     *
     * @return int
     */
    public static function getTabIndex(): int
    {
        return self::$tabindex++;
    }



    /**
     * Returns a new HtmlSelect object to generate an <select> element
     *
     * @return Select
     */
    public static function select(): Select
    {
        return new Select();
    }



    /**
     * Returns a new HtmlImg object to generate an <img> element
     *
     * @return Img
     */
    public static function img(): Img
    {
        return new Img();
    }



    /**
     * Wrapper for htmlentities()
     *
     * @see htmlentities()
     * @param string $html
     * @return string
     */
    function safe(string $html): string
    {
        return htmlentities($html);
    }



    function iefilter($html, $filter) {
        if (!$filter) {
            return $html;
        }

        if ($mod = Strings::until(Strings::from($filter, '.'), '.')) {
            return "\n<!--[if ".$mod.' IE '.Strings::fromReverse($filter, '.')."]>\n\t".$html."\n<![endif]-->\n";

        } elseif ($filter == 'ie') {
            return "\n<!--[if IE ]>\n\t".$html."\n<![endif]-->\n";
        }

        return "\n<!--[if IE ".Strings::from($filter, 'ie')."]>\n\t".$html."\n<![endif]-->\n";
    }



    /*
     * Bundles CSS or JS files together into one larger file with an md5 name
     *
     * This function will bundle the CSS and JS files required for the current page into one large file and have that one sent to the browser instead of all the individual files. This will improve transfer speeds to the client.
     *
     * The bundler file name will be a sha1() of the list of required files plus the current framework and project versions. This way, if two pages have two different lists of files, they will have two different bundle files. Also, as each deply causes at least a new project version, each deploy will also cause new bundle file names which simplifies caching for the client; we can simply set caching to a month or longer and never worry about it anymore.
     *
     * The bundler files themselves will also be cached (by default one day, see $_CONFIG[cdn][bundler][max_age]) in pub/css/bundler-* for CSS files and pub/js/bundler-* for javascript files. The cache script can clean these files when executed with the "clean" method
     *
     * This function is called automatically by the html_generate_css() and html_generate_js() calls and should not be used by the developer.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_generate_css()
     * @see html_generate_js()
     * @see html_minify()
     * @version 1.27.0: Added documentation
     * @version 2.6.16: Added CSS purge support
     * @version 2.6.30: Fixed CSS purge temp files not being deleted
     *
     * @param string $list One of "css", "js_header", or "js_footer".  Specified what file list to bundle.  "css" bundles all CSS files, "js_header" bundles all files for the <script> tag in the <head> section, and "js_footer" bundles all files that go in the <script> tag of the footer of the HTML file
     * @return boolean False if no bundling has been applied, true if bundling was applied
     */
    function bundler($list) {

        if (!$_CONFIG['cdn']['bundler']) {
            /*
             * Bundler has been disabled
             */
            return false;
        }

        if ($list === 'css') {
            $extension = 'css';

        } else {
            $extension = 'js';
        }

        /*
         * Prepare bundle information. The bundle file name will be a hash of
         * the bundle file names and the framework and project code versions.
         * This way, if the framework version or code version get bumped up,
         * the bundle filename will be different, avoiding caching issues. Since
         * the deploy script will automatically bump the project version on
         * deploy, each deploy will cause different bundle filenames. With this
         * we can easily set caching to a year if needed, any updates to CSS or
         * JS will cause the client browser to load the new bundle files.
         */
        $admin_path  = (Core::getCallType('admin') ? 'admin/'           : '');
        $ext         = ($_CONFIG['cdn']['min']   ? '.min.'.$extension : '.'.$extension);
        $bundle      =  Strings::force(array_keys($core->register[$list]));
        $bundle      =  substr(sha1($bundle.Core::FRAMEWORKCODEVERSION.PROJECTCODEVERSION), 1, 16);
        $path        =  ROOT.'www/'.LANGUAGE.'/'.$admin_path.'pub/'.$extension.'/';
        $bundle_file =  $path.'bundle-'.$bundle.$ext;
        $file_count  =  0;

        /*
         * If we don't find an existing bundle file, then procced with the
         * concatination process
         */
        if (file_exists($bundle_file)) {
            /*
             * Ensure file is not 0 bytes. This might be caused due to a number
             * of issues, but mainly due to disk full events. When this happens,
             * the 0 bytes bundle files remain, leaving the site without CSS or
             * JS
             */
            if (!filesize($bundle_file)) {
                log_file(tr('Deleting empty bundle file ":file"', array(':file' => $bundle_file)), 'html-bundler', 'yellow');

                File::executeMode(dirname($bundle_file), 0770, function() use ($bundle_file, $list) {
                    file_delete($bundle_file, ROOT.'www/'.LANGUAGE.'/pub/');
                });

                return html_bundler($list);
            }

            /*
             * Bundle files are essentially cached files. Ensure the cache is
             * not too old
             */
            if (($_CONFIG['cdn']['cache_max_age'] > 60) and (filemtime($bundle_file) + $_CONFIG['cdn']['cache_max_age']) < time()) {
                log_file(tr('Deleting expired cached bundle file ":file"', array(':file' => $bundle_file)), 'html-bundler', 'VERBOSE/yellow');

                File::executeMode(dirname($bundle_file), 0770, function() use ($bundle_file, $list) {
                    file_delete($bundle_file, ROOT.'www/'.LANGUAGE.'/pub/');
                });

                return html_bundler($list);
            }

            $core->register[$list] = array('bundle-'.$bundle => false);

        } else {
            /*
             * Generate new bundle file. This requires the pub/$list path to be
             * writable
             */
            File::executeMode(dirname($bundle_file), 0770, function() use ($list, &$file_count, $path, $ext, $extension, $bundle_file) {
                global $core, $_CONFIG;

                if (!empty($core->register[$list])) {
                    foreach ($core->register[$list] as $file => $data) {
                        /*
                         * Check for @imports
                         */
                        $orgfile = $file;
                        $file    = $path.$file.$ext;

                        log_file(tr('Adding file ":file" to bundle file ":bundle"', array(':file' => $file, ':bundle' => $bundle_file)), 'bundler', 'VERYVERBOSE/cyan');

                        if (!file_exists($file)) {
                            notify(array('code'    => 'not-exists',
                                'groups'  => 'developers',
                                'title'   => tr('Bundler file does not exist'),
                                'message' => tr('html_bundler(): The requested ":extension" type file ":file" should be bundled but does not exist', array(':extension' => $extension, ':file' => $file))));
                            continue;
                        }

                        $file_count++;

                        $data = file_get_contents($file);
                        unset($core->register[$list][$orgfile]);

                        if ($extension === 'css') {
// :TODO: ADD SUPPORT FOR RECURSIVE @IMPORT STATEMENTS!! What if the files that are imported with @import contain @import statements themselves!?!?!?
                            if (preg_match_all('/@import.+?;/', $data, $matches)) {
                                foreach ($matches[0] as $match) {
                                    /*
                                     * Inline replace each @import with the file
                                     * contents
                                     */
// :CLEANUP:
//                                if (preg_match('/@import\s?(?:url\()?((?:"?.+?"?)|(?:\'.+?\'))\)?/', $match)) {
                                    if (preg_match('/@import\s"|\'.+?"|\'/', $match)) {
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                                        $import = Strings::cut($match, '"', '"');

                                        if (!file_exists($path.$import)) {
                                            notify(array('code'    => 'not-exists',
                                                'groups'  => 'developers',
                                                'title'   => tr('Bundler file does not exist'),
                                                'message' => tr('html_bundler(): The bundler ":extension" file ":import" @imported by file ":file" does not exist', array(':extension' => $extension, ':import' => $import, ':file' => $file))));

                                            $import = '';

                                        } else {
                                            $import = file_get_contents($path.$import);
                                        }

                                    } elseif (preg_match('/@import\surl\(.+?\)/', $match)) {
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                                        /*
                                         * This is an external URL. Get it locally
                                         * as a temp file, then include
                                         */
                                        $import = Strings::cut($match, '(', ')');
                                        $import = Strings::slash(dirname($file)).Strings::unslash($import);

                                        if (!file_exists($import)) {
                                            notify(array('code'    => 'not-exists',
                                                'groups'  => 'developers',
                                                'title'   => tr('Bundler file does not exist'),
                                                'message' => tr('html_bundler(): The bundler ":extension" file ":import" @imported by file ":file" does not exist', array(':extension' => $extension, ':import' => $import, ':file' => $file))));

                                            $import = '';

                                        } else {
                                            $import = file_get_contents($import);
                                        }
                                    }

                                    $data = str_replace($match, $import, $data);
                                }
                            }

                            $count = substr_count($orgfile, '/');

                            if ($count) {
                                /*
                                 * URL rewriting required, this file is not in
                                 * /css or /js, and not in a sub dir
                                 */
                                if (preg_match_all('/url\((.+?)\)/', $data, $matches)) {
                                    /*
                                     * Rewrite all URL's to avoid relative URL's
                                     * failing for files in sub directories
                                     *
                                     * e.g.:
                                     *
                                     * The bundle file is /pub/css/bundle-1.css,
                                     * includes a css file /pub/css/foo/bar.css,
                                     * bar.css includes an image 1.jpg that is
                                     * in the same directory as bar.css with
                                     * url("1.jpg")
                                     *
                                     * In the bundled file, this should become
                                     * url("foo/1.jpg")
                                     */
                                    foreach ($matches[1] as $url) {
                                        if (strtolower(substr($url, 0, 5)) == 'data:') {
                                            /*
                                             * This is inline data, nothing we can do so
                                             * ignore
                                             */
                                            continue;
                                        }

                                        if (substr($url, 0, 1) == '/') {
                                            /*
                                             * Absolute URL, we can ignore these
                                             * since they already point towards
                                             * the correct path
                                             */
                                        }

                                        if (preg_match('/https?:\/\//', $url)) {
                                            /*
                                             * Absolute domain, ignore because
                                             * we cannot fix anything here
                                             */
                                            continue;
                                        }

                                        $data = str_replace($url, '"'.str_repeat('../', $count).$url.'"', $data);
                                    }
                                }
                            }
                        }

                        if (Debug::enabled()) {
                            file_append($bundle_file, "\n/* *** BUNDLER FILE \"".$orgfile."\" *** */\n".$data.($_CONFIG['cdn']['min'] ? '' : "\n"));

                        } else {
                            file_append($bundle_file, $data.($_CONFIG['cdn']['min'] ? '' : "\n"));
                        }
                    }

                    if ($file_count) {
                        chmod($bundle_file, $_CONFIG['file']['file_mode']);
                    }
                }
            });

            /*
             * Only continue here if we actually added anything to the bundle
             * (some bundles may not have anything, like js_header)
             */
            if ($file_count) {
                $bundle = 'bundle-'.$bundle;

                /*
                 * Purge the file from duplicate content
                 */
                if ($list === 'css') {
                    if ($_CONFIG['cdn']['css']['purge']) {
                        try {
                            load_libs('css');

                            $html   = file_temp($core->register['html'], 'html');
                            $bundle = css_purge($html, $bundle);

                            log_file(tr('Purged not-used CSS rules from bundled file ":file"', array(':file' => $bundle)), 'bundler', 'green');
                            file_delete($html);

                        }catch(Exception $e) {
                            /*
                             * The CSS purge failed
                             */
                            file_delete($html);
                            notify($e->makeWarning(true));
                        }
                    }
                }

// :TODO: Add support for individual bundles that require async loading
                $core->register[$list][$bundle] = false;

                if ($_CONFIG['cdn']['enabled']) {
                    load_libs('cdn');
                    cdn_add_files($bundle_file);
                }
            }
        }

        return true;
    }



    /*
     * Add specified CSS files to the $core->register[css] table
     *
     * This function will add the specified list of CSS files to the $core register "css" section. These files will later be added as <link> tags in the <head> and <body> tags
     *
     * When the page is generated, html_headers() will call html_generate_css() to get the required <link> tags
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_generate_css()
     * @see html_load_js()
     * @see html_headers()
     * @version 1.26.0: Added documentation
     * @example
     * code
     * html_load_css('style,custom');
     * /code
     *
     * @param list $files The CSS files that should be loaded by the client for this page
     * @return void
     */
    function load_css($files = '', $media = null) {
        if (!$files) {
            $files = array();
        }

        if (!is_array($files)) {
            if (!is_string($files)) {
                throw new HtmlException('html_load_css(): Invalid files specification');
            }

            $files = explode(',', $files);
        }

        $min = $_CONFIG['cdn']['min'];

        foreach ($files as $file) {
            $core->register['css'][$file] = array('min'   => $min,
                'media' => $media);
        }
    }



    /*
     * Generate <script> elements for inclusion at the end of <head> and <body> tags
     *
     * This function will go over the CSS files registered in the $core->register[css] table and generate <link rel="stylesheet" type="text/css" href="..."> elements for each of them. The HTML will be returned
     *
     * This function typically should never have to be called by developers as it is a sub function of html_headers()
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_load_css()
     * @see html_generate_js()
     * @see http_headers()
     * @version 1.26.0: Added documentation
     * @example
     * code
     * $result = html_generate_css();
     * /code
     *
     * @return string The HTML containing <link> tags that is to be included in the <head> tag
     */
    function generate_css() {
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



    /*
     * Add specified javascript files to the $core->register[js_header] or $core->register[js_footer] tables
     *
     * This function will add the specified list of javascript files to the $core register "js_header" and / or "js_footer" sections. These files will later be added as <script> tags in the <head> and <body> tags. For each file it is possible to specify independantly if it has to be loaded in the <head> tag (prefix it with "<") or "body" tag (prefix it with ">"). If the file has no prefix, the default will be used, configured in $_CONFIG[cdn][js][load_delayed]
     *
     * When the page is generated, html_headers() will call html_generate_js() for both the required <script> tags inside the <head> and <body> tags
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_generate_js()
     * @see html_load_css()
     * @see html_headers()
     * @version 1.26.0: Added documentation
     * @example
     * code
     * html_load_js();
     * /code
     *
     * @param list $files The javascript files that should be loaded by the client for this page
     * @param string $list What javascript file list it should be added to. Typical valid options are "" and "page". The "" list will be loaded before the "page" list
     * @return void
     */
    function load_js($files, $list = 'page') {
        if (!isset($core->register['js_header'])) {
            throw new HtmlException(tr('html_load_js(): Cannot load javascript file(s) ":files", the files list have already been sent to the client by html_header()', array(':files' => $files)), 'invalid');
        }

        $config = &$_CONFIG['cdn']['js'];

        foreach (Arrays::force($files) as $file) {
            if (strstr($file, '://')) {
                /*
                 * Compatibility code: ALL LOCAL JS FILES SHOULD ALWAYS BE
                 * SPECIFIED WITHOUT .js OR .min.js!!
                 */
                if (substr($file, -3, 3) == '.js') {
                    $file = substr($file, 0, -3);

                    notify(array('code'    => 'not-exists',
                        'groups'  => 'developers',
                        'title'   => tr('html_load_js() issue detected'),
                        'message' => tr('html_load_js(): File ":file" was specified with ".js"', array(':file' => $file))));

                } elseif (substr($file, -7, 7) == '.min.js') {
                    $file = substr($file, 0, -7);

                    notify(array('code'    => 'not-exists',
                        'groups'  => 'developers',
                        'title'   => tr('html_load_js() issue detected'),
                        'message' => tr('html_load_js(): File ":file" was specified with ".min.js"', array(':file' => $file))));
                }
            }

            /*
             * Determine if this file should be delayed loaded or not
             */
            switch (substr($file, 0, 1)) {
                case '<':
                    $file    = substr($file, 1);
                    $delayed =  false;
                    break;

                case '>':
                    $file    = substr($file, 1);
                    $delayed =  true;
                    break;

                default:
                    $delayed = $config['load_delayed'];
            }

            /*
             * Determine if this file should be async or not
             */
            switch (substr($file, -1, 1)) {
                case '&':
                    $async = true;
                    break;

                default:
                    $async = false;
            }

            /*
             * Register the file to be loaded
             */
            if ($delayed) {
                $core->register['js_footer'.($list ? '_'.$list : '')][$file] = $async;

            } else {
                $core->register['js_header'.($list ? '_'.$list : '')][$file] = $async;
            }
        }

        unset($config);
    }



    /*
     * Generate <script> elements for inclusion at the end of <head> and <body> tags
     *
     * This function will go over the javascript files registered in the $core->register[js_headers] and $core->register[js_headers] tables and generate <script> elements for each of them. The javascript files in the js_headers table will be returned while the javascript files in the js_footer table will be aded to the $core->register[footer] string
     *
     * This function typically should never have to be called by developers as it is a sub function of html_headers()
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_load_js()
     * @see html_generate_css()
     * @see html_headers()
     * @version 1.26.0: Added documentation
     * @example
     * code
     * $result = html_generate_js();
     * /code
     *
     * @return string The HTML containing <script> tags that is to be included in the <head> tag
     */
    function generate_js($lists = null) {
        /*
         * Shortcut to JS configuration
         */
        $count  = 0;
        $js     = &$_CONFIG['cdn']['js'];
        $min    = ($_CONFIG['cdn']['min'] ? '.min' : '');
        $return = '';
        $footer = '';
        $lists  = array('js_header', 'js_header_page', 'js_footer', 'js_footer_page', 'js_footer_scripts');

        /*
         * Merge all body file lists into one
         */
        foreach ($lists as $key => $section) {
            switch ($section) {
                case 'js_header':
                    // no-break
                case 'js_footer':
                    continue 2;

                default:
                    $main = Strings::untilReverse($section, '_');

                    /*
                     * If the sub list is empty then ignore it and continue
                     */
                    if (empty($core->register[$section])) {
                        unset($lists[$key]);
                        continue 2;
                    }

                    /*
                     * Merge the sublist in the main list
                     */
                    $core->register[$main] = array_merge($core->register[$main], $core->register[$section]);
                    unset($lists[$key]);
                    unset($core->register[$section]);
            }
        }

        /*
         * Loop over header and body javascript file lists to generate the HTML
         * that will load javascript files to client
         */
        foreach ($lists as $section) {
            /*
             * Bundle all files for this list into one?
             */
            html_bundler($section);

            /*
             * Generate HTML that will load javascript files to client
             */
            foreach ($core->register[$section] as $file => $async) {
                if (!$file) {
                    /*
                     * We should never have empty files
                     */
                    notify(array('code'    => 'empty',
                        'groups'  => 'developers',
                        'title'   => tr('Empty file specified'),
                        'message' => tr('html_generate_js(): Found empty string file specified in html_load_js()')));
                    continue;
                }

                if (strstr($file, '://')) {
                    /*
                     * These are external scripts, hosted by somebody else
                     */
                    $html = '<script id="script-'.$count++.'" '.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.$file.'"'.($async ? ' async' : '').'></script>';

                } else {
                    /*
                     * These are local scripts, hosted by us
                     */
                    $html = '<script id="script-'.$count++.'" '.(!empty($data['option']) ? ' '.$data['option'] : '').' type="text/javascript" src="'.cdn_domain((($_CONFIG['whitelabels'] === true) ? $_SESSION['domain'].'/' : '').'js/'.($min ? $file.$min : Strings::until($file, '.min').$min).'.js').'"'.($async ? ' async' : '').'></script>';
                }

                if ($section === 'js_header') {
                    /*
                     * Add this script in the header
                     */
                    $return .= $html;

                } else {
                    /*
                     * Add this script in the footer of the body tag
                     */
                    $footer .= $html;
                }
            }

            $core->register[$section] = array();
        }

        /*
         * If we have footer data, add it to the footer register, which will
         * automatically be added to the end of the <body> tag
         */
        if (!empty($footer)) {
            $core->register['footer'] .= $footer.$core->register['footer'].Core::readRegister('system', 'script_delayed');
            unset($core->register['script_delayed']);
        }

        unset($core->register['js_header']);
        unset($core->register['js_footer']);

        return $return;
    }



    /*
     * Generate and return the HTML header
     *
     * This function will generate the entrire HTML header, from <!DOCTYPE> until </head><body>
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_generate_js()
     * @see html_generate_css()
     * @see http_headers()
     * @version 1.26.0: Added documentation
     * @example
     * code
     * $result = html_header();
     * /code
     *
     * @param params $params The parameters for the HTML header
     * @param string $params[title] The contents for the <title> tag
     * @param string $params[doctype] The complete <!DOCTYPE> tag to be used
     * @param string $params[http] The complete <html> tag to be used
     * @param boolean $params[captcha]
     * @param string $params[body] The complete <body> tag to be used
     * @param string $params[title]
     * @param string $params[links]
     * @param string $params[extra]
     * @param boolean $params[favicon]
     * @param boolean $params[amp]
     * @param array $params[prefecth_dns]
     * @param array $params[prefecth_files]
     * @param string $params[description]
     * @param string $params[keywords]
     * @param string $params[noindex]
     * @param string $params[canonical]
     * @param params $meta The list of meta values to be included in the <head> tags
     * @return string The HTML containing <script> tags that is to be included in the <head> tag
     */
    function header($params, $meta, &$html) {
        Arrays::ensure($params, 'links,extra');
        Arrays::default($params, 'http'          , 'html');
        Arrays::default($params, 'captcha'       , false);
        Arrays::default($params, 'doctype'       , '<!DOCTYPE html>');
        Arrays::default($params, 'html'          , '<html lang="'.LANGUAGE.'">');
        Arrays::default($params, 'body'          , '<body>');
        Arrays::default($params, 'favicon'       , true);
        Arrays::default($params, 'amp'           , false);
        Arrays::default($params, 'style'         , '');
        Arrays::default($params, 'prefetch_dns'  , $_CONFIG['prefetch']['dns']);
        Arrays::default($params, 'prefetch_files', $_CONFIG['prefetch']['files']);

        if (!empty($params['js'])) {
            html_load_js($params['js']);
        }

        $core->register['html'] = $html;

        /*
         * Load captcha javascript
         */
        if (!empty($_CONFIG['captcha']['type']) and $params['captcha']) {
            switch ($_CONFIG['captcha']['type']) {
                case 'recaptcha':
                    html_load_js($_CONFIG['captcha']['recaptcha']['js-api']);
                    break;
            }
        }

        /*
         * AMP page? Canonical page?
         */
        if (!empty($params['amp'])) {
            $params['links'] .= '<link rel="amphtml" href="'.domain('/amp'.$_SERVER['REQUEST_URI']).'">';
        }

        if (!empty($params['canonical'])) {
            $params['links'] .= '<link rel="canonical" href="'.$params['canonical'].'">';
        }

        $return =  $params['doctype'].
            $params['html'].'
               <head>';

        if ($params['style']) {
            $return .= '<style>'.$params['style'].'</style>';
        }

        if ($params['links']) {
            if (is_string($params['links'])) {
                $return .= $params['links'];

            } else {
// :OBSOLETE: Links specified as an array only adds more complexity, we're going to send it as plain HTML, and be done with the crap. This is still here for backward compatibility
                foreach ($params['links'] as $data) {
                    $sections = array();

                    foreach ($data as $key => $value) {
                        $sections[] = $key.'="'.$value.'"';
                    }

                    $return .= '<link '.implode(' ', $sections).'>';
                }
            }
        }

        foreach ($params['prefetch_dns'] as $prefetch) {
            $return .= '<link rel="dns-prefetch" href="//'.$prefetch.'">';
        }

        foreach ($params['prefetch_files'] as $prefetch) {
            $return .= '<link rel="prefetch" href="'.$prefetch.'">';
        }

        unset($prefetch);

        if (!empty($core->register['header'])) {
            $return .= $core->register['header'];
        }

        $return .= html_generate_css().
            html_generate_js();

        /*
         * Set load_delayed to false from here on. If anything after this still
         * generates javascript (footer function!) it should be directly sent to
         * client
         */
        $_CONFIG['cdn']['js']['load_delayed'] = false;

        /*
         * Add required fonts
         */
        if (!empty($params['fonts'])) {
            foreach ($params['fonts'] as $font) {
                $extension = Strings::fromReverse($font, '.');

                switch ($extension) {
                    case 'woff':
                        // no-break
                    case 'woff2':
                        $return .= '<link rel="preload" href="'.$font.'" as="font" type="font/'.$extension.'" crossorigin="anonymous">';
                        break;

                    default:
                        if (!str_contains($font, 'fonts.googleapis.com')) {
                            throw new HtmlException(tr('html_header(): Unknown font type ":type" specified for font ":font"', array(':type' => $extension, ':font' => $font)), 'unknown');
                        }

                        $return .= '<link rel="preload" href="'.$font.'" as="font" type="text/css" crossorigin="anonymous">';
                }
            }
        }

        /*
         * Add meta data, favicon, and <body> tag
         */
        $return .= html_meta($meta);
        $return .= html_favicon($params['favicon']).$params['extra'];
        $return .= '</head>'.$params['body'];

        return $return;
    }



    /*
     * Generate all <meta> tags
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_header()
     * @see html_og()
     * @note: This function is primarily used by html_header(). There should not be any reason to call this function from any other location
     * @version 2.4.89: Added function and documentation
     * @version 2.8.24: Added support for html_og() open graph data
     * @version 2.8.25: Fixed various minor issues, improved warning messages
     *
     * @param params $meta The required meta tags in key => value format
     * @return string The <meta> tags
     */
    function meta($meta) {
        /*
         * Add all other meta tags
         * Only add keywords with contents, all that have none are considerred
         * as false, and do-not-add
         */
        Arrays::ensure($meta, 'title,description,og');

//<meta property="og:locale" content="en_GB" />
//<meta property="og:locale:alternate" content="fr_FR" />
//<meta property="og:locale:alternate" content="es_ES" />

        /*
         * Add meta tag no-index for non production environments and admin pages
         */
        if (!empty($meta['noindex']) or !Debug::production() or $_CONFIG['noindex'] or Core::getCallType('admin')) {
            $meta['robots'] = 'noindex, nofollow, nosnippet, noarchive, noydir';
            unset($meta['noindex']);
        }

        /*
         * Validate meta keys
         */
        if (empty($meta['title'])) {
            $meta['title'] = domain(true);
            notify(new HtmlException(tr('html_meta(): No meta title specified for script ":script" (BAD SEO!)', array(':script' => $core->register['script'])), 'warning/not-specified'));

        } elseif (strlen($meta['title']) > 65) {
            $meta['title'] = str_truncate($meta['title'], 65);
            notify(new HtmlException(tr('html_meta(): Specified meta title ":title" is larger than 65 characters', array(':title' => $meta['title'])), 'warning/invalid'));
        }

        if (empty($meta['description'])) {
            $meta['description'] = domain(true);
            notify(new HtmlException(tr('html_meta(): No meta description specified for script ":script" (BAD SEO!)', array(':script' => $core->register['script'])), 'warning/not-specified'));

        } elseif (strlen($meta['description']) > 155) {
            $meta['description'] = str_truncate($meta['description'], 155);
            notify(new HtmlException(tr('html_meta(): Specified meta description ":description" is larger than 155 characters', array(':description' => $meta['description'])), 'warning/invalid'));
        }

        /*
         * Add configured meta keys
         */
        if (!empty($_CONFIG['meta'])) {
            /*
             * Add default configured meta tags
             */
            $meta = array_merge($_CONFIG['meta'], $meta);
        }

        /*
         * Add viewport meta tag for mobile devices
         */
        if (empty($meta['viewport'])) {
            $meta['viewport'] = isset_get($_CONFIG['mobile']['viewport']);
        }

        if (!$meta['viewport']) {
            notify(new HtmlException(tr('html_header(): Meta viewport tag is not specified'), 'warning/not-specified'));
        }

        /*
         * Start building meta data
         */
        $return = '<meta http-equiv="Content-Type" content="text/html;charset="'.$_CONFIG['encoding']['charset'].'">'.
            '<title>'.$meta['title'].'</title>';

        foreach ($meta as $key => $value) {
            if ($key === 'og') {
                $return .= html_og($value, $meta);

            } elseif (substr($key, 0, 3) === 'og:') {
// :COMPATIBILITY: Remove this section @ 2.10
                notify(new HtmlException(tr('html_meta(): Found $meta[:key], this should be $meta[og][:ogkey], ignoring', array(':key' => $key, ':ogkey' => Strings::from($key, 'og:'))), 'warning/invalid'));

            } else {
                $return .= '<meta name="'.$key.'" content="'.$value.'">';
            }
        }

        return $return;
    }



    /*
     * Generate all open graph <meta> tags
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_header()
     * @see html_meta()
     * @note: This function is primarily used by html_header(). There should not be any reason to call this function from any other location
     * @note: Any OG meta properties without content will cause notifications, not errors. This will not stop the page from loading, but log entries will be made and developers will receive warnings to resolve the issue
     * @version 2.8.24: Added function and documentation
     * @version 2.8.25: Fixed various minor issues, improved warning messages
     *
     * @param params $og The required meta tags in property => content format
     * @param params $$meta The required meta data
     * @return string The <meta> tags containing open graph data
     */
    function og($og, $meta) {
        Arrays::ensure($meta, 'title,description');
        Arrays::ensure($og, 'description,url,image');
        Arrays::default($og, 'url'        , domain(true));
        Arrays::default($og, 'site_name'  , $_CONFIG['name']);
        Arrays::default($og, 'title'      , $meta['title']);
        Arrays::default($og, 'image'      , (isset($_CONFIG['logo']['og']) ? cdn_domain($_CONFIG['logo']['og']) : ''));
        Arrays::default($og, 'description', $meta['description']);
        Arrays::default($og, 'locale'     , $core->register['locale']);
        Arrays::default($og, 'type'       , 'website');

        $return = '';

        if (strlen($og['description']) > 65) {
            $og['description'] = str_truncate($og['description'], 65);
            notify(new HtmlException(tr('html_og(): Specified OG description ":description" is larger than 65 characters, truncating to correct size', array(':description' => $og['description'])), 'warning/invalid'));
        }

        if (strlen($og['title']) > 35) {
            $og['title'] = str_truncate($og['title'], 35);
            notify(new HtmlException(tr('html_og(): Specified OG title ":title" is larger than 35 characters, truncating to correct size', array(':title' => $og['title'])), 'warning/invalid'));
        }

        $og['locale'] = Strings::until($og['locale'], '.');

        foreach ($og as $property => $content) {
            if (empty($content)) {
                notify(new HtmlException(tr('html_og(): Missing property content for meta og key ":property". Please add this data for SEO!', array(':property' => $property)), 'warning/not-specified'));
            }

            $return .= '<meta property="og:'.$property.'" content="'.$content.'">';
        }

        return $return;
    }



    /*
     * Generate and return the HTML footer
     *
     * This function generates and returns the HTML footer. Any data stored in $core->register[footer] will be added, and if the debug bar is enabled, it will be attached as well
     *
     * This function should be called in your c_page() function
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_header()
     * @version 2.5.9: Added documentation, added debug bar support
     *
     * @return string The footer HTML
     */
    function footer() {
        $html = '';

        if (Debug::enabled()) {
            $html .= debug_bar();
        }

        return $html;
    }



    /*
     * Generate and return the HTML footer
     *
     * This function generates and returns the HTML footer. Any data stored in $core->register[footer] will be added, and if the debug bar is enabled, it will be attached as well
     *
     * This function should be called in your c_page() function
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_header()
     * @version 2.5.9: Added documentation, added debug bar support
     *
     * @return string The footer HTML
     */
    function end() {
        if ($core->register['footer']) {
            return $core->register['footer'].'</body></html>';
        }

        return '</body></html>';
    }



    /*
     * Generate and return HTML to show HTML flash messages
     *
     * This function will scan the $_SESSION[flash] array for messages to be displayed as flash messages. If $class is specified, only messages that have the specified class will be displayed. If multiple flash messages are available, all will be returned. Messages that are returned will be removed from the $_SESSION[flash] array.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_flash_set()
     * @version 1.26.0: Added documentation
     * @note Each message will be placed in an HTML template defined in $_CONFIG[flash][html]
     * @example
     * code
     * $html = '<div>.
     *             'html_flash('users').'
     *          </div>';
     * /code
     *
     * @param string $class If specified, only display messages with this specified class
     * @return string The HTML containing all flash messages that matched
     */
    function flash($class = null) {
        if (!PLATFORM_HTTP) {
            throw new HtmlException('html_flash(): This function can only be executed on a webserver!');
        }

        if (!isset($_SESSION['flash'])) {
            /*
             * Nothing to see here!
             */
            return '';
        }

        if (!is_array($_SESSION['flash'])) {
            /*
             * $_SESSION['flash'] should always be an array. Don't crash on minor detail, just correct and continue
             */
            $_SESSION['flash'] = array();

            notify(array('code'    => 'invalid',
                'groups'  => 'developers',
                'title'   => tr('Invalid flash structure specified'),
                'message' => tr('html_flash(): Invalid flash structure in $_SESSION array, it should always be an array but it is a ":type". Be sure to always use html_flash_set() to add new flash messages', array(':type' => gettype($_SESSION['flash'])))));
        }

        $return = '';

        foreach ($_SESSION['flash'] as $id => $flash) {
            Arrays::default($flash, 'class', null);

            if ($flash['class'] and ($flash['class'] != $class)) {
                continue;
            }

            Arrays::default($flash, 'title', null);
            Arrays::default($flash, 'type' , null);
            Arrays::default($flash, 'html' , null);
            Arrays::default($flash, 'text' , null);

            unset($flash['class']);

            switch ($type = strtolower($flash['type'])) {
                case 'info':
                    break;

                case 'information':
                    break;

                case 'success':
                    break;

                case 'error':
                    break;

                case 'warning':
                    break;

                case 'attention':
                    break;

                case 'danger':
                    break;

                default:
                    $type = 'error';
// :TODO: NOTIFY OF UNKNOWN HTML FLASH TYPE
            }

            if (!Debug::enabled()) {
                /*
                 * Don't show "function_name(): " part of message
                 */
                $flash['html'] = trim(Strings::from($flash['html'], '():'));
                $flash['text'] = trim(Strings::from($flash['text'], '():'));
            }

            /*
             * Set the indicator that we have added flash texts
             */
            switch ($_CONFIG['flash']['type']) {
                case 'html':
                    /*
                     * Either text or html could have been specified, or both
                     * In case both are specified, show both!
                     */
                    foreach (array('html', 'text') as $type) {
                        if ($flash[$type]) {
                            $return .= tr($_CONFIG['flash']['html'], array(':message' => $flash[$type], ':type' => $flash['type'], ':hidden' => ''), false);
                        }
                    }

                    break;

                case 'sweetalert':
                    if ($flash['html']) {
                        /*
                         * Show specified html
                         */
                        $sweetalerts[] = array_remove($flash, 'text');
                    }

                    if ($flash['text']) {
                        /*
                         * Show specified text
                         */
                        $sweetalerts[] = array_remove($flash, 'html');
                    }

                    break;

                default:
                    throw new HtmlException(tr('html_flash(): Unknown html flash type ":type" specified. Please check your $_CONFIG[flash][type] configuration', array(':type' => $_CONFIG['flash']['type'])), 'unknown');
            }

            $core->register['flash'] = true;
            unset($_SESSION['flash'][$id]);
        }

        switch ($_CONFIG['flash']['type']) {
            case 'html':
// :TODO: DONT USE tr() HERE!!!!
                /*
                 * Add an extra hidden flash text box that can respond for jsFlashMessages
                 */
                return $return.tr($_CONFIG['flash']['html'], array(':message' => '', ':type' => '', ':hidden' => ' hidden'), false);

            case 'sweetalert':
                load_libs('sweetalert');

                switch (count(isset_get($sweetalerts, array()))) {
                    case 0:
                        /*
                         * No alerts
                         */
                        return '';

                    case 1:
                        return html_script(sweetalert(array_pop($sweetalerts)));

                    default:
                        /*
                         * Multiple modals, show a queue
                         */
                        return html_script(sweetalert_queue(array('modals' => $sweetalerts)));
                }
        }
    }



    /*
     * Set a message in the $_SESSION[flash] array so that it can be shown later as an HTML flash message
     *
     * Messages set with this function will be stored in the $_SESSION[flash] array, which can later be accessed by html_flash(). Messages stored without a class will be shown on any page, messages stored with a class will only be shown on the pages where html_flash() is called with that specified class.
     *
     * Each message requires a type, which can be one of info, warning, error, or success. Depending on the type, the shown flash message will be one of those four types
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_flash()
     * @version 1.26.0: Added documentation
     * @example
     * code
     * html_flash_set(tr('The action was succesful!'), 'success', 'users');
     * /code
     *
     * @param mixed $params The message to be shown. Can be a simple string, a parameter array or an exception object. In case if an exception object was given, the $e->getMessage() text will be used. In case a parameter object was specified, the following variables may be specified
     * @param params $params[html] The actual message to be shown. May include HTML if needed
     * @param params $params[type] The type of flash message to be shown, must be one of "info", "warning", "error" or "success". Defaults to $type
     * @param params $params[title] (Only applies when sweetalert flash messages are used) The title of the sweetalert popup. Defaults to a str_capitalized() $type
     * @param params $params[class] the class for this message. If specified, subsequent html_flash() calls will only return this message if the class matches. Defaults to $class
     * @param string $type The type of flash message to be shown, must be one of "info", "warning", "error" or "success"
     * @param string $class If specified, subsequent html_flash() calls will only return this specific message if they specify the same class
     * @return string The HTML containing all flash messages that matched
     */
    function flash_set($params, $type = 'info', $class = null) {
        try {
            if (!PLATFORM_HTTP) {
                throw new HtmlException(tr('html_flash_set(): This function can only be executed on a webserver!'), 'invalid');
            }

            if (!$params) {
                /*
                 * Wut? no message?
                 */
                throw new HtmlException(tr('html_flash_set(): No messages specified'), 'not-specified');
            }

            /*
             * Ensure session flash data consistency
             */
            if (empty($_SESSION['flash'])) {
                $_SESSION['flash'] = array();
            }

            if (is_object($params)) {
                return include(__DIR__.'/handlers/html-flash-set-object.php');
            }

            /*
             * Backward compatibility
             */
            if (!is_array($params)) {
                $params = array('title' => str_capitalize($type),
                    'html'  => $params,
                    'type'  => $type,
                    'class' => $class);
            }

            /*
             * Backward compatibility as well
             */
            if (empty($params['html']) and empty($params['text']) and empty($params['title'])) {
                if (Debug::production()) {
                    notify(array('code'    => 'invalid',
                        'groups'  => 'developers',
                        'title'   => tr('Invalid flash structure specified'),
                        'message' => tr('html_flash_set(): Invalid html flash structure specified'),
                        'data'    => $params));

                    return html_flash_set(implode(',', $params), $type, $class);
                }

                throw new HtmlException(tr('html_flash_set(): Invalid call data ":data", should contain at least "text" or "html" or "title"!', array(':data' => $params)), 'invalid');
            }

            switch (strtolower($params['type'])) {
                case 'success':
                    $color = 'green';
                    break;

                case 'exception':
                    // no-break
                case 'error':
                    $color = 'green';
                    break;

                default:
                    $color = 'yellow';
            }

            if (empty($params['title'])) {
                $params['title'] = str_capitalize($params['type']);
            }

            $_SESSION['flash'][] = $params;

            log_file(strip_tags($params['html']), $core->register['script'], $color);

        }catch(Exception $e) {
            if (Debug::enabled() and (substr(Strings::from($e->getCode(), '/'), 0, 1) == '_')) {
                /*
                 * These are exceptions sent to be shown as an html flash error, but
                 * since we're in debug mode, we'll just show it as an uncaught
                 * exception. Don't add html_flash_set() history to this exception
                 * as that would cause confusion.
                 */
                throw $e->setCode(substr(Strings::from($e->getCode(), '/'), 1));
            }

            /*
             * Here, something actually went wrong within html_flash_set()
             */
            throw new HtmlException('html_flash_set(): Failed', $e);
        }
    }



///*
// * Returns true if there is an HTML message with the specified class
// */
//function flash_class($class = null) {
//    try {
//        if (isset($_SESSION['flash'])) {
//            foreach ($_SESSION['flash'] as $message) {
//                if ((isset_get($message['class']) == $class) or ($message['class'] == '*')) {
//                    return true;
//                }
//            }
//        }
//
//        return false;
//
//    }catch(Exception $e) {
//        throw new HtmlException('html_flash_class(): Failed', $e);
//    }
//}



    /*
     * Returns HTML for an HTML anchor link <a> that is safe for use with target
     * _blank
     *
     * For vulnerability info:
     * See https://dev.to/ben/the-targetblank-vulnerability-by-example
     * See https://mathiasbynens.github.io/rel-noopener/
     *
     * For when to use _blank anchors:
     * See https://css-tricks.com/use-target_blank/
     */
    function a($params) {
        array_params ($params, 'href');
        Arrays::default($params, 'name'  , '');
        Arrays::default($params, 'target', '');
        Arrays::default($params, 'rel'   , '');

        switch ($params['target']) {
            case '_blank':
                $params['rel'] .= ' noreferrer noopener';
                break;
        }

        if (empty($params['href'])) {
            throw new HtmlException('html_a(): No href specified', 'not-specified');
        }

        if ($params['name']) {
            $params['name'] = ' name="'.$params['name'].'"';
        }

        if ($params['class']) {
            $params['class'] = ' class="'.$params['class'].'"';
        }

        $return = '<a href="'.$params['href'].'"'.$params['name'].$params['class'].$params['rel'].'">';

        return $return;
    }



    /*
     * Return HTML for a submit button
     * If the button should not cause validation, then use "no_validation" true
     */
    function submit($params, $class = '') {
        static $added;

        array_params ($params, 'value');
        Arrays::default($params, 'name'         , 'dosubmit');
        Arrays::default($params, 'class'        , $class);
        Arrays::default($params, 'no_validation', false);
        Arrays::default($params, 'value'        , 'submit');

        if ($params['no_validation']) {
            $params['class'] .= ' no_validation';

            if (empty($added)) {
                $added  = true;
                $script = html_script('$(".no_validation").click(function() { $(this).closest("form").find("input,textarea,select").addClass("ignore"); $(this).closest("form").submit(); });');
            }
        }

        if ($params['class']) {
            $params['class'] = ' class="'.$params['class'].'"';
        }

        if ($params['value']) {
            $params['value'] = ' value="'.$params['value'].'"';
        }

        $return = '<input type="submit" id="'.$params['name'].'" name="'.$params['name'].'"'.$params['class'].$params['value'].'>';

        return $return.isset_get($script);
    }



    /*
     * Return HTML for a multi select submit button. This button, once clicked, will show a list of selectable submit buttons.
     *
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_select()
     *
     * @param params $params The parameters for this HTML select button
     * @params string name The HTML name for the button
     * @params string id The HTML id for the button
     * @params boolean autosubmit If set to true, clicking the button will automatically subimit the form where this button is placed
     * @params string none The text that will be shown when the button is closed and not used
     * @params midex buttons The buttons to be shown. This may be specified by array, or PDO SQL statement
     * @return string The HTML for the button selector
     */
    function select_submit($params) {
        array_params ($params);
        Arrays::default($params, 'name'      , 'multisubmit');
        Arrays::default($params, 'id'        , '');
        Arrays::default($params, 'autosubmit', true);
        Arrays::default($params, 'none'      , tr('Select action'));
        Arrays::default($params, 'buttons'   , array());

        /*
         * Build the html_select resource from the buttons
         */
        if (is_object($params['buttons'])) {
            /*
             * This should be a PDO statement, do nothing, html_select will take
             * care of it
             */
            $params['resource'] = $params['buttons'];

        } elseif (is_array($params['buttons'])) {
            foreach ($params['buttons'] as $key => $value) {
                if (is_numeric($key)) {
                    $key = $value;
                }

                $params['resource'][$key] = $value;
            }

        } else {
            $type = gettype($params['buttons']);

            if ($type === 'object') {
                $type .= tr(' of class :class', array(':class' => get_class($params['buttons'])));
            }

            throw new HtmlException(tr('html_select_submit(): Invalid data type specified for params "buttons", it should be an array or PDO statement object, but it is an ":type"', array(':type' => $type)), 'invalid');
        }

        return html_select($params);
    }



    /*
     * Return HTML for a <select> list
     *
     * This function is the go-to function when <select> boxes must be created.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_select_body()
     * @version 1.26.0: Added documentation
     * @example
     * code
     *     $html .= '<div>
     *                   '.html_select(array('name'       => 'users_id',
     *                                       'class'      => 'users',
     *                                       'autosubmit' => true,
     *                                       'selected'   => $item['users_id'],
     *                                       'resource'   => sql_query('SELECT `id`, `name` FROM `users` WHERE `status` IS NULL'))).'
     *               </div>
     * /code
     * @example
     * code
     *     $html .= '<div>
     *                   '.html_select(array('name'       => 'letter',
     *                                       'selected'   => $item['letter'],
     *                                       'resource'   => array('a', 'b', 'c', 'd', 'e'))).'
     *               </div>
     * /code
     *
     * @param params $params The parameters for this <select> box
     * @param string $params[class] If specified, <select class="CLASS"> will be used
     * @param string $params[option_class] If specified, <option class="CLASS"> will be used
     * @param boolean $params[disabled] If specified, <select disabled> will be used
     * @param string $params[name] If specified, <select id="NAME" name="NAME"> will be used
     * @param string $params[id] If specified, <select id="ID"> will be used. This will override the "name" variable
     * @param string $params[none] If specified, and no <option> is selected due to "selected", this text will be shown. Defaults to "None selected"
     * @param string $params[empty] If specified, and the resource is empty, this text will be shown. Defaults to "None available"
     * @param numeric $params[tabindex] If specified, <select tabindex="TABINDEX"> will be used
     * @param string $params[extra] If specified, these extra HTML attributes will be added into the <select> tag
     * @param string $params[selected] If specified, the <option> that has the specified key will be selected
     * @param boolean $params[bodyonly] If specified, only the body contents of this select will be returned, so the <select> tags will be removed. This is useful when having a page in a client that needs to change its contents using an AJAX call
     * @param boolean $params[autosubmit] If specified, javascript code will be added to automatically execute a form submit when the <select> has an onchange event
     * @param string $params[onchange] If specified, this code will be execute on an onchange event for the <select> element
     * @param boolean $params[hide_empty]
     * @param boolean $params[autofocus] If set to true, <select autofocus> will be used, drawing the focus directly on this select item
     * @param boolean $params[multiple] If set to true, <select multiple> will be used, allowing the selection of multiple items in the list
     * @param mixed resource The resource for the contents of the <select>. May be a key => value array (where each value must be of scalar datatype) or a PDO statement from a query that selects 2 columns, where the first column will be the key and the second column the value.
     * @return string The HTML for a <select> tag.
     */
    function select($params) {
        static $count = 0;

        array_params ($params);
        Arrays::default($params, 'class'       , 'form-control');
        Arrays::default($params, 'option_class', '');
        Arrays::default($params, 'disabled'    , false);
        Arrays::default($params, 'name'        , '');
        Arrays::default($params, 'id'          , $params['name']);
        Arrays::default($params, 'none'        , tr('None selected'));
        Arrays::default($params, 'empty'       , tr('None available'));
        Arrays::default($params, 'tabindex'    , html_tabindex());
        Arrays::default($params, 'extra'       , '');
        Arrays::default($params, 'selected'    , null);
        Arrays::default($params, 'bodyonly'    , false);
        Arrays::default($params, 'autosubmit'  , false);
        Arrays::default($params, 'onchange'    , '');
        Arrays::default($params, 'hide_empty'  , false);
        Arrays::default($params, 'autofocus'   , false);
        Arrays::default($params, 'multiple'    , false);

        if (!$params['tabindex']) {
            $params['tabindex'] = html_tabindex();
        }

        if (!$params['name']) {
            if (!$params['id']) {
                throw new HtmlException(tr('html_select(): No name specified'), 'not-specified');
            }

            $params['name'] = $params['id'];
        }

        if ($params['autosubmit']) {
            if ($params['class']) {
                $params['class'] .= ' autosubmit';

            } else {
                $params['class']  = 'autosubmit';
            }
        }

        if (empty($params['resource'])) {
            if ($params['hide_empty']) {
                return '';
            }

            $params['resource'] = array();

// :DELETE: Wut? What exactly was this supposed to do? doesn't make any sense at all..
            //if (is_numeric($params['disabled'])) {
            //    $params['disabled'] = true;
            //
            //} else {
            //    if (is_array($params['resource'])) {
            //        $params['disabled'] = ((count($params['resource']) + ($params['name'] ? 1 : 0)) <= $params['disabled']);
            //
            //    } elseif (is_object($params['resource'])) {
            //        $params['disabled'] = (($params['resource']->rowCount() + ($params['name'] ? 1 : 0)) <= $params['disabled']);
            //
            //    } elseif ($params['resource'] === null) {
            //        $params['disabled'] = true;
            //
            //    } else {
            //        throw new HtmlException(tr('html_select(): Invalid resource of type "%type%" specified, should be either null, an array, or a PDOStatement object', array('%type%' => gettype($params['resource']))), 'invalid');
            //    }
            //}
        }

        if ($params['bodyonly']) {
            return html_select_body($params);
        }

        /*
         * <select> class should not be applied to <option>
         */
        $class = $params['class'];
        $params['class'] = $params['option_class'];

        $body = html_select_body($params);

        if (substr($params['id'], -2, 2) == '[]') {
            $params['id'] = substr($params['id'], 0, -2).$count++;
        }

        if ($params['multiple']) {
            $params['multiple'] = ' multiple="multiple"';

        } else {
            $params['multiple'] = '';
        }

        if ($params['disabled']) {
            /*
             * Add a hidden element with the name to ensure that multiple selects with [] will not show holes
             */
            return '<select'.$params['multiple'].($params['tabindex'] ? ' tabindex="'.$params['tabindex'].'"' : '').($params['id'] ? ' id="'.$params['id'].'_disabled"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['extra'] ? ' '.$params['extra'] : '').' readonly disabled>'.
                $body.'</select><input type="hidden" name="'.$params['name'].'" >';
        } else {
            $return = '<select'.$params['multiple'].($params['id'] ? ' id="'.$params['id'].'"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['disabled'] ? ' disabled' : '').($params['autofocus'] ? ' autofocus' : '').($params['extra'] ? ' '.$params['extra'] : '').'>'.
                $body.'</select>';
        }

        if ($params['onchange']) {
            /*
             * Execute the JS code for an onchange
             */
            $return .= html_script('$("#'.$params['id'].'").change(function() { '.$params['onchange'].' });');

        }

        if (!$params['autosubmit']) {
            /*
             * There is no onchange and no autosubmit
             */
            return $return;

        } elseif ($params['autosubmit'] === true) {
            /*
             * By default autosubmit on the id
             */
            $params['autosubmit'] = $params['name'];
        }

        /*
         * Autosubmit on the specified selector
         */
        $params['autosubmit'] = str_replace('[', '\\\\[', $params['autosubmit']);
        $params['autosubmit'] = str_replace(']', '\\\\]', $params['autosubmit']);

        return $return.html_script('$("[name=\''.$params['autosubmit'].'\']").change(function() { $(this).closest("form").find("input,textarea,select").addClass("ignore"); $(this).closest("form").submit(); });');
    }



    /*
     * Return the body HTML for a <select> list
     *
     * This function returns only the body (<option> tags) for a <select> list. Typically, html_select() would be used, but this function is useful in situations where only the <option> tags would be required, like for example a web page that dynamically wants to change the contents of a <select> box using an AJAX call
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_select()
     * @version 1.26.0: Added documentation
     *
     * @param params $params The parameters for this <select> box
     * @param string $params[class] If specified, <option class="CLASS"> will be used
     * @param string $params[none] If specified, and no <option> is selected due to "selected", this text will be shown. Defaults to "None selected"
     * @param string $params[empty] If specified, and the resource is empty, this text will be shown. Defaults to "None available"
     * @param string $params[selected] If specified, the <option> that has the specified key will be selected
     * @param boolean $params[auto_select] If specified and the resource contains only one item, this item will be autmatically selected
     * @param mixed $params[resource] The resource for the contents of the <select>. May be a key => value array (where each value must be of scalar datatype) or a PDO statement from a query that selects 2 columns, where the first column will be the key and the second column the value.
     * @param mixed $params[data_resource]
     * @return string The body HTML for a <select> tag, containing all <option> tags
     */
    function select_body($params) {
        array_params ($params);
        Arrays::default($params, 'class'        , '');
        Arrays::default($params, 'none'         , tr('None selected'));
        Arrays::default($params, 'empty'        , tr('None available'));
        Arrays::default($params, 'selected'     , null);
        Arrays::default($params, 'auto_select'  , true);
        Arrays::default($params, 'data_resource', null);

        if ($params['none']) {
            $return = '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(($params['selected'] === null) ? ' selected' : '').' value="">'.$params['none'].'</option>';

        } else {
            $return = '';
        }

        if ($params['data_resource'] and !is_array($params['data_resource'])) {
            throw new HtmlException(tr('html_select_body(): Invalid data_resource specified, should be an array, but received a ":gettype"', array(':gettype' => gettype($params['data_resource']))), 'invalid');
        }

        if ($params['resource']) {
            if (is_array($params['resource'])) {
                if ($params['auto_select'] and ((count($params['resource']) == 1) and !$params['none'])) {
                    /*
                     * Auto select the only available element
                     */
                    $params['selected'] = array_keys($params['resource']);
                    $params['selected'] = array_shift($params['selected']);
                }

                /*
                 * Process array resource
                 */
                foreach ($params['resource'] as $key => $value) {
                    $notempty    = true;
                    $option_data = '';

                    if ($params['data_resource']) {
                        foreach ($params['data_resource'] as $data_key => $resource) {
                            if (!empty($resource[$key])) {
                                $option_data .= ' data-'.$data_key.'="'.$resource[$key].'"';
                            }
                        }
                    }

                    $return  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.((($params['selected'] !== null) and ($key === $params['selected'])) ? ' selected' : '').' value="'.html_safe($key).'"'.$option_data.'>'.html_safe($value).'</option>';
                }

            } elseif (is_object($params['resource'])) {
                if (!($params['resource'] instanceof PDOStatement)) {
                    throw new HtmlException(tr('html_select_body(): Specified resource object is not an instance of PDOStatement'), 'invalidresource');
                }

                if ($params['auto_select'] and ($params['resource']->rowCount() == 1)) {
                    /*
                     * Auto select the only available element
                     */
// :TODO: Implement
                }

                /*
                 * Process SQL resource
                 */
                while ($row = sql_fetch($params['resource'], false, PDO::FETCH_NUM)) {
                    $notempty    = true;
                    $option_data = '';

                    /*
                     * To avoid select problems with "none" entries, empty id column values are not allowed
                     */
                    if (!$row[0]) {
                        $row[0] = str_random(8);
                    }

                    /*
                     * Add data- in this option?
                     */
                    if ($params['data_resource']) {
                        foreach ($params['data_resource'] as $data_key => $resource) {
                            if (!empty($resource[$key])) {
                                $option_data = ' data-'.$data_key.'="'.$resource[$key].'"';
                            }
                        }
                    }

                    $return  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(($row[0] === $params['selected']) ? ' selected' : '').' value="'.html_safe($row[0]).'"'.$option_data.'>'.html_safe($row[1]).'</option>';
                }

            } else {
                throw new HtmlException(tr('html_select_body(): Specified resource ":resource" is neither an array nor a PDO statement', array(':resource' => $params['resource'])), 'invalid');
            }
        }


        if (empty($notempty)) {
            /*
             * No conent (other than maybe the "none available" entry) was added
             */
            if ($params['empty']) {
                $return = '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').' selected value="">'.$params['empty'].'</option>';
            }

            /*
             * Return empty body (though possibly with "none" element) so that the html_select() function can ensure the select box will be disabled
             */
            return $return;
        }

        return $return;
    }



    /*
     * Generate HTML <script> tags, and depending on load_delayed, return them immediately or attach them to $core->resource[footer]
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_select()
     * @note If $_CONFIG[cdn][js][load_delayed] is true, this function will not return anything, and add the generated HTML to $core->register[script_delayed] instead
     * @note Even if $_CONFIG[cdn][js][load_delayed] is true, the return value of this function should always be received in a variable, just in case the setting gets changes for whatever reason
     * @version 1.26.0: Added documentation
     *
     * @param params string $script The javascript content
     * @param boolean $dom_content_loaded If set to true, the $script will be changed to document.addEventListener("DOMContentLoaded", function(e) { :script });
     * @param string $extra If specified, these extra HTML attributes will be added into the <script> tag
     * @param string $type The <script type="TYPE"> contents. Defaults to "text/javascript"
     * @return string The body HTML for a <select> tag, containing all <option> tags
     */
    function script($script, $event = 'dom_content', $extra = null, $type = 'text/javascript') {
        static $count = 0;

        array_params($script, 'script');
        Arrays::default($script, 'event'  , $event);
        Arrays::default($script, 'extra'  , $extra);
        Arrays::default($script, 'type'   , $type);
        Arrays::default($script, 'to_file', null);
        Arrays::default($script, 'list'   , 'scripts');
        Arrays::default($script, 'delayed', $_CONFIG['cdn']['js']['load_delayed']);

        if ($script['to_file'] === null) {
            /*
             * The option if this javascript should be written to an external
             * file should be taken from the configuration
             */
            $script['to_file'] = $_CONFIG['cdn']['js']['internal_to_file'];
        }

        if (!$script['script']) {
            /*
             * No javascript was specified, notify developers
             */
            notify(new HtmlException(tr('html_script(): No javascript code specified'), 'not-specified'));
            return '';
        }

        switch ($script['script'][0]) {
            case '>':
                /*
                 * Keep this script internal! This is required when script contents
                 * contain session sensitive data, or may even change per page
                 */
                $return            = '<script type="'.$type.'" src="'.cdn_domain('js/'.substr($script['script'], 1)).'"'.($extra ? ' '.$extra : '').'></script>';
                $script['to_file'] = false;
                break;

            case '!':
                /*
                 * Keep this script internal! This is required when script contents
                 * contain session sensitive data, or may even change per page
                 */
                $return            = substr($script['script'], 1);
                $script['to_file'] = false;

            // no-break

            default:
                /*
                 * Event wrapper
                 *
                 * On what event should this script be executed? Eithere boolean true
                 * for standard "document ready" or your own jQuery
                 *
                 * If false, no event wrapper will be added
                 */
                if ($script['event']) {
                    switch ($script['event']) {
                        case 'dom_content':
                            $return = 'document.addEventListener("DOMContentLoaded", function(e) {
                                      '.$script['script'].'
                                   });';
                            break;

                        case 'window':
                            $return = 'window.addEventListener("load", function(e) {
                                      '.$script['script'].'
                                   });';
                            break;

                        case 'function':
                            $return = '$(function() {
                                      '.$script['script'].'
                                   });';
                            break;

                        default:
                            throw new HtmlException(tr('html_script(): Unknown event value ":value" specified', array(':value' => $script['event'])), 'unknown');
                    }

                } else {
                    /*
                     * Don't wrap the specified script in an event wrapper
                     */
                    $return = $script['script'];
                }

                if ($script['to_file']) {
                    $return .= ';';

                } else {
                    $return  = ' <script type="'.$type.'"'.($extra ? ' '.$extra : '').'>
                                 '.$return.'
                             </script>';
                }
        }

        /*
         * Store internal script in external files, or keep them internal?
         */
        if ($script['to_file']) {
            try {
                /*
                 * Create the cached file names
                 */
                $base = 'cached-'.substr($core->register['script'], 0, -4).'-'.($core->register['script_file'] ? $core->register['script_file'].'-' : '').$count;
                $file = ROOT.'www/'.LANGUAGE.(Core::getCallType('admin') ? '/admin' : '').'/pub/js/'.$base;

                log_file(tr('Creating externally cached javascript file ":file"', array(':file' => $file.'.js')), 'html-script', 'VERYVERBOSE/cyan');

                /*
                 * Check if the cached file exists and is not too old.
                 */
                if (file_exists($file.'.js')) {
                    if (!filesize($file.'.js')) {
                        /*
                         * The javascript file is empty
                         */
                        log_file(tr('Deleting externally cached javascript file ":file" because the file is 0 bytes', array(':file' => $file.'.js')), 'html-script', 'yellow');

                        File::executeMode(ROOT.'www/'.LANGUAGE.'/pub/js', 0770, function() use ($file) {
                            file_chmod($file.'.js,'.$file.'.min.js', 'ug+w', ROOT.'www/'.LANGUAGE.'/pub/js');
                            file_delete(array('patterns'       => $file.'.js,'.$file.'.min.js',
                                'force_writable' => true,
                                'restrictions'   => ROOT.'www/'.LANGUAGE.'/pub/js'));
                        });

                    } elseif (($_CONFIG['cdn']['cache_max_age'] > 60) and ((filemtime($file.'.js') + $_CONFIG['cdn']['cache_max_age']) < time())) {
                        /*
                         * External cached file is too old
                         */
                        log_file(tr('Deleting externally cached javascript file ":file" because the file cache time expired', array(':file' => $file.'.js')), 'html-script', 'yellow');

                        File::executeMode(ROOT.'www/'.LANGUAGE.'/pub/js', 0770, function() use ($file) {
                            file_delete(array('patterns'       => $file.'.js,'.$file.'.min.js',
                                'force_writable' => true,
                                'restrictions'   => ROOT.'www/'.LANGUAGE.'/pub/js'));
                        });
                    }
                }

                /*
                 * If file does not exist, create it now. Check again if it
                 * exist, because the previous function may have possibly
                 * deleted it
                 */
                if (!file_exists($file.'.js')) {
                    File::executeMode(dirname($file), 0770, function() use ($file, $return) {
                        log_file(tr('Writing internal javascript to externally cached file ":file"', array(':file' => $file.'.js')), 'html-script', 'cyan');
                        file_put_contents($file.'.js', $return);
                    });
                }

                /*
                 * Always minify the file. On local machines where minification is
                 * turned off this is not a problem, it should take almost zero
                 * resources, and it will immediately test minification for
                 * production as well.
                 */
                if (!file_exists($file.'.min.js')) {
                    try {
                        load_libs('uglify');
                        uglify_js($file.'.js');

                    }catch(Exception $e) {
                        /*
                         * Minify process failed. Notify and fall back on a plain
                         * copy
                         */
                        notify($e);
                        copy($file.'.js', $file.'.min.js');
                    }
                }

                /*
                 * Add the file to the html javascript load list
                 */
                html_load_js($base, $script['list']);

                $count++;
                return '';

            }catch(Exception $e) {
                /*
                 * Moving internal javascript to external files failed, notify
                 * developers
                 */
                notify($e);

                /*
                 * Add a <script> element because now we'll include it into the
                 * HTML anyway
                 */
                $return = ' <script type="'.$type.'"'.($extra ? ' '.$extra : '').'>
                            '.$return.'
                        </script>';
            }
        }

        /*
         * Javascript is included into the webpage directly
         *
         * $core->register[script] tags are added all at the end of the page
         * for faster loading
         */
        if (!$script['delayed']) {
            return $return;
        }

        /*
         * If delayed, add it to the footer, else return it directly for
         * inclusion at the point where the html_script() function was
         * called
         */
        if (isset($core->register['script_delayed'])) {
            $core->register['script_delayed'] .= $return;

        } else {
            $core->register['script_delayed']  = $return;
        }

        $count++;
        return '';
    }



    /*
     * Return favicon HTML
     */
    function favicon($icon = null, $mobile_icon = null, $sizes = null, $precomposed = false) {
        array_params($params, 'icon');
        Arrays::default($params, 'mobile_icon', $mobile_icon);
        Arrays::default($params, 'sizes'      , $sizes);
        Arrays::default($params, 'precomposed', $precomposed);

        if (!$params['sizes']) {
            $params['sizes'] = array('');

        } else {
            $params['sizes'] = Arrays::force($params['sizes']);
        }

        foreach ($params['sizes'] as $sizes) {
            if (Core::getCallType('mobile')) {
                if (!$params['mobile_icon']) {
                    $params['mobile_icon'] = cdn_domain('img/mobile/favicon.png');
                }

                return '<link rel="apple-touch-icon'.($params['precomposed'] ? '-precompsed' : '').'"'.($sizes ? ' sizes="'.$sizes.'"' : '').' href="'.$params['mobile_icon'].'" />';

            } else {
                if (empty($params['icon'])) {
                    $params['icon'] = cdn_domain('img/favicon.png');
                }

                return '<link rel="icon" type="image/x-icon"'.($sizes ? ' sizes="'.$sizes.'"' : '').'  href="'.$params['icon'].'" />';
            }
        }
    }



    /*
     * Create HTML for an HTML step process bar
     */
    function list($params, $selected = '') {
        if (!is_array($params)) {
            throw new HtmlException('html_list(): Specified params is not an array', 'invalid');
        }

        if (empty($params['steps']) or !is_array($params['steps'])) {
            throw new HtmlException('html_list(): params[steps] is not specified or not an array', 'invalid');
        }

        Arrays::default($params, 'selected'    , $selected);
        Arrays::default($params, 'class'       , '');
        Arrays::default($params, 'disabled'    , false);
        Arrays::default($params, 'show_counter', false);
        Arrays::default($params, 'use_list'    , true);

        if (!$params['disabled']) {
            if ($params['class']) {
                $params['class'] = Strings::endsWith($params['class'], ' ');
            }

            $params['class'].'hover';
        }

        if ($params['use_list']) {
            $return = '<ul'.($params['class'] ? ' class="'.$params['class'].'"' : '').'>';

        } else {
            $return = '<div'.($params['class'] ? ' class="'.$params['class'].'"' : '').'>';
        }

        /*
         * Get first and last keys.
         */
        end($params['steps']);
        $last  = key($params['steps']);

        reset($params['steps']);
        $first = key($params['steps']);

        $count = 0;

        foreach ($params['steps'] as $name => $data) {
            $count++;

            $class = $params['class'].(($params['selected'] == $name) ? ' selected active' : '');

            if ($name == $first) {
                $class .= ' first';

            } elseif ($name == $last) {
                $class .= ' last';

            } else {
                $class .= ' middle';
            }

            if ($params['show_counter']) {
                $counter = '<strong>'.$count.'.</strong> ';

            } else {
                $counter = '';
            }

            if ($params['use_list']) {
                if ($params['disabled']) {
                    $return .= '<li'.($class ? ' class="'.$class.'"' : '').'><a href="" class="nolink">'.$counter.$data['name'].'</a></li>';

                } else {
                    $return .= '<li'.($class ? ' class="'.$class.'"' : '').'><a href="'.$data['url'].'">'.$counter.$data['name'].'</a></li>';
                }

            } else {
                if ($params['disabled']) {
                    $return .= '<a'.($class ? ' class="nolink'.($class ? ' '.$class : '').'"' : '').'>'.$counter.$data['name'].'</a>';

                } else {
                    $return .= '<a'.($class ? ' class="'.$class.'"' : '').' href="'.$data['url'].'">'.$counter.$data['name'].'</a>';
                }

            }
        }

        if ($params['use_list']) {
            return $return.'</ul>';
        }

        return $return.'</div>';
    }



    /*
     *
     */
    function status_select($params) {
        array_params ($params, 'name');
        Arrays::default($params, 'name'    , 'status');
        Arrays::default($params, 'none'    , '');
        Arrays::default($params, 'resource', false);
        Arrays::default($params, 'selected', '');

        return html_select($params);
    }



    /*
     *
     */
    function hidden($source, $key = 'id') {
        return '<input type="hidden" name="'.$key.'" value="'.isset_get($source[$key]).'">';
    }



    /*
     * Converts the specified src URL by adding the CDN domain if it does not have a domain specified yet. Also converts the image to a different format if configured to do so
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package image
     * @version 2.5.161: Added function and documentation
     *
     * @param string $url The URL for the image
     * @param string
     * @param string
     * @return string The result
     */
    function img_src($src, &$external = null, &$file_src = null, &$original_src = null, $section = 'pub') {
        /*
         * Check if the URL comes from this domain. This info will be needed
         * below
         */
        $external = str_contains($src, '://');

        if ($external) {
// :TODO: This will fail with the dynamic CDN system!
            if (str_contains($src, cdn_domain('', ''))) {
                /*
                 * The src contains the CDN domain
                 */
                $file_part = Strings::startsWith(Strings::from($src, cdn_domain('', '')), '/');
                $external  = false;

                if (substr($file_part, 0, 5) === '/pub/') {
                    $file_src = ROOT.'www/'.LANGUAGE.$file_part;

                } else {
                    $file_src = ROOT.'data/content'.$file_part;
                }

            } elseif (str_contains($src, domain(''))) {
                /*
                 * Here, mistakenly, the main domain was used for CDN data
                 */
                $file_part = Strings::startsWith(Strings::from($src, domain('')), '/');
                $file_src  = ROOT.'data/content'.$file_part;
                $external  = false;

                notify(new HtmlException(tr('html_img(): The main domain ":domain" was specified for CDN data, please correct this issue', array(':domain' => domain(''))), 'warning/invalid'));

            } else {
                $file_src  = $src;
                $external  = true;
            }

        } else {
            /*
             * Assume all images are PUB images
             */
            $file_part = '/pub'.Strings::startsWith($src, '/');
            $file_src  = ROOT.'www/'.LANGUAGE.$file_part;
            $src       = cdn_domain($src, $section);
        }

        /*
         * Check if the image should be auto converted
         */
        $original_src = $file_src;
        $format       = Strings::fromReverse($src, '.');

        if ($format === 'jpeg') {
            $format = 'jpg';
        }

        if (empty($_CONFIG['cdn']['img']['auto_convert'][$format])) {
            /*
             * No auto conversion to be done for this image
             */
            return $src;
        }

        if (!accepts('image/'.$_CONFIG['cdn']['img']['auto_convert'][$format])) {
            /*
             * This browser does not accept the specified image format
             */
            return $src;
        }

        if ($external) {
            /*
             * Download the file locally, convert it, then host it locally
             */
            under_construction();
        }

        /*
         * Automatically convert the image to the specified format for
         * automatically optimized images
         */
        $target_part = Strings::untilReverse($file_part, '.').'.'.$_CONFIG['cdn']['img']['auto_convert'][$format];
        $target      = Strings::untilReverse($file_src , '.').'.'.$_CONFIG['cdn']['img']['auto_convert'][$format];

        log_file(tr('Automatically converting ":format" format image ":src" to format ":target"', array(':format' => $format, ':src' => $file_src, ':target' => $_CONFIG['cdn']['img']['auto_convert'][$format])), 'html', 'VERBOSE/cyan');

        try {
            if (!file_exists($target)) {
                log_file(tr('Modified format target ":target" does not exist, converting original source', array(':target' => $target)), 'html', 'VERYVERBOSE/warning');
                load_libs('image');

                File::executeMode(dirname($file_src), 0770, function() use ($file_src, $target, $format) {
                    File::executeMode($file_src, 0660, function() use ($file_src, $target, $format) {
                        global $_CONFIG;

                        image_convert(array('method' => 'custom',
                            'source' => $file_src,
                            'target' => $target,
                            'format' => $_CONFIG['cdn']['img']['auto_convert'][$format]));
                    });
                });
            }

            /*
             * Convert src back to URL again
             */
            $file_src = $target;
            $src      = cdn_domain($target_part, '');

        }catch(Exception $e) {
            /*
             * Failed to upgrade image. Use the original image
             */
            $e->makeWarning(true);
            $e->addMessages(tr('html_img_src(): Failed to auto convert image ":src" to format ":format". Leaving image as-is', array(':src' => $src, ':format' => $_CONFIG['cdn']['img']['auto_convert'][$format])));
            notify($e);
        }

        return $src;
    }



    /*
     * Create and return an img tag that contains at the least src, alt, height and width
     * If height / width are not specified, then html_img() will try to get the height / width
     * data itself, and store that data in database for future reference
     */
    function img($params, $alt = null, $width = null, $height = null, $extra = '') {
        static $images, $cache = array();

// :LEGACY: The following code block exists to support legacy apps that still use 5 arguments for html_img() instead of a params array
        if (!is_array($params)) {
            /*
             * Ensure we have a params array
             */
            $params = array('src'    => $params,
                'alt'    => $alt,
                'width'  => $width,
                'height' => $height,
                'lazy'   => null,
                'extra'  => $extra);
        }

        array_ensure ($params, 'src,alt,width,height,class,extra');
        Arrays::default($params, 'lazy'   , $_CONFIG['cdn']['img']['lazy_load']);
        Arrays::default($params, 'tag'    , 'img');
        Arrays::default($params, 'section', 'pub');

        if (!$params['src']) {
            /*
             * No image at all?
             */
            if (Debug::production()) {
                /*
                 * On production, just notify and ignore
                 */
                notify(array('code'    => 'not-specified',
                    'groups'  => 'developers',
                    'title'   => tr('No image src specified'),
                    'message' => tr('html_img(): No src for image with alt text ":alt"', array(':alt' => $params['alt']))));
                return '';
            }

            throw new HtmlException(tr('html_img(): No src for image with alt text ":alt"', array(':alt' => $params['alt'])), 'no-image');
        }

        if (!Debug::production()) {
            if (!$params['src']) {
                throw new HtmlException(tr('html_img(): No image src specified'), 'not-specified');
            }

            if (!$params['alt']) {
                throw new HtmlException(tr('html_img(): No image alt text specified for src ":src"', array(':src' => $params['src'])), 'not-specified');
            }

        } else {
            if (!$params['src']) {
                notify(array('code'   => 'not-specified',
                    'groups' => 'developers',
                    'title'  => tr('html_img(): No image src specified')));
            }

            if (!$params['alt']) {
                notify(array('code'    => 'not-specified',
                    'groups'  => 'developers',
                    'title'   => tr('No image alt specified'),
                    'message' => tr('html_img(): No image alt text specified for src ":src"', array(':src' => $params['src']))));
            }
        }

        /*
         * Correct the src parameter if it doesn't contain a domain yet by
         * adding the CDN domain
         *
         * Also check if the file should be automatically converted to a
         * different format
         */
        $params['src'] = html_img_src($params['src'], $external, $file_src, $original_src, $params['section']);

        /*
         * Atumatically detect width / height of this image, as it is not
         * specified
         */
        try {
// :TODO: Add support for memcached
            if (isset($cache[$params['src']])) {
                $image = $cache[$params['src']];

            } else {
                $image = sql_get('SELECT `width`,
                                     `height`

                              FROM   `html_img_cache`

                              WHERE  `url`       = :url
                              AND    `createdon` > NOW() - INTERVAL 1 DAY
                              AND    `status`    IS NULL',

                    array(':url' => $params['src']));

                if ($image) {
                    /*
                     * Database cache found, add it to local cache
                     */
                    $cache[$params['src']] = array('width'  => $image['width'],
                        'height' => $image['height']);

                }
            }

        }catch(Exception $e) {
            notify($e);
            $image = null;
        }

        if (!$image) {
            try {
                /*
                 * Check if the URL comes from this domain (so we can
                 * analyze the files directly on this server) or a remote
                 * domain (we have to download the files first to analyze
                 * them)
                 */
                if ($external) {
                    /*
                     * Image comes from a domain, fetch to temp directory to analize
                     */
                    try {
                        $file  = file_move_to_target($file_src, TMP, false, true);
                        $image = getimagesize(TMP.$file);

                    }catch(Exception $e) {
                        switch ($e->getCode()) {
                            case 404:
                                log_file(tr('html_img(): Specified image ":src" does not exist', array(':src' => $file_src)));
                                break;

                            case 403:
                                log_file(tr('html_img(): Specified image ":src" got access denied', array(':src' => $file_src)));
                                break;

                            default:
                                log_file(tr('html_img(): Specified image ":src" got error ":e"', array(':src' => $file_src, ':e' => $e->getMessage())));
                                throw $e->makeWarning(true);
                        }

                        /*
                         * Image doesnt exist
                         */
                        notify(array('code'    => 'not-exists',
                            'groups'  => 'developers',
                            'title'   => tr('Image does not exist'),
                            'message' => tr('html_img(): Specified image ":src" does not exist', array(':src' => $file_src))));

                        $image[0] = 0;
                        $image[1] = 0;
                    }

                    if (!empty($file)) {
                        file_delete(TMP.$file);
                    }

                } else {
                    /*
                     * Local image. Analize directly
                     */
                    if (file_exists($file_src)) {
                        try {
                            $image = getimagesize($file_src);

                        }catch(Exception $e) {
                            switch ($e->getCode()) {
                                case 404:
                                    log_file(tr('html_img(): Specified image ":src" does not exist', array(':src' => $file_src)));
                                    break;

                                case 403:
                                    log_file(tr('html_img(): Specified image ":src" got access denied', array(':src' => $file_src)));
                                    break;

                                default:
                                    log_file(tr('html_img(): Specified image ":src" got error ":e"', array(':src' => $file_src, ':e' => $e->getMessage())));
                                    throw $e->makeWarning(true);
                            }

                            /*
                             * Image doesnt exist
                             */
                            notify(array('code'    => 'not-exists',
                                'groups'  => 'developers',
                                'title'   => tr('Image does not exist'),
                                'message' => tr('html_img(): Specified image ":src" does not exist', array(':src' => $file_src))));

                            $image[0] = 0;
                            $image[1] = 0;
                        }

                    } else {
                        /*
                         * Image doesn't exist.
                         */
                        log_console(tr('html_img(): Can not analyze image ":src", the local path ":path" does not exist', array(':src' => $params['src'], ':path' => $file_src)), 'yellow');
                        $image[0] = 0;
                        $image[1] = 0;
                    }
                }

                $image['width']  = $image[0];
                $image['height'] = $image[1];
                $status          = null;

            }catch(Exception $e) {
                notify($e);

                $image['width']  = 0;
                $image['height'] = 0;
                $status          = $e->getCode();
            }

            if (!$image['height'] or !$image['width']) {
                log_console(tr('html_img(): image ":src" has invalid dimensions with width ":width" and height ":height"', array(':src' => $params['src'], ':width' => $image['width'], ':height' => $image['height'])), 'yellow');

            } else {
                try {
                    /*
                     * Store image info in local and db cache
                     */
// :TODO: Add support for memcached
                    $cache[$params['src']] = array('width'  => $image['width'],
                        'height' => $image['height']);

                    sql_query('INSERT INTO `html_img_cache` (`status`, `url`, `width`, `height`)
                           VALUES                       (:status , :url , :width , :height )

                           ON DUPLICATE KEY UPDATE `status`    = NULL,
                                                   `createdon` = NOW()',

                        array(':url'    => $params['src'],
                            ':width'  => $image['width'],
                            ':height' => $image['height'],
                            ':status' => $status));

                }catch(Exception $e) {
                    notify($e);
                }
            }
        }

        if (!$params['width'] or !$params['height']) {
            /*
             * Use image width and height
             */
            $params['width']  = $image['width'];
            $params['height'] = $image['height'];

        } else {
            /*
             * Is the image width and or height larger than specified? If so,
             * auto rescale!
             */
            if (!is_numeric($params['width']) and ($params['width'] > 0)) {
                if (!$image['width']) {
                    notify(new HtmlException(tr('Detected invalid "width" parameter specification for image ":src", and failed to get real image width too, ignoring "width" attribute', array(':width' => $params['width'], ':src' => $params['src'])), 'warning/invalid'));
                    $params['width'] = null;

                } else {
                    notify(new HtmlException(tr('Detected invalid "width" parameter specification for image ":src", forcing real image width ":real" instead', array(':width' => $params['width'], ':real' => $image['width'], ':src' => $params['src'])), 'warning/invalid'));
                    $params['width'] = $image['width'];
                }
            }

            if (!is_numeric($params['height']) and ($params['height'] > 0)) {
                if (!$image['height']) {
                    notify(new HtmlException(tr('Detected invalid "height" parameter specification for image ":src", and failed to get real image height too, ignoring "height" attribute', array(':height' => $params['height'], ':src' => $params['src'])), 'warning/invalid'));
                    $params['height'] = null;

                } else {
                    notify(new HtmlException(tr('Detected invalid "height" parameter specification for image ":src", forcing real image height ":real" instead', array(':height' => $params['height'], ':real' => $image['height'], ':src' => $params['src'])), 'warning/invalid'));
                    $params['height'] = $image['height'];
                }
            }

            /*
             * If the image is not an external image, and we have a specified
             * width and height for the image, and we should auto resize then
             * check if the real image dimensions fall within the specified
             * dimensions. If not, automatically resize the image
             */
            if ($_CONFIG['cdn']['img']['auto_resize'] and !$external and $params['width'] and $params['height']) {
                if (($image['width'] > $params['width']) or ($image['height'] > $params['height'])) {
                    log_file(tr('Image src ":src" is larger than its specification, sending resized image instead', array(':src' => $params['src'])), 'html', 'warning');

                    /*
                     * Determine the resize dimensions
                     */
                    if (!$params['height']) {
                        $params['height'] = $image['height'];
                    }

                    if (!$params['width']) {
                        $params['width']  = $image['width'];
                    }

                    /*
                     * Determine the file target name and src
                     */
                    if (str_contains($params['src'], '@2x')) {
                        $pre    = Strings::until($params['src'], '@2x');
                        $post   = str_from ($params['src'], '@2x');
                        $target = $pre.'@'.$params['width'].'x'.$params['height'].'@2x'.$post;

                        $pre         = Strings::until($file_src, '@2x');
                        $post        = str_from ($file_src, '@2x');
                        $file_target = $pre.'@'.$params['width'].'x'.$params['height'].'@2x'.$post;

                    } else {
                        $pre    = Strings::untilReverse($params['src'], '.');
                        $post   = str_rfrom ($params['src'], '.');
                        $target = $pre.'@'.$params['width'].'x'.$params['height'].'.'.$post;

                        $pre         = Strings::untilReverse($file_src, '.');
                        $post        = str_rfrom ($file_src, '.');
                        $file_target = $pre.'@'.$params['width'].'x'.$params['height'].'.'.$post;
                    }

                    /*
                     * Resize or do we have a cached version?
                     */
                    try {
                        if (!file_exists($file_target)) {
                            log_file(tr('Resized version of ":src" does not yet exist, converting', array(':src' => $params['src'])), 'html', 'VERBOSE/cyan');
                            load_libs('image');

                            File::executeMode(dirname($file_src), 0770, function() use ($file_src, $file_target, $params) {
                                global $_CONFIG;

                                image_convert(array('method' => 'resize',
                                    'source' => $file_src,
                                    'target' => $file_target,
                                    'x'      => $params['width'],
                                    'y'      => $params['height']));
                            });
                        }

                        /*
                         * Convert src to the resized target
                         */
                        $params['src'] = $target;
                        $file_src      = $file_target;

                    }catch(Exception $e) {
                        /*
                         * Failed to auto resize the image. Notify and stay with
                         * the current version meanwhile.
                         */
                        $e->addMessages(tr('html_img(): Failed to auto resize image ":image", using non resized image with incorrect width / height instead', array(':image' => $file_src)));
                        notify($e->makeWarning(true));
                    }
                }
            }
        }

        if ($params['height']) {
            $params['height'] = ' height="'.$params['height'].'"';

        } else {
            $params['height'] = '';
        }

        if ($params['width']) {
            $params['width'] = ' width="'.$params['width'].'"';

        } else {
            $params['width'] = '';
        }

        if (isset($params['style'])) {
            $params['extra'] .= ' style="'.$params['style'].'"';
        }

        if (isset($params['class'])) {
            $params['extra'] .= ' class="'.$params['class'].'"';
        }

        if ($params['lazy']) {
            if ($params['extra']) {
                if (str_contains($params['extra'], 'class="')) {
                    /*
                     * Add lazy class to the class definition in "extra"
                     */
                    $params['extra'] = str_replace('class="', 'class="lazy ', $params['extra']);

                } else {
                    /*
                     * Add class definition with "lazy" to extra
                     */
                    $params['extra'] = ' class="lazy" '.$params['extra'];
                }

            } else {
                /*
                 * Set "extra" to be class definition with "lazy"
                 */
                $params['extra'] = ' class="lazy"';
            }

            $html = '';

            if (empty($core->register['lazy_img'])) {
                /*
                 * Use lazy image loading
                 */
                try {
                    if (!file_exists(ROOT.'www/'.LANGUAGE.'/pub/js/jquery.lazy/jquery.lazy.js')) {
                        /*
                         * jquery.lazy is not available, auto install it.
                         */
                        $file = download('https://github.com/eisbehr-/jquery.lazy/archive/master.zip');
                        $path = cli_unzip($file);

                        File::executeMode(ROOT.'www/en/pub/js', 0770, function() use ($path) {
                            file_delete(ROOT.'www/'.LANGUAGE.'/pub/js/jquery.lazy/', ROOT.'www/'.LANGUAGE.'/pub/js/');
                            rename($path.'jquery.lazy-master/', ROOT.'www/'.LANGUAGE.'/pub/js/jquery.lazy');
                        });

                        file_delete($path);
                    }

                    html_load_js('jquery.lazy/jquery.lazy');
                    load_config('lazy_img');

                    /*
                     * Build jquery.lazy options
                     */
                    $options = array();

                    foreach ($_CONFIG['lazy_img'] as $key => $value) {
                        if ($value === null) {
                            continue;
                        }

                        switch ($key) {
                            /*
                             * Booleans
                             */
                            case 'auto_destroy':
                                // no-break
                            case 'chainable':
                                // no-break
                            case 'combined':
                                // no-break
                            case 'enable_throttle':
                                // no-break
                            case 'visible_only':
                                // no-break

                                /*
                                 * Numbers
                                 */
                            case 'delay':
                                // no-break
                            case 'effect_time':
                                // no-break
                            case 'threshold':
                                // no-break
                            case 'throttle':
                                /*
                                 * All these need no quotes
                                 */
                                $options[str_underscore_to_camelcase($key)] = $value;
                                break;

                            /*
                             * Callbacks
                             */
                            case 'after_load':
                                // no-break
                            case 'on_load':
                                // no-break
                            case 'before_load':
                                // no-break
                            case 'on_error':
                                // no-break
                            case 'on_finished_all':
                                /*
                                 * All these need no quotes
                                 */
                                $options[str_underscore_to_camelcase($key)] = 'function(e) {'.$value.'}';
                                break;

                            /*
                             * Strings
                             */
                            case 'append_scroll':
                                // no-break
                            case 'bind':
                                // no-break
                            case 'default_image':
                                // no-break
                            case 'effect':
                                // no-break
                            case 'image_base':
                                // no-break
                            case 'name':
                                // no-break
                            case 'placeholder':
                                // no-break
                            case 'retina_attribute':
                                // no-break
                            case 'scroll_direction':
                                /*
                                 * All these need quotes
                                 */
                                $options[str_underscore_to_camelcase($key)] = '"'.$value.'"';
                                break;

                            default:
                                throw new HtmlException(tr('html_img(): Unknown lazy_img option ":key" specified. Please check the $_CONFIG[lazy_img] configuration!', array(':key' => $key)), 'unknown');
                        }
                    }

                    $core->register['lazy_img'] = true;
                    $html .= html_script(array('event'  => 'function',
                        'script' => '$(".lazy").Lazy({'.array_implode_with_keys($options, ',', ':').'});'));

                }catch(Exception $e) {
                    /*
                     * Oops, jquery.lazy failed to install or load. Notify, and
                     * ignore, we will just continue without lazy loading.
                     */
                    notify(new HtmlException(tr('html_img(): Failed to install or load jquery.lazy'), $e));
                }
            }

            $html .= '<'.$params['tag'].' data-src="'.$params['src'].'" alt="'.htmlentities($params['alt']).'"'.$params['width'].$params['height'].$params['extra'].'>';

            return $html;
        }

        return '<'.$params['tag'].' src="'.$params['src'].'" alt="'.htmlentities($params['alt']).'"'.$params['width'].$params['height'].$params['extra'].'>';
    }



    /*
     * Create and return a video container that has at the least src, alt, height and width
     */
    function video($params) {
        Arrays::ensure($params, 'src,width,height,more,type');
        Arrays::default($params, 'controls', true);

        if (!Debug::production()) {
            if (!$params['src']) {
                throw new HtmlException(tr('html_video(): No video src specified'), 'not-specified');
            }
        }

// :INVESTIGATE: Is better getting default width and height dimensions like in html_img()
// But in this case, we have to use a external "library" to get this done
// Investigate the best option for this!
        if (!$params['width']) {
            throw new HtmlException(tr('html_video(): No width specified'), 'not-specified');
        }

        if (!is_natural($params['width'])) {
            throw new HtmlException(tr('html_video(): Invalid width ":width" specified', array(':width' => $params['width'])), 'invalid');
        }

        if (!$params['height']) {
            throw new HtmlException(tr('html_video(): No height specified'), 'not-specified');
        }

        if (!is_natural($params['height'])) {
            throw new HtmlException(tr('html_video(): Invalid height ":height" specified', array(':height' => $params['height'])), 'invalid');
        }

        /*
         * Videos can be either local or remote
         * Local videos either have http://thisdomain.com/video, https://thisdomain.com/video, or /video
         * Remote videos must have width and height specified
         */
        if (substr($params['src'], 0, 7) == 'http://') {
            $protocol = 'http';

        } elseif ($protocol = substr($params['src'], 0, 8) == 'https://') {
            $protocol = 'https';

        } else {
            $protocol = '';
        }

        if (!$protocol) {
            /*
             * This is a local video
             */
            $params['src']  = ROOT.'www/en'.Strings::startsWith($params['src'], '/');
            $params['type'] = mime_content_type($params['src']);

        } else {
            if (preg_match('/^'.str_replace('/', '\/', str_replace('.', '\.', domain())).'\/.+$/ius', $params['src'])) {
                /*
                 * This is a local video with domain specification
                 */
                $params['src']  = ROOT.'www/en'.Strings::startsWith(Strings::from($params['src'], domain()), '/');
                $params['type'] = mime_content_type($params['src']);

            } elseif (!Debug::production()) {
                /*
                 * This is a remote video
                 * Remote videos MUST have height and width specified!
                 */
                if (!$params['height']) {
                    throw new HtmlException(tr('html_video(): No height specified for remote video'), 'not-specified');
                }

                if (!$params['width']) {
                    throw new HtmlException(tr('html_video(): No width specified for remote video'), 'not-specified');
                }

                switch ($params['type']) {
                    case 'mp4':
                        $params['type'] = 'video/mp4';
                        break;

                    case 'flv':
                        $params['type'] = 'video/flv';
                        break;

                    case '':
                        /*
                         * Try to autodetect
                         */
                        $params['type'] = 'video/'.Strings::fromReverse($params['src'], '.');
                        break;

                    default:
                        throw new HtmlException(tr('html_video(): Unknown type ":type" specified for remote video', array(':type' => $params['type'])), 'unknown');
                }
            }
        }

        /*
         * Build HTML
         */
        $html = '   <video width="'.$params['width'].'" height="'.$params['height'].'" '.($params['controls'] ? 'controls ' : '').''.($params['more'] ? ' '.$params['more'] : '').'>
                    <source src="'.$params['src'].'" type="'.$params['type'].'">
                </video>';

        return $html;
    }



    /*
     *
     */
    function autosuggest($params) {
        static $sent = array();

        Arrays::ensure($params);
        Arrays::default($params, 'class'          , '');
        Arrays::default($params, 'input_class'    , 'form-control');
        Arrays::default($params, 'name'           , '');
        Arrays::default($params, 'id'             , $params['name']);
        Arrays::default($params, 'placeholder'    , '');
        Arrays::default($params, 'required'       , false);
        Arrays::default($params, 'tabindex'       , html_tabindex());
        Arrays::default($params, 'extra'          , '');
        Arrays::default($params, 'value'          , '');
        Arrays::default($params, 'source'         , '');
        Arrays::default($params, 'maxlength'      , '');
        Arrays::default($params, 'filter_selector', '');
        Arrays::default($params, 'selector'       , 'form.autosuggest');

        $return = ' <div class="autosuggest'.($params['class'] ? ' '.$params['class'] : '').'">
                    <input autocomplete="new_password" spellcheck="false" role="combobox" dir="ltr" tabindex="'.$params['tabindex'].'" '.($params['input_class'] ? 'class="'.$params['input_class'].'" ' : '').'type="text" name="'.$params['name'].'" id="'.$params['id'].'" placeholder="'.$params['placeholder'].'" data-source="'.$params['source'].'" value="'.$params['value'].'"'.($params['filter_selector'] ? ' data-filter-selector="'.$params['filter_selector'].'"' : '').($params['maxlength'] ? ' maxlength="'.$params['maxlength'].'"' : '').($params['extra'] ? ' '.$params['extra'] : '').($params['required'] ? ' required' : '').'>
                    <ul>
                    </ul>
                </div>';

        if (empty($sent[$params['selector']])) {
            /*
             * Add only one autosuggest start per selector
             */
            $sent[$params['selector']] = true;
            $return                   .= html_script('$("'.$params['selector'].'").autosuggest();');
        }

        html_load_js('base/autosuggest');

        return $return;
    }



    /*
     * This function will minify the given HTML by removing double spaces, and strip white spaces before and after tags (except space)
     * Found on http://stackoverflow.com/questions/6225351/how-to-minify-php-page-html-output, rewritten for use in base project
     */
    function minify($html) {
        if ($_CONFIG['cdn']['min']) {
            return Minify::html($html);
        }

        /*
         * Don't do anything. This way, on non debug systems, where this is
         * used to minify HTML output, we can still see normal HTML that is
         * a bit more readable.
         */
        return $html;
    }



    /*
     * Generate and return a randon name for the specified $name, and store the
     * link between the two under "group"
     */
    function translate($name) {
        static $translations = array();

        if (!isset($translations[$name])) {
            $translations[$name] = '__HT'.$name.'__'.substr(unique_code('sha256'), 0, 16);
        }

        return $translations[$name];
    }



    /*
     * Return the $_POST value for the translated specified key
     */
    function untranslate() {
        $count = 0;

        foreach ($_POST as $key => $value) {
            if (substr($key, 0, 4) == '__HT') {
                $_POST[Strings::until(substr($key, 4), '__')] = $_POST[$key];
                unset($_POST[$key]);
                $count++;
            }
        }

        return $count;
    }



    /*
     * Ensure that missing checkbox values are restored automatically (Seriously, sometimes web design is tiring...)
     *
     * This function works by assuming that each checkbox with name NAME has a hidden field with name _NAME. If NAME is missing, _NAME will be moved to NAME
     *
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     *
     * @return void
     */
    function fix_checkbox_values() {
        foreach ($_POST as $key => $value) {
            if (substr($key, 0, 4) === '__CB') {
                if (!array_key_exists(substr($key, 4), $_POST)) {
                    $_POST[substr($key, 4)] = $value;
                }

                unset($_POST[$key]);
            }
        }
    }



    /*
     * Returns an HTML <form> tag with (if configured so) a hidden CSRF variable
     * attached
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     *
     * @param params $param The form parameters
     * @param string $param[action] The URL where the post should be sent to
     * @param string $param[method] The HTTP method to be used. Should be either get or post.
     * @param string $param[id] The id attribute of the form
     * @param string $param[name] The name attribute of the form
     * @param string $param[class] Any class data to be added to the form
     * @param string $param[extra] Any extra attributes to be added. Can be a complete string like 'data="blah" foo="bar"'
     * @param boolean $param[csrf] If set to true, the form will include a hidden Cross Site Request Forgery protection input. Defaults to $_CONFIG[security][csrf][enabled]
     * @return string the HTML <form> tag
     */
    function form($params = null) {
        global $_CONFIG;

        Arrays::ensure($params, 'extra');
        Arrays::default($params, 'id'    , 'form');
        Arrays::default($params, 'name'  , $params['id']);
        Arrays::default($params, 'method', 'post');
        Arrays::default($params, 'action', domain(true));
        Arrays::default($params, 'class' , 'form-horizontal');
        Arrays::default($params, 'csrf'  , $_CONFIG['security']['csrf']['enabled']);

        foreach (array('id', 'name', 'method', 'action', 'class', 'extra') as $key) {
            if (!$params[$key]) continue;

            if ($params[$key] == 'extra') {
                $attributes[] = $params[$key];

            } else {
                $attributes[] = $key.'="'.$params[$key].'"';
            }
        }

        $form = '<form '.implode(' ', $attributes).'>';

        if ($params['csrf']) {
            $csrf  = set_csrf();
            $form .= '<input type="hidden" name="csrf" value="'.$csrf.'">';
        }

        return $form;
    }



    /*
     * Returns the current global tabindex and automatically increases it
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     *
     * @return natural The current tab index
     */
    function tabindex() {
        return ++$core->register['tabindex'];
    }



    /*
     * Set the base URL for CDN requests from javascript
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     *
     * @return void()
     */
    function set_js_cdn_url() {
        $core->register['header'] = html_script('var cdnprefix="'.cdn_domain().'"; var site_prefix="'.domain().'";', false);
    }



    /*
     * Filter the specified tags from the specified HTML
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @version 2.5.0: Added function and documentation

     * @param string $html
     * @param string array $tags
     * @param boolean $exception
     * @return string The result
     */
    function filter_tags($html, $tags, $exception = false) {
        $list = array();
        $tags = Arrays::force($tags);
        $dom  = new DOMDocument();

        $dom->loadHTML($html);

        foreach ($tags as $tag) {
            $elements = $dom->getElementsByTagName($tag);

            /*
             * Generate a list of elements that must be removed
             */
            foreach ($elements as $element) {
                $list[] = $element;
            }
        }

        if ($list) {
            if ($exception) {
                throw new HtmlException('html_filter_tags(): Found HTML tags ":tags" which are forbidden', array(':tags', implode(', ', $list)), 'forbidden');
            }

            foreach ($list as $item) {
                $item->parentNode->removeChild($item);
            }
        }

        $html = $dom->saveHTML();
        return $html;
    }



    /*
     * Returns HTML for a loader screen that will hide the buildup of the web page behind it. Once the page is loaded, the loader screen will automatically disappear.
     *
     * This function typically should be executed in the c_page_header() call, and the HTML output of this function should be inserted at the beginning of the HTML that that function generates. This way, the loader screen will be the first thing (right after the <body> tag) that the browser will render, hiding all the other elements that are buiding up.
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @version 2.5.57: Added function and documentation
     * @note: If the page_selector is specified, the loader screen will assume its hidden and try to show it. If it is not specified, the loader screen will assume its visible (but behind the loader screen) and once the page is loaded it will only attempt to hide itself.
     *
     * @param params $params A parameters array
     * @param string $params[page_selector] The selector required to show the main page wrapper, if it is hidden and must be shown when the loader screen is hidden
     * @param string $params[image_src] The src for the image to be displayed on the loader screen
     * @param string $params[image_alt] The alt text for the loader image
     * @param string $params[image_width] The required width for the loader image
     * @param string $params[image_height] The required height for the loader image
     * @param string $params[transition_time] The time in msec that the loader screen transition should take until the web page itself is visible
     * @param string $params[transition_style] The style of the transition from loader screen to webpage that should be used
     * @param string $params[screen_line_height] The "line-height" setting for the loader screen style attribute
     * @param string $params[screen_background] The "background" setting for the loader screen style attribute
     * @param string $params[screen_text_align] The "text-align" setting for the loader screen style attribute
     * @param string $params[screen_vertical_align] The "vertical-align" setting for the loader screen style attribute
     * @param string $params[screen_style_extra] If specified, the entire string will be added in the style="" attribute
     * @param string $params[test_loader_screen] If set to true, the loader screen will not hide and be removed, instead it will show indefinitely so that the contents can be checked and tested
     * @return string The HTML for the loader screen.
     */
    function loader_screen($params) {
        array_params($params);
        Arrays::default($params, 'page_selector'        , '');
        Arrays::default($params, 'text'                 , '');
        Arrays::default($params, 'text_style'           , '');
        Arrays::default($params, 'image_src'            , '');
        Arrays::default($params, 'image_alt'            , tr('Loader screen'));
        Arrays::default($params, 'image_width'          , null);
        Arrays::default($params, 'image_height'         , null);
        Arrays::default($params, 'image_top'            , '100px');
        Arrays::default($params, 'image_left'           , null);
        Arrays::default($params, 'image_right'          , null);
        Arrays::default($params, 'image_bottom'         , null);
        Arrays::default($params, 'image_style'          , 'position:relative;');
        Arrays::default($params, 'screen_line_height'   , 0);
        Arrays::default($params, 'screen_background'    , 'white');
        Arrays::default($params, 'screen_color'         , 'black');
        Arrays::default($params, 'screen_remove'        , true);
        Arrays::default($params, 'screen_text_align'    , 'center');
        Arrays::default($params, 'screen_vertical_align', 'middle');
        Arrays::default($params, 'screen_style_extra'   , '');
        Arrays::default($params, 'transition_time'      , 300);
        Arrays::default($params, 'transition_style'     , 'fade');
        Arrays::default($params, 'test_loader_screen'   , false);

        $extra = '';

        if ($params['screen_line_height']) {
            $extra .= 'line-height:'.$params['screen_line_height'].';';
        }

        if ($params['screen_vertical_align']) {
            $extra .= 'vertical-align:'.$params['screen_vertical_align'].';';
        }

        if ($params['screen_text_align']) {
            $extra .= 'text-align:'.$params['screen_text_align'].';';
        }

        $html  = '  <div id="loader-screen" style="position:fixed;top:0px;bottom:0px;left:0px;right:0px;z-index:2147483647;display:block;background:'.$params['screen_background'].';color: '.$params['screen_color'].';text-align: '.$params['screen_text_align'].';'.$extra.'" '.$params['screen_style_extra'].'>';

        /*
         * Show loading text
         */
        if ($params['text']) {
            $html .=    '<div style="'.$params['text_style'].'">
                     '.$params['text'].'
                     </div>';
        }

        /*
         * Show loading image
         */
        if ($params['image_src']) {
            if ($params['image_top']) {
                $params['image_style'] .= 'top:'.$params['image_top'].';';
            }

            if ($params['image_left']) {
                $params['image_style'] .= 'left:'.$params['image_left'].';';
            }

            if ($params['image_right']) {
                $params['image_style'] .= 'right:'.$params['image_right'].';';
            }

            if ($params['image_bottom']) {
                $params['image_style'] .= 'bottom:'.$params['image_bottom'].';';
            }

            $html .=    html_img(array('src'    => $params['image_src'],
                'alt'    => $params['image_alt'],
                'lazy'   => false,
                'width'  => $params['image_width'],
                'height' => $params['image_height'],
                'style'  => $params['image_style']));
        }

        $html .= '  </div>';

        if (!$params['test_loader_screen']) {
            switch ($params['transition_style']) {
                case 'fade':
                    if ($params['page_selector']) {
                        /*
                         * Hide the loader screen and show the main page wrapper
                         */
                        $html .= html_script('$("'.$params['page_selector'].'").show('.$params['transition_time'].');
                                          $("#loader-screen").fadeOut('.$params['transition_time'].', function() { $("#loader-screen").css("display", "none"); '.($params['screen_remove'] ? '$("#loader-screen").remove();' : '').' });');

                        return $html;
                    }

                    /*
                     * Only hide the loader screen
                     */
                    $html .= html_script('$("#loader-screen").fadeOut('.$params['transition_time'].', function() { $("#loader-screen").css("display", "none"); '.($params['screen_remove'] ? '$("#loader-screen").remove();' : '').' });');
                    break;

                case 'slide':
                    $html .= html_script('var height = $("#loader-screen").height(); $("#loader-screen").animate({ top: height }, '.$params['transition_time'].', function() { $("#loader-screen").css("display", "none"); '.($params['screen_remove'] ? '$("#loader-screen").remove();' : '').' });');
                    break;

                default:
                    throw new HtmlException(tr('html_loader_screen(): Unknown screen transition value ":value" specified', array(':value' => $params['test_loader_screen'])), 'unknown');
            }
        }

        return $html;
    }



    /*
     * Strip tags or attributes from all HTML tags
     *
     * This function will strip all attributes except for those attributes specified in $allowed_attributes
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see strip_tags()
     * @note Requires php-xml package to be installed as it uses the DOMDocument() class
     * @version 2.7.121: Added function and documentation
     *
     * @param string $source The source string to be processed
     * @param list $allowed_attributes The HTML tag attributes that are allowed to remain
     * @return string The source string with all HTML attributes filtered except for those specified in $allowed_attributes
     */
    function strip_attributes($source, $allowed_attributes = null) {
        $allowed_attributes = Arrays::force($allowed_attributes);

        /*
         * If specified source string is empty, then we're done right away
         */
        if (!$source) {
            return '';
        }

        $xml = new DOMDocument();

        if ($xml->loadHTML($source, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
            foreach ($xml->getElementsByTagName("*") as $tag) {
                /*
                 * Filter attributes
                 */
                foreach ($tag->attributes as $attr) {
                    if (!in_array($attr->nodeName, $allowed_attributes)) {
                        $tag->removeAttribute($attr->nodeName);
                    }
                }
            }
        }

        return $xml->saveHTML();
    }
}