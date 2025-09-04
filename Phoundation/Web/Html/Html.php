<?php

/**
 * Class Html
 *
 * This class contains various HTML processing methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html;

use PDOStatement;
use Phoundation\Content\Images\ImageFile;
use Phoundation\Core\Core;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Exception\HtmlException;
use Stringable;
use Throwable;

class Html
{
    /**
     * Keeps track of the tab index
     *
     * @var int $tabindex
     */
    protected static int $tabindex = 1;

    /**
     * The Cross Site Request Forgery protection class
     *
     * @var Csrf|null $csrf
     */
    protected static ?Csrf $csrf = null;

    /**
     * Register for all HTML headers
     *
     * @var array $headers
     */
    protected static array $headers = [];

    /**
     * Register for all HTML footers
     *
     * @var array $footers
     */
    protected static array $footers = [];


    /**
     * Returns the current tab index and automatically increments it
     *
     * @return int
     */
    public static function getTabIndex(): int
    {
        return static::$tabindex++;
    }


    /**
     * Minify and return the specified HTML
     *
     * @param string $html
     * @param bool   $force
     *
     * @return string
     */
    public static function minify(string $html, bool $force = false): string
    {
        if ($force or config()->getBoolean('web.minify', false)) {
            return Minifier::html($html);
        }

        return $html;
    }


    /**
     * Wrapper for htmlspecialchars() that can conditionally execute and accept more data types
     *
     * @param Stringable|string|float|int|null $html
     * @param bool                             $enabled
     * @param bool                             $force_render
     *
     * @return Stringable|string|null
     * @see htmlentities()
     */
    public static function safe(Stringable|string|float|int|null $html, bool $enabled = true, bool $force_render = false): Stringable|string|null
    {
        if (is_object($html)) {
            // We don't make objects safe! We know these are renderable objects, but:
            // a) We'd need to render them before making them safe, even though that may not be wanted yet because we might need to set properties still later
            // b) it would make the entire content of the object safe, which likely isn't what we want as those types of objects almost always contain HTML.
            if ($force_render) {
                return (string) $html;
            }

            return $html;
        }

        if ($html === null) {
            return null;
        }

        if ($enabled) {
            return get_null(trim(htmlspecialchars((string) $html)));
        }

        return get_null((string) $html);
    }



    /*
     * Generate all <meta> tags
     *
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
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
    /**
     * ???
     *
     * @param $html
     * @param $filter
     *
     * @return string
     */
    function iefilter($html, $filter): string
    {
        if (!$filter) {
            return $this->render;
        }
        if ($mod = Strings::until(Strings::from($filter, '.'), '.')) {
            return "\n<!--[if " . $mod . ' IE ' . Strings::fromReverse($filter, '.') . "]>\n\t" . $html . "\n<![endif]-->\n";

        } elseif ($filter == 'ie') {
            return "\n<!--[if IE ]>\n\t" . $html . "\n<![endif]-->\n";
        }

        return "\n<!--[if IE " . Strings::from($filter, 'ie') . "]>\n\t" . $html . "\n<![endif]-->\n";
    }


    /*
     * Generate all open graph <meta> tags
     *
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
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
    function header($params, $meta, &$html)
    {
        Arrays::ensure($params, 'links,extra');
        Arrays::default($params, 'http', 'html');
        Arrays::default($params, 'captcha', false);
        Arrays::default($params, 'doctype', '<!DOCTYPE html>');
        Arrays::default($params, 'html', '<html lang="' . LANGUAGE . '">');
        Arrays::default($params, 'body', '<body>');
        Arrays::default($params, 'favicon', true);
        Arrays::default($params, 'amp', false);
        Arrays::default($params, 'style', '');
        Arrays::default($params, 'prefetch_dns', $_CONFIG['prefetch']['dns']);
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
            $params['links'] .= '<link rel="amphtml" href="' . domain('/amp' . $_SERVER['REQUEST_URI']) . '">';
        }
        if (!empty($params['canonical'])) {
            $params['links'] .= '<link rel="canonical" href="' . $params['canonical'] . '">';
        }
        $return = $params['doctype'] . $params['html'] . '
               <head>';
        if ($params['style']) {
            $return .= '<style>' . $params['style'] . '</style>';
        }
        if ($params['links']) {
            if (is_string($params['links'])) {
                $return .= $params['links'];

            } else {
// :OBSOLETE: Links specified as an array only adds more complexity, we're going to send it as plain HTML, and be done with the crap. This is still here for backward compatibility
                foreach ($params['links'] as $data) {
                    $sections = [];
                    foreach ($data as $key => $value) {
                        $sections[] = $key . '="' . $value . '"';
                    }
                    $return .= '<link ' . implode(' ', $sections) . '>';
                }
            }
        }
        foreach ($params['prefetch_dns'] as $prefetch) {
            $return .= '<link rel="dns-prefetch" href="//' . $prefetch . '">';
        }
        foreach ($params['prefetch_files'] as $prefetch) {
            $return .= '<link rel="prefetch" href="' . $prefetch . '">';
        }
        unset($prefetch);
        if (!empty($core->register['header'])) {
            $return .= $core->register['header'];
        }
        $return .= html_generate_css() . html_generate_js();
        /*
         * Set load_delayed to false from here on. If anything after this still
         * generates javascript (footer function!) it should be directly sent to
         * client
         */
        $_CONFIG['cdn']['js']['load_delayed'] = false;
        // Add required fonts
        if (!empty($params['fonts'])) {
            foreach ($params['fonts'] as $font) {
                $extension = Strings::fromReverse($font, '.');
                switch ($extension) {
                    case 'woff':
                        // no break
                    case 'woff2':
                        $return .= '<link rel="preload" href="' . $font . '" as="font" type="font/' . $extension . '" crossorigin="anonymous">';
                        break;
                    default:
                        if (!str_contains($font, 'fonts.googleapis.com')) {
                            throw new HtmlException(tr('Unknown font type ":type" specified for font ":font"', [
                                ':type' => $extension,
                                ':font' => $font,
                            ]));
                        }
                        $return .= '<link rel="preload" href="' . $font . '" as="font" type="text/css" crossorigin="anonymous">';
                }
            }
        }
        /*
         * Add meta data, favicon, and <body> tag
         */
        $return .= html_meta($meta);
        $return .= html_favicon($params['favicon']) . $params['extra'];
        $return .= '</head>' . $params['body'];

        return $return;
    }


    /*
     * Generate and return the HTML footer
     *
     * This function generates and returns the HTML footer. Any data stored in $core->register[footer] will be added, and if the debug bar is enabled, it will be attached as well
     *
     * This function should be called in your c_page() function
     *
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @see html_header()
     * @version 2.5.9: Added documentation, added debug bar support
     *
     * @return string The footer HTML
     */
    function meta($meta)
    {
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
        if (!empty($meta['noindex']) or !Core::isProductionEnvironment() or $_CONFIG['noindex'] or Core::getCallType('admin')) {
            $meta['robots'] = 'noindex, nofollow, nosnippet, noarchive, noydir';
            unset($meta['noindex']);
        }
        /*
         * Validate meta keys
         */
        if (empty($meta['title'])) {
            $meta['title'] = domain(true);
            Notification(new HtmlException(tr('html_meta(): No meta title specified for script ":script" (BAD SEO!)', [':script' => $core->register['script']]), 'warning/not-specified'));

        } elseif (strlen($meta['title']) > 65) {
            $meta['title'] = str_truncate($meta['title'], 65);
            Notification(new HtmlException(tr('html_meta(): Specified meta title ":title" is larger than 65 characters', [':title' => $meta['title']]), 'warning/invalid'));
        }
        if (empty($meta['description'])) {
            $meta['description'] = domain(true);
            Notification(new HtmlException(tr('html_meta(): No meta description specified for script ":script" (BAD SEO!)', [':script' => $core->register['script']]), 'warning/not-specified'));

        } elseif (strlen($meta['description']) > 155) {
            $meta['description'] = str_truncate($meta['description'], 155);
            Notification(new HtmlException(tr('html_meta(): Specified meta description ":description" is larger than 155 characters', [':description' => $meta['description']]), 'warning/invalid'));
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
            Notification(new HtmlException(tr('html_header(): Meta viewport tag is not specified'), 'warning/not-specified'));
        }
        /*
         * Start building meta data
         */
        $return = '<meta http-equiv="Content-Type" content="text/html;charset="' . $_CONFIG['encoding']['charset'] . '">' . '<title>' . $meta['title'] . '</title>';
        foreach ($meta as $key => $value) {
            if ($key === 'og') {
                $return .= html_og($value, $meta);

            } elseif (substr($key, 0, 3) === 'og:') {
// :COMPATIBILITY: Remove this section @ 2.10
                Notification(new HtmlException(tr('html_meta(): Found $meta[:key], this should be $meta[og][:ogkey], ignoring', [
                    ':key'   => $key,
                    ':ogkey' => Strings::from($key, 'og:'),
                ]), 'warning/invalid'));

            } else {
                $return .= '<meta name="' . $key . '" content="' . $value . '">';
            }
        }

        return $return;
    }




    function script($script, $event = 'dom_content', $extra = null, $type = 'text/javascript')
    {
        static $count = 0;
        array_params($script, 'script');
        Arrays::default($script, 'event', $event);
        Arrays::default($script, 'extra', $extra);
        Arrays::default($script, 'type', $type);
        Arrays::default($script, 'to_file', null);
        Arrays::default($script, 'list', 'scripts');
        Arrays::default($script, 'delayed', $_CONFIG['cdn']['js']['load_delayed']);
        if ($script['to_file'] === null) {
            /*
             * The option if this javascript should be written to an external
             * file should be taken from the configuration
             */
            $script['to_file'] = $_CONFIG['cdn']['js']['internal_to_file'];
        }
        if (!$script['script']) {
            // No javascript was specified, Notification developers
            Notification(new HtmlException(tr('html_script(): No javascript code specified'), 'not-specified'));

            return '';
        }
        switch ($script['script'][0]) {
            case '>':
                // Keep this script internal! This is required when script contents contain session sensitive data, or
                // may even change per page
                $return            = '<script type="' . $type . '" src="' . cdn_domain('js/' . substr($script['script'], 1)) . '"' . ($extra ? ' ' . $extra : '') . '></script>';
                $script['to_file'] = false;
                break;
            case '!':
                // Keep this script internal! This is required when script contents contain session sensitive data, or
                // may even change per page
                $return            = substr($script['script'], 1);
                $script['to_file'] = false;
            // no break
            default:
                /*
                 * Event wrapper
                 *
                 * On what event should this script be executed? Eithere boolean true
                 * for standard "document ready" or your own jQuery
                 *
                 * If false, no event wrapper will be added
                 */ if ($script['event']) {
                switch ($script['event']) {
                    case 'dom_content':
                        $return = 'document.addEventListener("DOMContentLoaded", function(e) {
                                      ' . $script['script'] . '
                                   });';
                        break;
                    case 'window':
                        $return = 'window.addEventListener("load", function(e) {
                                      ' . $script['script'] . '
                                   });';
                        break;
                    case 'function':
                        $return = '$(function() {
                                      ' . $script['script'] . '
                                   });';
                        break;
                    default:
                        throw new HtmlException(tr('Unknown event value ":value" specified', [':value' => $script['event']]));
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
                    $return = ' <script type="' . $type . '"' . ($extra ? ' ' . $extra : '') . '>
                                 ' . $return . '
                             </script>';
                }
        }
        // Store internal script in external files, or keep them internal?
        if ($script['to_file']) {
            try {
                // Create the cached file names
                $base = 'cached-' . substr($core->register['script'], 0, -4) . '-' . ($core->register['script_file'] ? $core->register['script_file'] . '-' : '') . $count;
                $file = DIRECTORY_ROOT . 'www/' . LANGUAGE . (Core::getCallType('admin') ? '/admin' : '') . '/pub/js/' . $base;
                log_file(tr('Creating externally cached javascript file ":file"', [':file' => $file . '.js']), 'html-script', 'VERYVERBOSE/cyan');
                /*
                 * Check if the cached file exists and is not too old.
                 */
                if (file_exists($file . '.js')) {
                    if (!filesize($file . '.js')) {
                        // The javascript file is empty
                        log_file(tr('Deleting externally cached javascript file ":file" because the file is 0 bytes', [':file' => $file . '.js']), 'html-script', 'yellow');
                        PhoFile::new(DIRECTORY_ROOT . 'www/' . LANGUAGE . '/pub/js')
                               ->executeMode(0770, function () use ($file) {
                                PhoFile::new($file . '.js,' . $file . '.min.js', 'ug+w', DIRECTORY_ROOT . 'www/' . LANGUAGE . '/pub/js')
                                    ->chmod();
                                PhoFile::new($file . '.js,' . $file . '.min.js', DIRECTORY_ROOT . 'www/' . LANGUAGE . '/pub/js')
                                    ->delete();
                            });

                    } elseif (($_CONFIG['cdn']['cache_max_age'] > 60) and ((filemtime($file . '.js') + $_CONFIG['cdn']['cache_max_age']) < time())) {
                        /*
                         * External cached file is too old
                         */
                        log_file(tr('Deleting externally cached javascript file ":file" because the file cache time expired', [':file' => $file . '.js']), 'html-script', 'yellow');
                        PhoFile::new()
                            ->executeMode(DIRECTORY_ROOT . 'www/' . LANGUAGE . '/pub/js', 0770, function () use ($file) {
                                file_delete([
                                    'patterns'       => $file . '.js,' . $file . '.min.js',
                                    'force_writable' => true,
                                    'restrictions'   => DIRECTORY_ROOT . 'www/' . LANGUAGE . '/pub/js',
                                ]);
                            });
                    }
                }
                /*
                 * If file does not exist, create it now. Check again if it
                 * exist, because the previous function may have possibly
                 * deleted it
                 */
                if (!file_exists($file . '.js')) {
                    PhoFile::new()
                        ->executeMode(dirname($file), 0770, function () use ($file, $return) {
                            log_file(tr('Writing internal javascript to externally cached file ":file"', [':file' => $file . '.js']), 'html-script', 'cyan');
                            file_put_contents($file . '.js', $return);
                        });
                }
                /*
                 * Always minify the file. On local machines where minification is
                 * turned off this is not a problem, it should take almost zero
                 * resources, and it will immediately test minification for
                 * production as well.
                 */
                if (!file_exists($file . '.min.js')) {
                    try {
                        load_libs('uglify');
                        uglify_js($file . '.js');

                    } catch (Throwable $e) {
                        /*
                         * Minify process failed. Notification and fall back on a plain
                         * copy
                         */
                        Notification($e);
                        copy($file . '.js', $file . '.min.js');
                    }
                }
                /*
                 * Add the file to the html javascript load list
                 */
                html_load_js($base, $script['list']);
                $count++;

                return '';

            } catch (Throwable $e) {
                /*
                 * Moving internal javascript to external files failed, Notification
                 * developers
                 */
                Notification($e);
                /*
                 * Add a <script> element because now we'll include it into the
                 * HTML anyway
                 */
                $return = ' <script type="' . $type . '"' . ($extra ? ' ' . $extra : '') . '>
                            ' . $return . '
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
            $core->register['script_delayed'] = $return;
        }
        $count++;

        return '';
    }



    /**
     * Converts the specified src URL by adding the CDN domain if it does not have a domain specified yet. Also
     * converts the image to a different format if configured to do so
     *
     * @param string $url The URL for the image
     * @param string
     * @param string
     *
     * @return string The result
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
         * @package   image
     * @version   2.5.161: Added function and documentation
     *
     */
    function img_src($src, &$external = null, &$file_src = null, &$original_src = null, $section = 'pub')
    {
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
                $file_part = Strings::ensureBeginsWith(Strings::from($src, cdn_domain('', '')), '/');
                $external  = false;
                if (substr($file_part, 0, 5) === '/pub/') {
                    $file_src = DIRECTORY_ROOT . 'www/' . LANGUAGE . $file_part;

                } else {
                    $file_src = DIRECTORY_ROOT . 'data/content' . $file_part;
                }

            } elseif (str_contains($src, domain(''))) {
                /*
                 * Here, mistakenly, the main domain was used for CDN data
                 */
                $file_part = Strings::ensureBeginsWith(Strings::from($src, domain('')), '/');
                $file_src  = DIRECTORY_ROOT . 'data/content' . $file_part;
                $external  = false;
                Notification(new HtmlException(tr('html_img(): The main domain ":domain" was specified for CDN data, please correct this issue', [':domain' => domain('')]), 'warning/invalid'));

            } else {
                $file_src = $src;
                $external = true;
            }

        } else {
            /*
             * Assume all images are PUB images
             */
            $file_part = '/pub' . Strings::ensureBeginsWith($src, '/');
            $file_src  = DIRECTORY_ROOT . 'www/' . LANGUAGE . $file_part;
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
        if (!accepts('image/' . $_CONFIG['cdn']['img']['auto_convert'][$format])) {
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
        $target_part = Strings::untilReverse($file_part, '.') . '.' . $_CONFIG['cdn']['img']['auto_convert'][$format];
        $target      = Strings::untilReverse($file_src, '.') . '.' . $_CONFIG['cdn']['img']['auto_convert'][$format];
        log_file(tr('Automatically converting ":format" format image ":src" to format ":target"', [
            ':format' => $format,
            ':src'    => $file_src,
            ':target' => $_CONFIG['cdn']['img']['auto_convert'][$format],
        ]), 'html', 'VERBOSE/cyan');
        try {
            if (!file_exists($target)) {
                log_file(tr('Modified format target ":target" does not exist, converting original source', [':target' => $target]), 'html', 'VERYVERBOSE/warning');
                load_libs('image');
                PhoFile::new()
                       ->executeMode(dirname($file_src), 0770, function () use ($file_src, $target, $format) {
                        PhoFile::new()
                            ->executeMode($file_src, 0660, function () use ($file_src, $target, $format) {
                                global $_CONFIG;
                                image_convert([
                                    'method' => 'custom',
                                    'source' => $file_src,
                                    'target' => $target,
                                    'format' => $_CONFIG['cdn']['img']['auto_convert'][$format],
                                ]);
                            });
                    });
            }
            /*
             * Convert src back to URL again
             */
            $file_src = $target;
            $src      = cdn_domain($target_part, '');

        } catch (Throwable $e) {
            /*
             * Failed to upgrade image. Use the original image
             */
            $e->makeWarning(true);
            $e->addMessages(tr('html_img_src(): Failed to auto convert image ":src" to format ":format". Leaving image as-is', [
                ':src'    => $src,
                ':format' => $_CONFIG['cdn']['img']['auto_convert'][$format],
            ]));
            Notification($e);
        }

        return $src;
    }


    /*
     * Create and return an img tag that contains at the least src, alt, height and width
     * If height / width are not specified, then html_img() will try to get the height / width
     * data itself, and store that data in database for future reference
     */
    function img($params, $alt = null, $width = null, $height = null, $extra = '')
    {
        static $images, $cache = [];
// :LEGACY: The following code block exists to support legacy apps that still use 5 arguments for html_img() instead of a params array
        if (!is_array($params)) {
            /*
             * Ensure we have a params array
             */
            $params = [
                'src'    => $params,
                'alt'    => $alt,
                'width'  => $width,
                'height' => $height,
                'lazy'   => null,
                'extra'  => $extra,
            ];
        }
        array_ensure($params, 'src,alt,width,height,class,extra');
        Arrays::default($params, 'lazy', $_CONFIG['cdn']['img']['lazy_load']);
        Arrays::default($params, 'tag', 'img');
        Arrays::default($params, 'section', 'pub');
        if (!$params['src']) {
            /*
             * No image at all?
             */
            if (Core::isProductionEnvironment()) {
                /*
                 * On production, just Notification and ignore
                 */
                Notification([
                    'code'    => 'not-specified',
                    'groups'  => 'developer',
                    'title'   => tr('No image src specified'),
                    'message' => tr('html_img(): No src for image with alt text ":alt"', [':alt' => $params['alt']]),
                ]);

                return '';
            }
            throw new HtmlException(tr('html_img(): No src for image with alt text ":alt"', [':alt' => $params['alt']]));
        }
        if (!Core::isProductionEnvironment()) {
            if (!$params['src']) {
                throw new HtmlException(tr('No image src specified'));
            }
            if (!$params['alt']) {
                throw new HtmlException(tr('html_img(): No image alt text specified for src ":src"', [':src' => $params['src']]));
            }

        } else {
            if (!$params['src']) {
                Notification([
                    'code'   => 'not-specified',
                    'groups' => 'developer',
                    'title'  => tr('html_img(): No image src specified'),
                ]);
            }
            if (!$params['alt']) {
                Notification([
                    'code'    => 'not-specified',
                    'groups'  => 'developer',
                    'title'   => tr('No image alt specified'),
                    'message' => tr('html_img(): No image alt text specified for src ":src"', [':src' => $params['src']]),
                ]);
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
                              AND    `created_on` > NOW() - INTERVAL 1 DAY
                              AND    `status`    IS NULL', [':url' => $params['src']]);
                if ($image) {
                    /*
                     * Database cache found, add it to local cache
                     */
                    $cache[$params['src']] = [
                        'width'  => $image['width'],
                        'height' => $image['height'],
                    ];

                }
            }

        } catch (Throwable $e) {
            Notification($e);
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
                        $file  = file_move_to_target($file_src, DIRECTORY_TMP, false, true);
                        $image = getimagesize(DIRECTORY_TMP . $file);

                    } catch (Throwable $e) {
                        switch ($e->getCode()) {
                            case 404:
                                log_file(tr('html_img(): Specified image ":src" does not exist', [':src' => $file_src]));
                                break;
                            case 403:
                                log_file(tr('html_img(): Specified image ":src" got access denied', [':src' => $file_src]));
                                break;
                            default:
                                log_file(tr('html_img(): Specified image ":src" got error ":e"', [
                                    ':src' => $file_src,
                                    ':e'   => $e->getMessage(),
                                ]));
                                throw $e->makeWarning(true);
                        }
                        /*
                         * Image doesnt exist
                         */
                        Notification([
                            'code'    => 'not-exists',
                            'groups'  => 'developer',
                            'title'   => tr('Image does not exist'),
                            'message' => tr('html_img(): Specified image ":src" does not exist', [':src' => $file_src]),
                        ]);
                        $image[0] = 0;
                        $image[1] = 0;
                    }
                    if (!empty($file)) {
                        file_delete(DIRECTORY_TMP . $file);
                    }

                } else {
                    /*
                     * Local image. Analize directly
                     */
                    if (file_exists($file_src)) {
                        try {
                            $image = getimagesize($file_src);

                        } catch (Throwable $e) {
                            switch ($e->getCode()) {
                                case 404:
                                    log_file(tr('html_img(): Specified image ":src" does not exist', [':src' => $file_src]));
                                    break;
                                case 403:
                                    log_file(tr('html_img(): Specified image ":src" got access denied', [':src' => $file_src]));
                                    break;
                                default:
                                    log_file(tr('html_img(): Specified image ":src" got error ":e"', [
                                        ':src' => $file_src,
                                        ':e'   => $e->getMessage(),
                                    ]));
                                    throw $e->makeWarning(true);
                            }
                            // Image doesnt exist
                            Notification([
                                'code'    => 'not-exists',
                                'groups'  => 'developer',
                                'title'   => tr('Image does not exist'),
                                'message' => tr('html_img(): Specified image ":src" does not exist', [':src' => $file_src]),
                            ]);
                            $image[0] = 0;
                            $image[1] = 0;
                        }

                    } else {
                        // Image doesn't exist.
                        log_console(tr('html_img(): Can not analyze image ":src", the local directory ":directory" does not exist', [
                            ':src'       => $params['src'],
                            ':directory' => $file_src,
                        ]), 'yellow');
                        $image[0] = 0;
                        $image[1] = 0;
                    }
                }
                $image['width']  = $image[0];
                $image['height'] = $image[1];
                $status          = null;

            } catch (Throwable $e) {
                Notification($e);
                $image['width']  = 0;
                $image['height'] = 0;
                $status          = $e->getCode();
            }
            if (!$image['height'] or !$image['width']) {
                log_console(tr('html_img(): image ":src" has invalid dimensions with width ":width" and height ":height"', [
                    ':src'    => $params['src'],
                    ':width'  => $image['width'],
                    ':height' => $image['height'],
                ]), 'yellow');

            } else {
                try {
                    /*
                     * Store image info in local and db cache
                     */
// :TODO: Add support for memcached
                    $cache[$params['src']] = [
                        'width'  => $image['width'],
                        'height' => $image['height'],
                    ];
                    sql()->query('INSERT INTO `html_img_cache` (`status`, `url`, `width`, `height`)
                           VALUES                       (:status , :url , :width , :height )

                           ON DUPLICATE KEY UPDATE `status`    = NULL,
                                                   `created_on` = NOW()', [
                            ':url'    => $params['src'],
                            ':width'  => $image['width'],
                            ':height' => $image['height'],
                            ':status' => $status,
                        ]);

                } catch (Throwable $e) {
                    Notification($e);
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
                    Notification(new HtmlException(tr('Detected invalid "width" parameter specification for image ":src", and failed to get real image width too, ignoring "width" attribute', [
                        ':width' => $params['width'],
                        ':src'   => $params['src'],
                    ]), 'warning/invalid'));
                    $params['width'] = null;

                } else {
                    Notification(new HtmlException(tr('Detected invalid "width" parameter specification for image ":src", forcing real image width ":real" instead', [
                        ':width' => $params['width'],
                        ':real'  => $image['width'],
                        ':src'   => $params['src'],
                    ]), 'warning/invalid'));
                    $params['width'] = $image['width'];
                }
            }
            if (!is_numeric($params['height']) and ($params['height'] > 0)) {
                if (!$image['height']) {
                    Notification(new HtmlException(tr('Detected invalid "height" parameter specification for image ":src", and failed to get real image height too, ignoring "height" attribute', [
                        ':height' => $params['height'],
                        ':src'    => $params['src'],
                    ]), 'warning/invalid'));
                    $params['height'] = null;

                } else {
                    Notification(new HtmlException(tr('Detected invalid "height" parameter specification for image ":src", forcing real image height ":real" instead', [
                        ':height' => $params['height'],
                        ':real'   => $image['height'],
                        ':src'    => $params['src'],
                    ]), 'warning/invalid'));
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
                    log_file(tr('Image src ":src" is larger than its specification, sending resized image instead', [':src' => $params['src']]), 'html', 'warning');
                    /*
                     * Determine the resize dimensions
                     */
                    if (!$params['height']) {
                        $params['height'] = $image['height'];
                    }
                    if (!$params['width']) {
                        $params['width'] = $image['width'];
                    }
                    /*
                     * Determine the file target name and src
                     */
                    if (str_contains($params['src'], '@2x')) {
                        $pre    = Strings::until($params['src'], '@2x');
                        $post   = Strings::from($params['src'], '@2x');
                        $target = $pre . '@' . $params['width'] . 'x' . $params['height'] . '@2x' . $post;
                        $pre         = Strings::until($file_src, '@2x');
                        $post        = Strings::from($file_src, '@2x');
                        $file_target = $pre . '@' . $params['width'] . 'x' . $params['height'] . '@2x' . $post;

                    } else {
                        $pre    = Strings::untilReverse($params['src'], '.');
                        $post   = Strings::fromReverse($params['src'], '.');
                        $target = $pre . '@' . $params['width'] . 'x' . $params['height'] . '.' . $post;
                        $pre         = Strings::untilReverse($file_src, '.');
                        $post        = Strings::fromReverse($file_src, '.');
                        $file_target = $pre . '@' . $params['width'] . 'x' . $params['height'] . '.' . $post;
                    }
                    /*
                     * Resize or do we have a cached version?
                     */
                    try {
                        if (!file_exists($file_target)) {
                            log_file(tr('Resized version of ":src" does not yet exist, converting', [':src' => $params['src']]), 'html', 'VERBOSE/cyan');
                            load_libs('image');
                            PhoFile::new()
                                ->executeMode(dirname($file_src), 0770, function () use ($file_src, $file_target, $params) {
                                    global $_CONFIG;
                                    ImageFile::convert([
                                        'method' => 'resize',
                                        'source' => $file_src,
                                        'target' => $file_target,
                                        'x'      => $params['width'],
                                        'y'      => $params['height'],
                                    ]);
                                });
                        }
                        // Convert src to the resized target
                        $params['src'] = $target;
                        $file_src      = $file_target;

                    } catch (Throwable $e) {
                        // Failed to auto resize the image. Notification and stay with the current version meanwhile.
                        $e->addMessages(tr('html_img(): Failed to auto resize image ":image", using non resized image with incorrect width / height instead', [':image' => $file_src]));
                        Notification($e->makeWarning(true));
                    }
                }
            }
        }
        if ($params['height']) {
            $params['height'] = ' height="' . $params['height'] . '"';

        } else {
            $params['height'] = '';
        }
        if ($params['width']) {
            $params['width'] = ' width="' . $params['width'] . '"';

        } else {
            $params['width'] = '';
        }
        if (isset($params['style'])) {
            $params['extra'] .= ' style="' . $params['style'] . '"';
        }
        if (isset($params['class'])) {
            $params['extra'] .= ' class="' . $params['class'] . '"';
        }
        if ($params['lazy']) {
            if ($params['extra']) {
                if (str_contains($params['extra'], 'class="')) {
                    // Add lazy class to the class definition in "extra"
                    $params['extra'] = str_replace('class="', 'class="lazy ', $params['extra']);

                } else {
                    // Add class definition with "lazy" to extra
                    $params['extra'] = ' class="lazy" ' . $params['extra'];
                }

            } else {
                // Set "extra" to be class definition with "lazy"
                $params['extra'] = ' class="lazy"';
            }
            $this->render = '';
            if (empty($core->register['lazy_img'])) {
                // Use lazy image loading
                try {
                    if (!file_exists(DIRECTORY_ROOT . 'www/' . LANGUAGE . '/pub/js/jquery.lazy/jquery.lazy.js')) {
                        // jquery.lazy is not available, auto install it.
                        $file      = PhoFile::download('https://github.com/eisbehr-/jquery.lazy/archive/master.zip');
                        $directory = cli_unzip($file);
                        PhoFile::new()
                               ->executeMode(DIRECTORY_ROOT . 'www/en/pub/js', 0770, function () use ($directory) {
                                PhoFile::delete(DIRECTORY_ROOT . 'www/' . LANGUAGE . '/pub/js/jquery.lazy/', DIRECTORY_ROOT . 'www/' . LANGUAGE . '/pub/js/');
                                rename($directory . 'jquery.lazy-master/', DIRECTORY_ROOT . 'www/' . LANGUAGE . '/pub/js/jquery.lazy');
                            });
                        file_delete($directory);
                    }
                    html_load_js('jquery.lazy/jquery.lazy');
                    load_config('lazy_img');
                    // Build jquery.lazy options
                    $options = [];
                    foreach ($_CONFIG['lazy_img'] as $key => $value) {
                        if ($value === null) {
                            continue;
                        }
                        switch ($key) {
                            // Booleans
                            case 'auto_destroy':
                                // no break
                            case 'chainable':
                                // no break
                            case 'combined':
                                // no break
                            case 'enable_throttle':
                                // no break
                            case 'visible_only':
                                // no break
                                /*
                                 * Numbers
                                 */
                            case 'delay':
                                // no break
                            case 'effect_time':
                                // no break
                            case 'threshold':
                                // no break
                            case 'throttle':
                                // All these need no quotes
                                $options[Strings::underscoreToCamelcase($key)] = $value;
                                break;
                            // Callbacks
                            case 'after_load':
                                // no break
                            case 'on_load':
                                // no break
                            case 'before_load':
                                // no break
                            case 'on_error':
                                // no break
                            case 'on_finished_all':
                                // All these need no quotes
                                $options[Strings::underscoreToCamelcase($key)] = 'function(e) {' . $value . '}';
                                break;
                            // Strings
                            case 'append_scroll':
                                // no break
                            case 'bind':
                                // no break
                            case 'default_image':
                                // no break
                            case 'effect':
                                // no break
                            case 'image_base':
                                // no break
                            case 'name':
                                // no break
                            case 'placeholder':
                                // no break
                            case 'retina_attribute':
                                // no break
                            case 'scroll_direction':
                                /*
                                 * All these need quotes
                                 */ $options[Strings::underscoreToCamelcase($key)] = '"' . $value . '"';
                                break;
                            default:
                                throw new HtmlException(tr('Unknown lazy_img option ":key" specified. Please check the $_CONFIG[lazy_img] configuration!', [':key' => $key]));
                        }
                    }
                    $core->register['lazy_img'] = true;
                    $this->render               .= Script::new([
                        'event'  => 'function',
                        'script' => '$(".lazy").Lazy({' . array_implode_with_keys($options, ',', ':') . '});',
                    ])
                                                         ->render();

                } catch (Throwable $e) {
                    /*
                     * Oops, jquery.lazy failed to install or load. Notification, and
                     * ignore, we will just continue without lazy loading.
                     */
                    Notification(new HtmlException(tr('html_img(): Failed to install or load jquery.lazy'), $e));
                }
            }
            $this->render .= '<' . $params['tag'] . ' data-src="' . $params['src'] . '" alt="' . htmlspecialchars($params['alt']) . '"' . $params['width'] . $params['height'] . $params['extra'] . '>';

            return $this->render;
        }

        return '<' . $params['tag'] . ' src="' . $params['src'] . '" alt="' . htmlspecialchars($params['alt']) . '"' . $params['width'] . $params['height'] . $params['extra'] . '>';
    }


    /**
     * Create and return a video container that has at the least src, alt, height and width
     */
    function video($params)
    {
        Arrays::ensure($params, 'src,width,height,more,type');
        Arrays::default($params, 'controls', true);
        if (!Core::isProductionEnvironment()) {
            if (!$params['src']) {
                throw new HtmlException(tr('No video src specified'));
            }
        }
// :INVESTIGATE: Is better getting default width and height dimensions like in html_img()
// But in this case, we have to use a external "library" to get this done
// Investigate the best option for this!
        if (!$params['width']) {
            throw new HtmlException(tr('No width specified'));
        }
        if (!is_natural($params['width'])) {
            throw new HtmlException(tr('html_video(): Invalid width ":width" specified', [':width' => $params['width']]));
        }
        if (!$params['height']) {
            throw new HtmlException(tr('html_video(): No height specified'));
        }
        if (!is_natural($params['height'])) {
            throw new HtmlException(tr('html_video(): Invalid height ":height" specified', [':height' => $params['height']]));
        }
        /*
         * Videos can be either local or remote
         * Local videos either have http://thisdomain.com/video, https://thisdomain.com/video, or /video
         * Remote videos must have width and height specified
         */
        if (str_starts_with($params['src'], 'http://')) {
            $protocol = 'http';

        } elseif ($protocol = str_starts_with($params['src'], 'https://')) {
            $protocol = 'https';

        } else {
            $protocol = '';
        }
        if (!$protocol) {
            /*
             * This is a local video
             */
            $params['src']  = DIRECTORY_ROOT . 'www/en' . Strings::ensureBeginsWith($params['src'], '/');
            $params['type'] = mime_content_type($params['src']);

        } else {
            if (preg_match('/^' . str_replace('/', '\/', str_replace('.', '\.', domain())) . '\/.+$/ius', $params['src'])) {
                /*
                 * This is a local video with domain specification
                 */
                $params['src']  = DIRECTORY_ROOT . 'www/en' . Strings::ensureBeginsWith(Strings::from($params['src'], domain()), '/');
                $params['type'] = mime_content_type($params['src']);

            } elseif (!Core::isProductionEnvironment()) {
                /*
                 * This is a remote video
                 * Remote videos MUST have height and width specified!
                 */
                if (!$params['height']) {
                    throw new HtmlException(tr('html_video(): No height specified for remote video'));
                }
                if (!$params['width']) {
                    throw new HtmlException(tr('html_video(): No width specified for remote video'));
                }
                switch ($params['type']) {
                    case 'mp4':
                        $params['type'] = 'video/mp4';
                        break;
                    case 'flv':
                        $params['type'] = 'video/flv';
                        break;
                    case '':
                        // Try to autodetect
                        $params['type'] = 'video/' . Strings::fromReverse($params['src'], '.');
                        $params['type'] = 'video/' . Strings::fromReverse($params['src'], '.');
                        break;
                    default:
                        throw new HtmlException(tr('Unknown type ":type" specified for remote video', [
                            ':type' => $params['type'],
                        ]));
                }
            }
        }
        // Build HTML
        $this->render = '   <video width="' . $params['width'] . '" height="' . $params['height'] . '" ' . ($params['controls'] ? 'controls ' : '') . '' . ($params['more'] ? ' ' . $params['more'] : '') . '>
                                <source src="' . $params['src'] . '" type="' . $params['type'] . '">
                            </video>';

        return $this->render;
    }


    /*
     * Ensure that missing checkbox values are restored automatically (Seriously, sometimes web design is tiring...)
     *
     * This function works by assuming that each checkbox with name NAME has a hidden field with name _NAME. If NAME is missing, _NAME will be moved to NAME
     *
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     *
     * @return void
     */
    function fix_checkbox_values()
    {
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
     * Filter the specified tags from the specified HTML
     *
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package html
     * @version 2.5.0: Added function and documentation

     * @param string $html
     * @param string array $tags
     * @param boolean $exception
     * @return string The result
     */
    function filter_tags($html, $tags, $exception = false)
    {
        $list = [];
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
                throw new HtmlException('Found HTML tags ":tags" which are forbidden', [
                    ':tags',
                    implode(', ', $list),
                ]);
            }
            foreach ($list as $item) {
                $item->parentNode->removeChild($item);
            }
        }
        $html = $dom->saveHTML();

        return $this->render;
    }


    /*
     * Returns HTML for a loader screen that will hide the buildup of the web page behind it. Once the page is loaded, the loader screen will automatically disappear.
     *
     * This function typically should be executed in the c_page_header() call, and the HTML output of this function should be inserted at the beginning of the HTML that that function generates. This way, the loader screen will be the first thing (right after the <body> tag) that the browser will render, hiding all the other elements that are buiding up.
     *
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
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
    function loader_screen($params)
    {
        array_params($params);
        Arrays::default($params, 'page_selector', '');
        Arrays::default($params, 'text', '');
        Arrays::default($params, 'text_style', '');
        Arrays::default($params, 'image_src', '');
        Arrays::default($params, 'image_alt', tr('Loader screen'));
        Arrays::default($params, 'image_width', null);
        Arrays::default($params, 'image_height', null);
        Arrays::default($params, 'image_top', '100px');
        Arrays::default($params, 'image_left', null);
        Arrays::default($params, 'image_right', null);
        Arrays::default($params, 'image_bottom', null);
        Arrays::default($params, 'image_style', 'position:relative;');
        Arrays::default($params, 'screen_line_height', 0);
        Arrays::default($params, 'screen_background', 'white');
        Arrays::default($params, 'screen_color', 'black');
        Arrays::default($params, 'screen_remove', true);
        Arrays::default($params, 'screen_text_align', 'center');
        Arrays::default($params, 'screen_vertical_align', 'middle');
        Arrays::default($params, 'screen_style_extra', '');
        Arrays::default($params, 'transition_time', 300);
        Arrays::default($params, 'transition_style', 'fade');
        Arrays::default($params, 'test_loader_screen', false);
        $extra = '';
        if ($params['screen_line_height']) {
            $extra .= 'line-height:' . $params['screen_line_height'] . ';';
        }
        if ($params['screen_vertical_align']) {
            $extra .= 'vertical-align:' . $params['screen_vertical_align'] . ';';
        }
        if ($params['screen_text_align']) {
            $extra .= 'text-align:' . $params['screen_text_align'] . ';';
        }
        $this->render = '  <div id="loader-screen" style="position:fixed;top:0px;bottom:0px;left:0px;right:0px;z-index:2147483647;display:block;background:' . $params['screen_background'] . ';color: ' . $params['screen_color'] . ';text-align: ' . $params['screen_text_align'] . ';' . $extra . '" ' . $params['screen_style_extra'] . '>';
        // Show loading text
        if ($params['text']) {
            $this->render .= '<div style="' . $params['text_style'] . '">
                     ' . $params['text'] . '
                     </div>';
        }
        /*
         * Show loading image
         */
        if ($params['image_src']) {
            if ($params['image_top']) {
                $params['image_style'] .= 'top:' . $params['image_top'] . ';';
            }
            if ($params['image_left']) {
                $params['image_style'] .= 'left:' . $params['image_left'] . ';';
            }
            if ($params['image_right']) {
                $params['image_style'] .= 'right:' . $params['image_right'] . ';';
            }
            if ($params['image_bottom']) {
                $params['image_style'] .= 'bottom:' . $params['image_bottom'] . ';';
            }
            $this->render .= html_img([
                'src'    => $params['image_src'],
                'alt'    => $params['image_alt'],
                'lazy'   => false,
                'width'  => $params['image_width'],
                'height' => $params['image_height'],
                'style'  => $params['image_style'],
            ]);
        }
        $this->render .= '  </div>';
        if (!$params['test_loader_screen']) {
            switch ($params['transition_style']) {
                case 'fade':
                    if ($params['page_selector']) {
                        /*
                         * Hide the loader screen and show the main page wrapper
                         */
                        $this->render .= html_script('$("' . $params['page_selector'] . '").show(' . $params['transition_time'] . ');
                                          $("#loader-screen").fadeOut(' . $params['transition_time'] . ', function() { $("#loader-screen").css("display", "none"); ' . ($params['screen_remove'] ? '$("#loader-screen").remove();' : '') . ' });');

                        return $this->render;
                    }
                    /*
                     * Only hide the loader screen
                     */
                    $this->render .= html_script('$("#loader-screen").fadeOut(' . $params['transition_time'] . ', function() { $("#loader-screen").css("display", "none"); ' . ($params['screen_remove'] ? '$("#loader-screen").remove();' : '') . ' });');
                    break;
                case 'slide':
                    $this->render .= html_script('var height = $("#loader-screen").height(); $("#loader-screen").animate({ top: height }, ' . $params['transition_time'] . ', function() { $("#loader-screen").css("display", "none"); ' . ($params['screen_remove'] ? '$("#loader-screen").remove();' : '') . ' });');
                    break;
                default:
                    throw new HtmlException(tr('html_loader_screen(): Unknown screen transition value ":value" specified', [':value' => $params['test_loader_screen']]), 'unknown');
            }
        }

        return $this->render;
    }


    /*
     * Strip tags or attributes from all HTML tags
     *
     * This function will strip all attributes except for those attributes specified in $allowed_attributes
     *
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
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
    function strip_attributes($source, $allowed_attributes = null)
    {
        $allowed_attributes = Arrays::force($allowed_attributes);
        /*
         * If specified, source string is empty, then we're done right away
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
