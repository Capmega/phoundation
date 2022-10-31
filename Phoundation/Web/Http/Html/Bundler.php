<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Filesystem\File;
use Phoundation\Notifications\Notification;
use Throwable;



/**
 * Class Bundler
 *
 * This class contains the bundler functions which can bundle JS and CSS files into one
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Bundler
{
    /**
     * Singleton variable
     *
     * @var Bundler|null $instance
     */
    protected static ?Bundler $instance = null;

    /**
     * The path where the bundled file should be stored
     *
     * @var string $path
     */
    protected static string $path = '';

    /**
     * The extension to be used for the bundle file
     *
     * @var string $extension
     */
    protected static string $extension = '';

    /**
     * The name of the bundle file
     *
     * @var string $bundle_file
     */
    protected static string $bundle_file = '';

    /**
     * The number of files in this bundle
     *
     * @var int $count
     */
    protected static int $count = 0;



    /**
     * Bundler constructor
     */
    protected function __construct()
    {
        $admin_path      = (Core::getCallType('admin') ? 'admin/' : '');
        self::$extension = (Config::get('web.minify', true)   ? '.min.'.$extension : '.'.$extension);
        $bundle      =  Strings::force(array_keys($core->register[$list]));
        $bundle      =  substr(sha1($bundle.Core::FRAMEWORKCODEVERSION.PROJECTCODEVERSION), 1, 16);
        $path        =  PATH_CDN . LANGUAGE.'/' . $admin_path . 'pub/' . $extension . '/';
        $bundle_file =  $path.'bundle-'.$bundle.$ext;
        $file_count  =  0;
    }



    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @return Bundler
     */
    public static function getInstance(): Bundler
    {
        if (!isset(self::$instance)) {
            self::$instance = new Log($global_id);

            // Log class startup message
            if (Debug::enabled()) {
                self::information(tr('Logger started with debug enabled, log threshold set to ":threshold"', [':threshold' => self::$threshold]));
            }
        }
    }



    /**
     * Bundle multiple javascript files into one
     *
     * @param array $files
     * @return string|null
     */
    public function js(array $files): ?string
    {
        if (!Config::get('web.bundle', true)) {
            // Bundler has been disabled
            return null;
        }

        $extension = 'js';

        /*
         * Prepare bundle information. The bundle file name will be a hash of the bundle file names and the framework
         * and project code versions. This way, if the framework version or code version get bumped up, the bundle
         * filename will be different, avoiding caching issues. Since the deploy script will automatically bump the
         * project version on deploy, each deploy will cause different bundle filenames. With this we can easily set
         * caching to a year if needed, any updates to CSS or JS will cause the client browser to load the new bundle
         * files.
         */
        $admin_path  = (Core::getCallType('admin') ? 'admin/'           : '');
        $ext         = (Config::get('web.minify', true)   ? '.min.'.$extension : '.'.$extension);
        $bundle      =  Strings::force(array_keys($core->register[$list]));
        $bundle      =  substr(sha1($bundle.Core::FRAMEWORKCODEVERSION.PROJECTCODEVERSION), 1, 16);
        $path        =  PATH_ROOT.'www/'.LANGUAGE.'/'.$admin_path.'pub/'.$extension.'/';
        $bundle_file =  $path.'bundle-'.$bundle.$ext;
        $file_count  =  0;

        // If we don't find an existing bundle file, then procced with the concatination process
        if (file_exists($bundle_file)) {
            // Ensure file is not 0 bytes. This might be caused due to a number of issues, but mainly due to disk full
            // events. When this happens, the 0 bytes bundle files remain, leaving the site without CSS or JS
            if (!filesize($bundle_file)) {
                Log::warning(tr('Deleting empty bundle file ":file"', [':file' => $bundle_file]));

                File::executeMode(dirname($bundle_file), 0770, function() use ($bundle_file, $list) {
                    File::delete($bundle_file, PATH_ROOT.'www/'.LANGUAGE.'/pub/');
                });

                return Bundler::html($list);
            }

            // Bundle files are essentially cached files. Ensure the cache is not too old
            if ((Config::get('cache.bundler.max-age', 3600) > 60) and (filemtime($bundle_file) + Config::get('cache.bundler.max-age', 3600)) < time()) {
                Log::warning(tr('Deleting expired cached bundle file ":file"', [
                    ':file' => $bundle_file
                ]));

                File::executeMode(dirname($bundle_file), 0770, function() use ($bundle_file, $list) {
                    File::delete($bundle_file, PATH_ROOT.'www/'.LANGUAGE.'/pub/');
                });

                return Bundler::html($list);
            }

            $core->register[$list] = ['bundle-'.$bundle => false];

        } else {
            // Generate new bundle file. This requires the pub/$list path to be writable
            File::executeMode(dirname($bundle_file), 0770, function() use ($list, &$file_count, $path, $ext, $extension, $bundle_file) {
                if (!empty($core->register[$list])) {
                    foreach ($core->register[$list] as $file => $data) {
                        // Check for @imports
                        $orgfile = $file;
                        $file    = $path.$file.$ext;

                        Log::action(tr('Adding file ":file" to bundle file ":bundle"', [
                            ':file'   => $file,
                            ':bundle' => $bundle_file
                        ]));

                        if (!file_exists($file)) {
                            Notification::create()
                                ->setCode('not-exists')
                                ->setGroups('developers')
                                ->setTitle(tr('Bundler file does not exist'))
                                ->setMessage(tr('The requested ":extension" type file ":file" should be bundled but does not exist', [
                                    ':file'      => $file,
                                    ':extension' => $extension
                                ]))
                                ->send();
                            continue;
                        }

                        $file_count++;

                        $data = file_get_contents($file);
                        unset($core->register[$list][$orgfile]);

                        if ($extension === 'css') {
// :TODO: ADD SUPPORT FOR RECURSIVE @IMPORT STATEMENTS!! What if the files that are imported with @import contain @import statements themselves!?!?!?
                            if (preg_match_all('/@import.+?;/', $data, $matches)) {
                                foreach ($matches[0] as $match) {
                                    // Inline replace each @import with the file contents
// :CLEANUP:
//                                if (preg_match('/@import\s?(?:url\()?((?:"?.+?"?)|(?:\'.+?\'))\)?/', $match)) {
                                    if (preg_match('/@import\s"|\'.+?"|\'/', $match)) {
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                                        $import = Strings::cut($match, '"', '"');

                                        if (!file_exists($path.$import)) {
                                            Notification::create()
                                                ->setCode('not-exists')
                                                ->setGroups('developers')
                                                ->setTitle(tr('Bundler file does not exist'))
                                                ->setMessage(tr('The bundler ":extension" file ":import" @imported by file ":file" does not exist', [
                                                    ':file'      => $file,
                                                    ':import'    => $import,
                                                    ':extension' => $extension
                                                ]))
                                                ->send();

                                            $import = '';

                                        } else {
                                            $import = file_get_contents($path.$import);
                                        }

                                    } elseif (preg_match('/@import\surl\(.+?\)/', $match)) {
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                                        // This is an external URL. Get it locally as a temp file, then include
                                        $import = Strings::cut($match, '(', ')');
                                        $import = Strings::slash(dirname($file)).Strings::unslash($import);

                                        if (!file_exists($import)) {
                                            Notification::create()
                                                ->setCode('not-exists')
                                                ->setGroups('developers')
                                                ->setTitle(tr('Bundler file does not exist'))
                                                ->setMessage(tr('The bundler ":extension" file ":import" @imported by file ":file" does not exist', [
                                                    ':file'      => $file,
                                                    ':import'    => $import,
                                                    ':extension' => $extension
                                                ]))
                                                ->send()

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
                                // URL rewriting required, this file is not in /css or /js, and not in a sub dir
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
                                            // This is inline data, nothing we can do so ignore
                                            continue;
                                        }

                                        if (substr($url, 0, 1) == '/') {
                                            // Absolute URL, we can ignore these since they already point towards the
                                            // correct path
                                        }

                                        if (preg_match('/https?:\/\//', $url)) {
                                            // Absolute domain, ignore because we cannot fix anything here
                                            continue;
                                        }

                                        $data = str_replace($url, '"' . str_repeat('../', $count) . $url.'"', $data);
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

            // Only continue here if we actually added anything to the bundle (some bundles may not have anything, like
            // js_header)
            if ($file_count) {
                $bundle = 'bundle-'.$bundle;

                // Purge the file from duplicate content
                if ($list === 'css') {
                    if ($_CONFIG['cdn']['css']['purge']) {
                        try {
                            $html   = File::temp($core->register['html'], 'html');
                            $bundle = Css::purge($html, $bundle);

                            Log::success(tr('Purged not-used CSS rules from bundled file ":file"', [
                                ':file' => $bundle
                            ]));

                            File::delete($html);

                        }catch(Throwable $e) {
                            // The CSS purge failed
                            File::delete($html);
                            Notification::create()
                                ->setException($e->makeWarning())
                                ->send();
                        }
                    }
                }

// :TODO: Add support for individual bundles that require async loading
                $core->register[$list][$bundle] = false;

                if ($_CONFIG['cdn']['enabled']) {
                    Cdn::addFiles($bundle_file);
                }
            }
        }

        return true;
    }



    /**
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
     * @param string $files A list of files that must be bundled
     * @return string|null Returns the path to the bundle file, or NULL if nothing was bundled (also in case of failure)
     */
    public static function css(array $files): ?string
    {
        if (!Config::get('web.bundle', true)) {
            // Bundler has been disabled
            return null;
        }

        if ($list === 'css') {
            $extension = 'css';

        } else {
            $extension = 'js';
        }

        /*
         * Prepare bundle information. The bundle file name will be a hash of the bundle file names and the framework
         * and project code versions. This way, if the framework version or code version get bumped up, the bundle
         * filename will be different, avoiding caching issues. Since the deploy script will automatically bump the
         * project version on deploy, each deploy will cause different bundle filenames. With this we can easily set
         * caching to a year if needed, any updates to CSS or JS will cause the client browser to load the new bundle
         * files.
         */
        $admin_path  = (Core::getCallType('admin') ? 'admin/'           : '');
        $ext         = (Config::get('web.minify', true)   ? '.min.'.$extension : '.'.$extension);
        $bundle      =  Strings::force(array_keys($core->register[$list]));
        $bundle      =  substr(sha1($bundle.Core::FRAMEWORKCODEVERSION.PROJECTCODEVERSION), 1, 16);
        $path        =  PATH_ROOT.'www/'.LANGUAGE.'/'.$admin_path.'pub/'.$extension.'/';
        $bundle_file =  $path.'bundle-'.$bundle.$ext;
        $file_count  =  0;

        // If we don't find an existing bundle file, then procced with the concatination process
        if (file_exists($bundle_file)) {
            // Ensure file is not 0 bytes. This might be caused due to a number of issues, but mainly due to disk full
            // events. When this happens, the 0 bytes bundle files remain, leaving the site without CSS or JS
            if (!filesize($bundle_file)) {
                Log::warning(tr('Deleting empty bundle file ":file"', [':file' => $bundle_file]));

                File::executeMode(dirname($bundle_file), 0770, function() use ($bundle_file, $list) {
                    File::delete($bundle_file, PATH_ROOT.'www/'.LANGUAGE.'/pub/');
                });

                return Bundler::html($list);
            }

            // Bundle files are essentially cached files. Ensure the cache is not too old
            if ((Config::get('cache.bundler.max-age', 3600) > 60) and (filemtime($bundle_file) + Config::get('cache.bundler.max-age', 3600)) < time()) {
                Log::warning(tr('Deleting expired cached bundle file ":file"', [
                    ':file' => $bundle_file
                ]));

                File::executeMode(dirname($bundle_file), 0770, function() use ($bundle_file, $list) {
                    File::delete($bundle_file, PATH_ROOT.'www/'.LANGUAGE.'/pub/');
                });

                return Bundler::html($list);
            }

            $core->register[$list] = ['bundle-'.$bundle => false];

        } else {
            // Generate new bundle file. This requires the pub/$list path to be writable
            File::executeMode(dirname($bundle_file), 0770, function() use ($list, &$file_count, $path, $ext, $extension, $bundle_file) {
                if (!empty($core->register[$list])) {
                    foreach ($core->register[$list] as $file => $data) {
                        // Check for @imports
                        $orgfile = $file;
                        $file    = $path.$file.$ext;

                        Log::action(tr('Adding file ":file" to bundle file ":bundle"', [
                            ':file'   => $file,
                            ':bundle' => $bundle_file
                        ]));

                        if (!file_exists($file)) {
                            Notification::create()
                                ->setCode('not-exists')
                                ->setGroups('developers')
                                ->setTitle(tr('Bundler file does not exist'))
                                ->setMessage(tr('The requested ":extension" type file ":file" should be bundled but does not exist', [
                                    ':file'      => $file,
                                    ':extension' => $extension
                                ]))
                                ->send();
                            continue;
                        }

                        $file_count++;

                        $data = file_get_contents($file);
                        unset($core->register[$list][$orgfile]);

                        if ($extension === 'css') {
// :TODO: ADD SUPPORT FOR RECURSIVE @IMPORT STATEMENTS!! What if the files that are imported with @import contain @import statements themselves!?!?!?
                            if (preg_match_all('/@import.+?;/', $data, $matches)) {
                                foreach ($matches[0] as $match) {
                                    // Inline replace each @import with the file contents
// :CLEANUP:
//                                if (preg_match('/@import\s?(?:url\()?((?:"?.+?"?)|(?:\'.+?\'))\)?/', $match)) {
                                    if (preg_match('/@import\s"|\'.+?"|\'/', $match)) {
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                                        $import = Strings::cut($match, '"', '"');

                                        if (!file_exists($path.$import)) {
                                            Notification::create()
                                                ->setCode('not-exists')
                                                ->setGroups('developers')
                                                ->setTitle(tr('Bundler file does not exist'))
                                                ->setMessage(tr('The bundler ":extension" file ":import" @imported by file ":file" does not exist', [
                                                    ':file'      => $file,
                                                    ':import'    => $import,
                                                    ':extension' => $extension
                                                ]))
                                                ->send();

                                            $import = '';

                                        } else {
                                            $import = file_get_contents($path.$import);
                                        }

                                    } elseif (preg_match('/@import\surl\(.+?\)/', $match)) {
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                                        // This is an external URL. Get it locally as a temp file, then include
                                        $import = Strings::cut($match, '(', ')');
                                        $import = Strings::slash(dirname($file)).Strings::unslash($import);

                                        if (!file_exists($import)) {
                                            Notification::create()
                                                ->setCode('not-exists')
                                                ->setGroups('developers')
                                                ->setTitle(tr('Bundler file does not exist'))
                                                ->setMessage(tr('The bundler ":extension" file ":import" @imported by file ":file" does not exist', [
                                                    ':file'      => $file,
                                                    ':import'    => $import,
                                                    ':extension' => $extension
                                                ]))
                                                ->send()

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
                                // URL rewriting required, this file is not in /css or /js, and not in a sub dir
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
                                            // This is inline data, nothing we can do so ignore
                                            continue;
                                        }

                                        if (substr($url, 0, 1) == '/') {
                                            // Absolute URL, we can ignore these since they already point towards the
                                            // correct path
                                        }

                                        if (preg_match('/https?:\/\//', $url)) {
                                            // Absolute domain, ignore because we cannot fix anything here
                                            continue;
                                        }

                                        $data = str_replace($url, '"' . str_repeat('../', $count) . $url.'"', $data);
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

            // Only continue here if we actually added anything to the bundle (some bundles may not have anything, like
            // js_header)
            if ($file_count) {
                $bundle = 'bundle-'.$bundle;

                // Purge the file from duplicate content
                if ($list === 'css') {
                    if ($_CONFIG['cdn']['css']['purge']) {
                        try {
                            $html   = File::temp($core->register['html'], 'html');
                            $bundle = Css::purge($html, $bundle);

                            Log::success(tr('Purged not-used CSS rules from bundled file ":file"', [
                                ':file' => $bundle
                            ]));

                            File::delete($html);

                        }catch(Throwable $e) {
                            // The CSS purge failed
                            File::delete($html);
                            Notification::create()
                                ->setException($e->makeWarning())
                                ->send();
                        }
                    }
                }

// :TODO: Add support for individual bundles that require async loading
                $core->register[$list][$bundle] = false;

                if ($_CONFIG['cdn']['enabled']) {
                    Cdn::addFiles($bundle_file);
                }
            }
        }

        return true;
    }



    /**
     * Initialize the class to build a new bundle file
     *
     * @param array $files
     * @param string $extension
     * @return void
     */
    protected function newBundle(array $files, string $extension): void
    {
        $admin_path        = (Core::getCallType('admin') ? 'admin/'           : '');
        self::$ext         = (Config::get('web.minify', true)   ? '.min.' . $extension : '.' . $extension);
        self::$bundle      =  Strings::force($files);
        self::$bundle      =  substr(sha1(self::$bundle . Core::FRAMEWORKCODEVERSION), 1, 32);
        self::$path        =  PATH_DATA . 'www/' . LANGUAGE . '/' . $admin_path . 'pub/' . $extension.'/';
        self::$bundle_file =  $path.'bundle-'.$bundle.$ext;
        self::$file_count  =  0;
    }
}