<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Cdn\Cdn;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Filesystem\File;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Page;
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
     * Bundle multiple javascript files into one
     *
     * @param array $files
     * @return string|null
     */
    public static function js(array $files): ?string
    {
        return self::bundle($files, 'js');
    }



    /**
     * Bundle multiple CSS files into one
     *
     * @param array $files A list of files that must be bundled
     * @return string|null Returns the path to the bundle file, or NULL if nothing was bundled (also in case of failure)
     */
    public static function css(array $files): ?string
    {
        return self::bundle($files, 'css');
    }



    /**
     * Bundle all the files for the specified extension
     *
     * @todo Add support for individual bundles that require async loading
     * @param array $files
     * @param string $extension
     * @return string|null
     */
    protected static function bundle(array $files, string $extension): ?string
    {
// :TODO: Add support for individual bundles that require async loading
        if (!Config::get('web.bundle', true)) {
            // Bundler has been disabled
            return null;
        }

        // Initialize for new bundle
        self::newBundle($files, $extension);

        // Do we already have a VALID bundle file available? If so, we're done
        if (self::bundleExists()) {
            return self::$bundle_file;
        }

        // Bundle the files
        self::bundleFiles($files);

        // Only continue here if we actually added anything to the bundle (some bundles may not have anything, like
        // js_header)
        if (self::$count) {
            // Purge the file from duplicate content
            if (self::$extension === 'css') {
                if (Config::get('web.css.purge', true)) {
                    self::$bundle_file = self::purgeCss();
                }
            }

            // Send the file to the CDN
            if (!Config::get('web.cdn.enabled', true)) {
                self::$bundle_file = Cdn::addFiles(self::$bundle_file);
            }

            // Add the bundle file to the page
            Page::addFile(self::$bundle_file);
        }

        return self::$bundle_file;
    }



    /**
     * Initialize the class to build a new bundle file
     *
     * Prepare bundle information. The bundle file name will be a hash of the bundle file names and the framework
     * version.
     *
     * @param array $files
     * @param string $extension
     * @return void
     */
    protected static function newBundle(array $files, string $extension): void
    {
        $admin_path = (Core::getCallType('admin') ? 'admin/' : '');

        self::$extension   = (Config::get('web.minify', true) ? '.min.' . $extension : '.' . $extension);
        self::$path        =  PATH_WWW . LANGUAGE . '/' . $admin_path . 'pub/' . $extension.'/';
        self::$bundle_file =  Strings::force($files);
        self::$bundle_file =  substr(sha1(self::$bundle . Core::FRAMEWORKCODEVERSION), 1, 32);
        self::$bundle_file =  self::$path . 'bundle-' . self::$bundle_file . self::$extension;
        self::$count       =  0;
    }



    /**
     * Returns true if the current bundle file exists and is valid
     *
     * @return bool
     */
    protected static function bundleExists(): bool
    {
        // Bundle file should exist, of course
        if (!file_exists(self::$bundle_file)) {
            return false;
        }

        $bundle_file = self::$bundle_file;

        // Ensure file is not 0 bytes. This might be caused due to a number of issues, but mainly due to disk full
        // events. When this happens, the 0 bytes bundle files remain, leaving the site without CSS or JS
        if (!filesize($bundle_file)) {
            Log::warning(tr('Encountered empty bundle file ":file"', [':file' => $bundle_file]));
            Log::warning(tr('Deleting empty bundle file ":file"', [':file' => $bundle_file]));

            File::new()->executeMode(dirname($bundle_file), 0770, function() use ($bundle_file) {
                File::new()->delete($bundle_file);
            });

            return false;
        }

        // Bundle files are essentially cached files. Ensure the cache is not too old
        if (Config::get('cache.bundler.max-age', 3600) and (filemtime($bundle_file) + Config::get('cache.bundler.max-age', 3600)) < time()) {
            Log::warning(tr('Deleting expired cached bundle file ":file"', [':file' => $bundle_file]));

            File::new()->executeMode(dirname($bundle_file), 0770, function() use ($bundle_file) {
                File::new()->delete($bundle_file);
            });

            return false;
        }

        return true;
    }



    /**
     * Process CSS data, @includes need to be bundled directly as well
     *
     * @param string $file
     * @param string $org_file
     * @param string $data
     * @return string
     */
    protected static function processCssData(string $file, string $org_file, string $data): string
    {
// :TODO: ADD SUPPORT FOR RECURSIVE @IMPORT STATEMENTS!! What if the files that are imported with @import contain @import statements themselves!?!?!?
        if (preg_match_all('/@import.+?;/', $data, $matches)) {
            foreach ($matches[0] as $match) {
                // Inline replace each @import with the file contents
                $import = '';

// :CLEANUP:
//                                if (preg_match('/@import\s?(?:url\()?((?:"?.+?"?)|(?:\'.+?\'))\)?/', $match)) {
                if (preg_match('/@import\s"|\'.+?"|\'/', $match)) {
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                    $import = Strings::cut($match, '"', '"');

                    if (!file_exists(self::$path . $import)) {
                        Notification::new()
                            ->setCode('not-exists')
                            ->setGroups('developers')
                            ->setTitle(tr('Bundler file does not exist'))
                            ->setMessage(tr('The bundler ":extension" file ":import" @imported by file ":file" does not exist', [
                                ':file'      => $file,
                                ':import'    => $import,
                                ':extension' => self::$extension
                            ]))->send();

                        $import = '';

                    } else {
                        $import = file_get_contents(self::$path . $import);
                    }

                } elseif (preg_match('/@import\surl\(.+?\)/', $match)) {
// :TODO: What if specified URLs are absolute? WHat if start with either / or http(s):// ????
                    // This is an external URL. Get it locally as a temp file, then include
                    $import = Strings::cut($match, '(', ')');
                    $import = Strings::slash(dirname($file)).Strings::unslash($import);

                    if (!file_exists($import)) {
                        Notification::new()
                            ->setCode('not-exists')
                            ->setGroups('developers')
                            ->setTitle(tr('Bundler file does not exist'))
                            ->setMessage(tr('The bundler ":extension" file ":import" @imported by file ":file" does not exist', [
                                ':file'      => $file,
                                ':import'    => $import,
                                ':extension' => self::$extension
                            ]))->send();

                            $import = '';

                    } else {
                        $import = file_get_contents($import);
                    }
                }

                $data = str_replace($match, $import, $data);
            }
        }

        $count = substr_count($org_file, '/');

        if ($count) {
            // URL rewriting required, this file is not in /css or /js, and not in a sub dir
            if (preg_match_all('/url\((.+?)\)/', $data, $matches)) {
                /*
                 * Rewrite all URL's to avoid relative URL's failing for files in sub directories
                 *
                 * e.g.:
                 *
                 * The bundle file is /pub/css/bundle-1.css, includes a css file /pub/css/foo/bar.css, bar.css includes
                 * an image 1.jpg that is in the same directory as bar.css with url("1.jpg")
                 *
                 * In the bundled file, this should become url("foo/1.jpg")
                 */
                foreach ($matches[1] as $url) {
                    if (strtolower(substr($url, 0, 5)) == 'data:') {
                        // This is inline data, nothing we can do so ignore
                        continue;
                    }

                    if (str_starts_with($url, '/')) {
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

        return $data;
    }



    /**
     * Execute the bundling of all the specified files.
     *
     * @param array $files
     * @return void
     */
    protected static function bundleFiles(array $files): void
    {
        // Generate new bundle file. This requires the pub/$files path to be writable
        File::new()->executeMode(dirname(self::$bundle_file), 0770, function() use ($files) {
            foreach ($files as $file => $data) {
                $org_file = $file;
                $file     = self::$path . $file . self::$extension;

                Log::action(tr('Adding file ":file" to bundle file ":bundle"', [
                    ':file'   => $file,
                    ':bundle' => self::$bundle_file
                ]), 3);

                if (!file_exists($file)) {
                    Notification::new()
                        ->setCode('not-exists')
                        ->setGroups('developers')
                        ->setTitle(tr('Bundler file does not exist'))
                        ->setMessage(tr('The requested ":extension" type file ":file" is stated to be bundled but it does not exist', [
                            ':file'      => $file,
                            ':extension' => self::$extension
                        ]))
                        ->send();
                    continue;
                }

                self::$count++;

                $data = file_get_contents($file);
                unset($files[$org_file]);

                if (self::$extension === 'css') {
                    $data = self::processCssData($file, $org_file, $data);
                }

                if (Debug::enabled()) {
                    File::new(self::$bundle_file)->appendData("\n/* *** BUNDLER FILE \"" . $org_file . "\" *** */\n" . $data . (Config::get('web.minify', true) ? '' : "\n"));

                } else {
                    File::new(self::$bundle_file)->appendData($data . (Config::get('web.minify', true) ? '' : "\n"));
                }

                if (self::$count) {
                    chmod(self::$bundle_file, Config::get('filesystem.mode.files', 0640));
                }
            }
        });
    }



    /**
     * Purge CSS rules from this CSS bundle file
     *
     * @return string
     */
    protected static function purgeCss(): string
    {
        try {
            $html_file   = File::new()->temp(Page::getHtml(), 'html');
            $bundle_file = Css::purge($html_file, self::$bundle_file);

            Log::success(tr('Purged not-used CSS rules from bundled file ":file"', [
                ':file' => $bundle_file
            ]));

            File::new()->delete($html_file);

            return $bundle_file;

        }catch(Throwable $e) {
            // The CSS purge failed. Delete the HTML file (if required) and notify
            if (isset($html_file)) {
                File::new()->delete($html_file);
            }

            Notification::new()
                ->setException($e->makeWarning())
                ->send();
        }
    }
}