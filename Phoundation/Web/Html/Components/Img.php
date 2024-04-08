<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Interfaces\ImgInterface;
use Phoundation\Web\Html\Exception\HtmlException;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Stringable;

/**
 * Class Img
 *
 * This class generates <img> elements
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class Img extends Span implements ImgInterface
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var Restrictions $restrictions
     */
    protected Restrictions $restrictions;

    /**
     * Sets whether the image will be lazily loaded as-needed or directly
     *
     * @var bool $lazy_load
     */
    protected bool $lazy_load = true;

    /**
     * The source URL for this image
     *
     * @var Stringable|string|null $src
     */
    protected Stringable|string|null $src = null;

    /**
     * The file source path for this image
     *
     * @var string|null $file_src
     */
    protected ?string $file_src = null;

    /**
     * The alt text for this image
     *
     * @var string|null $alt
     */
    protected ?string $alt = null;

    /**
     * True if this is an image from an external domain (a domain NOT in the "web.domains" configuration)
     *
     * @var bool $external
     */
    protected bool $external = false;


    /**
     * Img constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        parent::setElement('img');
        $this->setLazyLoad(Config::get('web.images.lazy-load', true));
        $this->requires_closing_tag = false;
    }


    /**
     * Returns the HTML alt element attribute
     *
     * @return bool
     */
    public function getLazyLoad(): bool
    {
        return $this->lazy_load;
    }


    /**
     * Sets the HTML alt element attribute
     *
     * @param bool $lazy_load
     *
     * @return Img
     */
    public function setLazyLoad(?bool $lazy_load): static
    {
        if ($lazy_load === null) {
            $lazy_load = Config::get('web.images.lazy-load', true);
        }
        if ($lazy_load) {
            $this->addClass('lazy');
            Response::loadJavascript('js/jquery/lazyload/jquery.lazyload');
        }
        $this->lazy_load = $lazy_load;

        return $this;
    }


    /**
     * Returns the HTML alt element attribute
     *
     * @return string|null
     */
    public function getAlt(): ?string
    {
        return $this->alt;
    }


    /**
     * Sets the HTML alt element attribute
     *
     * @param string|null $alt
     *
     * @return Img
     */
    public function setAlt(?string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }


    /**
     * Returns if this image is hosted on an external domain (that is, a domain NOT in the "web.domains" configuration
     *
     * @return bool
     */
    public function getExternal(): bool
    {
        return $this->external;
    }


    /**
     * Returns the HTML src element attribute
     *
     * @return Stringable|string|null
     */
    public function getSrc(): Stringable|string|null
    {
        return $this->src;
    }


    /**
     * Sets the HTML src element attribute
     *
     * @param Stringable|string|null $src
     *
     * @return Img
     */
    public function setSrc(Stringable|string|null $src): static
    {
//        // Get a built src string. If $built_src is equal to specified $src then it wasn't changed and so it's an
//        $domain         = Url::getDomain($src);
//        $built_src      = UrlBuilder::getCdn($src);
//        $this->external = Url::isExternal($src);
//
//        if ($this->external) {
//            // Download external images local so that we can perform tests, changes, upgrades, etc.
//            $file_src = \Phoundation\Web\Http\File::new($this->restrictions)->download($src);
//        } else {
//            // This is a local image (either with or without domain specified) Locate the file
//            $file_src = Strings::from($src     , $domain . '/');
//            $file_src = Strings::from($file_src, $domain . 'img/');
//        }
//
//        // Get image extension and type, and see if it is accepted
//        $extension = $this->getExtension($src);
//        $type      = $this->getType($extension);
//
//        if (!$this->isAccepted($type)) {
//            $src = $this->convert($src);
//        }
//
//
//        if ($this->external) {
//            // This is an external image
//            $file_src = Strings::startsNotWith(Strings::from($src, $domain), '/');
//            $file_src = DIRECTORY_ROOT . 'data/cdn/' . $file_src;
//        } else {
//            // This is an internal image, it must be stored in ROOT/data/cdn/LANGUAGE/img/...
//
//            // Is this a WWW or CDN domain (from primary or any of the others), or completely external domain?
//            if (Url::getDomainType($src) === 'www') {
//                // Here, mistakenly, the main domain was used for CDN data
//                Notification::new()
//                    ->setRoles('developer')
//                    ->setTitle(tr('Minor issue with CDN data'))
//                    ->setMessage(tr('The main domain ":domain" was specified for CDN data, please correct this issue', [
//                        ':domain' => $domain
//                    ]))
//                    ->send();
//            }
//
//            $file_src =
//            $file_src = DIRECTORY_DATA . 'cdn/' . Session::getLanguage() . 'img/' . $src;
//            $file_src = '/pub'.Strings::startsWith($src, '/');
//            $src      = UrlBuilder::getImg($src);
//        }
        $this->src = UrlBuilder::getImg($src);

        return $this;
    }


    /**
     * Generates and returns the HTML string for a <select> control
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->src) {
            throw new HtmlException(tr('No src attribute specified'));
        }
        if (!$this->alt) {
            throw new HtmlException(tr('No alt attribute specified'));
        }

        return parent::render();
//// :LEGACY: The following code block exists to support legacy apps that still use 5 arguments for html_img() instead of a params array
//        if (!is_array($params)) {
//            /*
//             * Ensure we have a params array
//             */
//            $params = array('src'    => $params,
//                'alt'    => $alt,
//                'width'  => $width,
//                'height' => $height,
//                'lazy'   => null,
//                'extra'  => $extra);
//        }
//
//        array_ensure ($params, 'src,alt,width,height,class,extra');
//        array_default($params, 'lazy'   , $_CONFIG['cdn']['img']['lazy_load']);
//        array_default($params, 'tag'    , 'img');
//        array_default($params, 'section', 'pub');
//
//        if (!$params['src']) {
//            /*
//             * No image at all?
//             */
//            if (Core::isProductionEnvironment()) {
//                /*
//                 * On production, just notify and ignore
//                 */
//                notify(array('code'    => 'not-specified',
//                    'groups'  => 'developer',
//                    'title'   => tr('No image src specified'),
//                    'message' => tr('No src for image with alt text ":alt"', [':alt' => $params['alt']])));
//                return '';
//            }
//
//            throw new CoreException(tr('No src for image with alt text ":alt"', [':alt' => $params['alt']]));
//        }
//
//        if (!Core::isProductionEnvironment()) {
//            if (!$params['src']) {
//                throw new CoreException(tr('No image src specified'));
//            }
//
//            if (!$params['alt']) {
//                throw new CoreException(tr('No image alt text specified for src ":src"', [':src' => $params['src']]));
//            }
//
//        } else {
//            if (!$params['src']) {
//                notify(array('code'   => 'not-specified',
//                    'groups' => 'developer',
//                    'title'  => tr('html_img(): No image src specified')));
//            }
//
//            if (!$params['alt']) {
//                notify(array('code'    => 'not-specified',
//                    'groups'  => 'developer',
//                    'title'   => tr('No image alt specified'),
//                    'message' => tr('html_img(): No image alt text specified for src ":src"', array(':src' => $params['src']))));
//            }
//        }
//
//        /*
//         * Correct the src parameter if it doesn't contain a domain yet by
//         * adding the CDN domain
//         *
//         * Also check if the file should be automatically converted to a
//         * different format
//         */
//        $params['src'] = html_img_src($params['src'], $external, $file_src, $original_src, $params['section']);
//
//        /*
//         * Atumatically detect width / height of this image, as it is not
//         * specified
//         */
//        try {
//// :TODO: Add support for memcached
//            if (isset($cache[$params['src']])) {
//                $image = $cache[$params['src']];
//
//            } else {
//                $image = sql_get('SELECT `width`,
//                                         `height`
//
//                                  FROM   `html_img_cache`
//
//                                  WHERE  `url`       = :url
//                                  AND    `created_on` > NOW() - INTERVAL 1 DAY
//                                  AND    `status`    IS NULL',
//
//                    array(':url' => $params['src']));
//
//                if ($image) {
//                    /*
//                     * Database cache found, add it to local cache
//                     */
//                    $cache[$params['src']] = array('width'  => $image['width'],
//                        'height' => $image['height']);
//
//                }
//            }
//
//        }catch(Exception $e) {
//            notify($e);
//            $image = null;
//        }
//
//        if (!$image) {
//            try {
//                /*
//                 * Check if the URL comes from this domain (so we can
//                 * analyze the files directly on this server) or a remote
//                 * domain (we have to download the files first to analyze
//                 * them)
//                 */
//                if ($external) {
//                    /*
//                     * Image comes from a domain, fetch to temp directory to analize
//                     */
//                    try {
//                        $file  = file_move_to_target($file_src, DIRECTORY_TMP, false, true);
//                        $image = getimagesize(DIRECTORY_TMP.$file);
//
//                    }catch(Exception $e) {
//                        switch ($e->getCode()) {
//                            case 404:
//                                log_file(tr('html_img(): Specified image ":src" does not exist', array(':src' => $file_src)));
//                                break;
//
//                            case 403:
//                                log_file(tr('html_img(): Specified image ":src" got access denied', array(':src' => $file_src)));
//                                break;
//
//                            default:
//                                log_file(tr('html_img(): Specified image ":src" got error ":e"', array(':src' => $file_src, ':e' => $e->getMessage())));
//                                throw $e->makeWarning(true);
//                        }
//
//                        /*
//                         * Image doesnt exist
//                         */
//                        notify(array('code'    => 'not-exists',
//                            'groups'  => 'developer',
//                            'title'   => tr('Image does not exist'),
//                            'message' => tr('html_img(): Specified image ":src" does not exist', array(':src' => $file_src))));
//
//                        $image[0] = 0;
//                        $image[1] = 0;
//                    }
//
//                    if (!empty($file)) {
//                        file_delete(DIRECTORY_TMP.$file);
//                    }
//
//                } else {
//                    /*
//                     * Local image. Analize directly
//                     */
//                    if (file_exists($file_src)) {
//                        try {
//                            $image = getimagesize($file_src);
//
//                        }catch(Exception $e) {
//                            switch ($e->getCode()) {
//                                case 404:
//                                    log_file(tr('html_img(): Specified image ":src" does not exist', array(':src' => $file_src)));
//                                    break;
//
//                                case 403:
//                                    log_file(tr('html_img(): Specified image ":src" got access denied', array(':src' => $file_src)));
//                                    break;
//
//                                default:
//                                    log_file(tr('html_img(): Specified image ":src" got error ":e"', array(':src' => $file_src, ':e' => $e->getMessage())));
//                                    throw $e->makeWarning(true);
//                            }
//
//                            /*
//                             * Image doesnt exist
//                             */
//                            notify(array('code'    => 'not-exists',
//                                'groups'  => 'developer',
//                                'title'   => tr('Image does not exist'),
//                                'message' => tr('html_img(): Specified image ":src" does not exist', array(':src' => $file_src))));
//
//                            $image[0] = 0;
//                            $image[1] = 0;
//                        }
//
//                    } else {
//                        /*
//                         * Image doesn't exist.
//                         */
//                        log_console(tr('html_img(): Can not analyze image ":src", the local directory ":directory" does not exist', array(':src' => $params['src'], ':directory' => $file_src)), 'yellow');
//                        $image[0] = 0;
//                        $image[1] = 0;
//                    }
//                }
//
//                $image['width']  = $image[0];
//                $image['height'] = $image[1];
//                $status          = null;
//
//            }catch(Exception $e) {
//                notify($e);
//
//                $image['width']  = 0;
//                $image['height'] = 0;
//                $status          = $e->getCode();
//            }
//
//            if (!$image['height'] or !$image['width']) {
//                log_console(tr('html_img(): image ":src" has invalid dimensions with width ":width" and height ":height"', array(':src' => $params['src'], ':width' => $image['width'], ':height' => $image['height'])), 'yellow');
//
//            } else {
//                try {
//                    /*
//                     * Store image info in local and db cache
//                     */
//// :TODO: Add support for memcached
//                    $cache[$params['src']] = array('width'  => $image['width'],
//                        'height' => $image['height']);
//
//                    sql_query('INSERT INTO `html_img_cache` (`status`, `url`, `width`, `height`)
//                               VALUES                       (:status , :url , :width , :height )
//
//                               ON DUPLICATE KEY UPDATE `status`    = NULL,
//                                                       `created_on` = NOW()',
//
//                        array(':url'    => $params['src'],
//                            ':width'  => $image['width'],
//                            ':height' => $image['height'],
//                            ':status' => $status));
//
//                }catch(Exception $e) {
//                    notify($e);
//                }
//            }
//        }
//
//        if (!$params['width'] or !$params['height']) {
//            /*
//             * Use image width and height
//             */
//            $params['width']  = $image['width'];
//            $params['height'] = $image['height'];
//
//        } else {
//            /*
//             * Is the image width and or height larger than specified? If so,
//             * auto rescale!
//             */
//            if (!is_numeric($params['width']) and ($params['width'] > 0)) {
//                if (!$image['width']) {
//                    notify(new CoreException(tr('Detected invalid "width" parameter specification for image ":src", and failed to get real image width too, ignoring "width" attribute', array(':width' => $params['width'], ':src' => $params['src'])), 'warning/invalid'));
//                    $params['width'] = null;
//
//                } else {
//                    notify(new CoreException(tr('Detected invalid "width" parameter specification for image ":src", forcing real image width ":real" instead', array(':width' => $params['width'], ':real' => $image['width'], ':src' => $params['src'])), 'warning/invalid'));
//                    $params['width'] = $image['width'];
//                }
//            }
//
//            if (!is_numeric($params['height']) and ($params['height'] > 0)) {
//                if (!$image['height']) {
//                    notify(new CoreException(tr('Detected invalid "height" parameter specification for image ":src", and failed to get real image height too, ignoring "height" attribute', array(':height' => $params['height'], ':src' => $params['src'])), 'warning/invalid'));
//                    $params['height'] = null;
//
//                } else {
//                    notify(new CoreException(tr('Detected invalid "height" parameter specification for image ":src", forcing real image height ":real" instead', array(':height' => $params['height'], ':real' => $image['height'], ':src' => $params['src'])), 'warning/invalid'));
//                    $params['height'] = $image['height'];
//                }
//            }
//
//            /*
//             * If the image is not an external image, and we have a specified
//             * width and height for the image, and we should auto resize then
//             * check if the real image dimensions fall within the specified
//             * dimensions. If not, automatically resize the image
//             */
//            if ($_CONFIG['cdn']['img']['auto_resize'] and !$external and $params['width'] and $params['height']) {
//                if (($image['width'] > $params['width']) or ($image['height'] > $params['height'])) {
//                    log_file(tr('Image src ":src" is larger than its specification, sending resized image instead', array(':src' => $params['src'])), 'html', 'warning');
//
//                    /*
//                     * Determine the resize dimensions
//                     */
//                    if (!$params['height']) {
//                        $params['height'] = $image['height'];
//                    }
//
//                    if (!$params['width']) {
//                        $params['width']  = $image['width'];
//                    }
//
//                    /*
//                     * Determine the file target name and src
//                     */
//                    if (str_contains($params['src'], '@2x')) {
//                        $pre    = Strings::until($params['src'], '@2x');
//                        $post   = str_from ($params['src'], '@2x');
//                        $target = $pre.'@'.$params['width'].'x'.$params['height'].'@2x'.$post;
//
//                        $pre         = Strings::until($file_src, '@2x');
//                        $post        = str_from ($file_src, '@2x');
//                        $file_target = $pre.'@'.$params['width'].'x'.$params['height'].'@2x'.$post;
//
//                    } else {
//                        $pre    = Strings::untilReverse($params['src'], '.');
//                        $post   = str_rfrom ($params['src'], '.');
//                        $target = $pre.'@'.$params['width'].'x'.$params['height'].'.'.$post;
//
//                        $pre         = Strings::untilReverse($file_src, '.');
//                        $post        = str_rfrom ($file_src, '.');
//                        $file_target = $pre.'@'.$params['width'].'x'.$params['height'].'.'.$post;
//                    }
//
//                    /*
//                     * Resize or do we have a cached version?
//                     */
//                    try {
//                        if (!file_exists($file_target)) {
//                            log_file(tr('Resized version of ":src" does not yet exist, converting', array(':src' => $params['src'])), 'html', 'VERBOSE/cyan');
//                            load_libs('image');
//
//                            File::new()->executeMode(dirname($file_src), 0770, function() use ($file_src, $file_target, $params) {
//                                global $_CONFIG;
//
//                                image_convert(array('method' => 'resize',
//                                    'source' => $file_src,
//                                    'target' => $file_target,
//                                    'x'      => $params['width'],
//                                    'y'      => $params['height']));
//                            });
//                        }
//
//                        /*
//                         * Convert src to the resized target
//                         */
//                        $params['src'] = $target;
//                        $file_src      = $file_target;
//
//                    }catch(Exception $e) {
//                        /*
//                         * Failed to auto resize the image. Notify and stay with
//                         * the current version meanwhile.
//                         */
//                        $e->addMessages(tr('html_img(): Failed to auto resize image ":image", using non resized image with incorrect width / height instead', array(':image' => $file_src)));
//                        notify($e->makeWarning(true));
//                    }
//                }
//            }
//        }
//
//        if ($params['height']) {
//            $params['height'] = ' height="'.$params['height'].'"';
//
//        } else {
//            $params['height'] = '';
//        }
//
//        if ($params['width']) {
//            $params['width'] = ' width="'.$params['width'].'"';
//
//        } else {
//            $params['width'] = '';
//        }
//
//        if (isset($params['style'])) {
//            $params['extra'] .= ' style="'.$params['style'].'"';
//        }
//
//        if (isset($params['class'])) {
//            $params['extra'] .= ' class="'.$params['class'].'"';
//        }
//
//        if ($params['lazy']) {
//            if ($params['extra']) {
//                if (str_contains($params['extra'], 'class="')) {
//                    /*
//                     * Add lazy class to the class definition in "extra"
//                     */
//                    $params['extra'] = str_replace('class="', 'class="lazy ', $params['extra']);
//
//                } else {
//                    /*
//                     * Add class definition with "lazy" to extra
//                     */
//                    $params['extra'] = ' class="lazy" '.$params['extra'];
//                }
//
//            } else {
//                /*
//                 * Set "extra" to be class definition with "lazy"
//                 */
//                $params['extra'] = ' class="lazy"';
//            }
//
//            $this->render = '';
//
//            if (empty($core->register['lazy_img'])) {
//                /*
//                 * Use lazy image loading
//                 */
//                try {
//                    if (!file_exists(DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/js/jquery.lazy/jquery.lazy.js')) {
//                        /*
//                         * jquery.lazy is not available, auto install it.
//                         */
//                        $file = download('https://github.com/eisbehr-/jquery.lazy/archive/master.zip');
//                        $directory = cli_unzip($file);
//
//                        File::new()->executeMode(DIRECTORY_ROOT.'www/en/pub/js', 0770, function() use ($directory) {
//                            file_delete(DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/js/jquery.lazy/', DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/js/');
//                            rename($directory.'jquery.lazy-master/', DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/js/jquery.lazy');
//                        });
//
//                        file_delete($directory);
//                    }
//
//                    html_load_js('jquery.lazy/jquery.lazy');
//                    load_config('lazy_img');
//
//                    /*
//                     * Build jquery.lazy options
//                     */
//                    $options = array();
//
//                    foreach ($_CONFIG['lazy_img'] as $key => $value) {
//                        if ($value === null) {
//                            continue;
//                        }
//
//                        switch ($key) {
//                            /*
//                             * Booleans
//                             */
//                            case 'auto_destroy':
//                                // no-break
//                            case 'chainable':
//                                // no-break
//                            case 'combined':
//                                // no-break
//                            case 'enable_throttle':
//                                // no-break
//                            case 'visible_only':
//                                // no-break
//
//                                /*
//                                 * Numbers
//                                 */
//                            case 'delay':
//                                // no-break
//                            case 'effect_time':
//                                // no-break
//                            case 'threshold':
//                                // no-break
//                            case 'throttle':
//                                /*
//                                 * All these need no quotes
//                                 */
//                                $options[str_underscore_to_camelcase($key)] = $value;
//                                break;
//
//                            /*
//                             * Callbacks
//                             */
//                            case 'after_load':
//                                // no-break
//                            case 'on_load':
//                                // no-break
//                            case 'before_load':
//                                // no-break
//                            case 'on_error':
//                                // no-break
//                            case 'on_finished_all':
//                                /*
//                                 * All these need no quotes
//                                 */
//                                $options[str_underscore_to_camelcase($key)] = 'function(e) {'.$value.'}';
//                                break;
//
//                            /*
//                             * Strings
//                             */
//                            case 'append_scroll':
//                                // no-break
//                            case 'bind':
//                                // no-break
//                            case 'default_image':
//                                // no-break
//                            case 'effect':
//                                // no-break
//                            case 'image_base':
//                                // no-break
//                            case 'name':
//                                // no-break
//                            case 'placeholder':
//                                // no-break
//                            case 'retina_attribute':
//                                // no-break
//                            case 'scroll_direction':
//                                /*
//                                 * All these need quotes
//                                 */
//                                $options[str_underscore_to_camelcase($key)] = '"'.$value.'"';
//                                break;
//
//                            default:
//                                throw new CoreException(tr('Unknown lazy_img option ":key" specified. Please check the $_CONFIG[lazy_img] configuration!', [':key' => $key]));
//                        }
//                    }
//
//                    $core->register['lazy_img'] = true;
//                    $this->render .= html_script(array('event'  => 'function',
//                        'script' => '$(".lazy").Lazy({'.array_implode_with_keys($options, ',', ':').'});'));
//
//                }catch(Exception $e) {
//                    /*
//                     * Oops, jquery.lazy failed to install or load. Notify, and
//                     * ignore, we will just continue without lazy loading.
//                     */
//                    notify(new CoreException(tr('html_img(): Failed to install or load jquery.lazy'), $e));
//                }
//            }
//
//            $this->render .= '<'.$params['tag'].' data-src="'.$params['src'].'" alt="'.htmlentities($params['alt']).'"'.$params['width'].$params['height'].$params['extra'].'>';
//
//            return parent::render();
//        }
//
//        return '<'.$params['tag'].' src="'.$params['src'].'" alt="'.htmlentities($params['alt']).'"'.$params['width'].$params['height'].$params['extra'].'>';
    }


    /**
     * Add the system arguments to the arguments list
     *
     * @return IteratorInterface
     */
    protected function renderAttributes(): IteratorInterface
    {
        return parent::renderAttributes()
                     ->appendSource([
                         'src' => $this->src,
                         'alt' => $this->alt,
                     ]);
    }


    /**
     * Returns the lowercase extension of the file
     *
     * @param string $url
     *
     * @return string|null
     */
    protected function getExtension(string $url): ?string
    {
        $extension = Strings::fromReverse($url, '.');

        return strtolower($extension);
    }


    /**
     * Returns the extension of the file
     *
     * @param string $extension
     *
     * @return string|null
     */
    protected function getFormat(string $extension): ?string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'jpg',
            default       => $extension,
        };

    }


    /**
     * Returns if this image type is accepted or not
     *
     * @param string $format
     *
     * @return bool
     */
    protected function isAccepted(string $format): bool
    {
        return Request::accepts('image/' . $format);
    }


//    /**
//     * Convert the current image file to the requested format
//     *
//     * @return void
//     */
//    protected function convert(): void
//    {
//
//        if (!Config::test('cdn.images.convert.' . $this->format)) {
//            // No auto conversion to be done for this image
//            return;
//        }
//
//        // Automatically convert the image to the specified format for automatically optimized images
//        $target_part = Strings::untilReverse($this->source, '.') . '.' . Config::get('cdn.images.convert.' . $this->format);
//        $target      = Strings::untilReverse($this->source , '.') . '.' . Config::get('cdn.images.convert.' . $this->format);
//
//        Log::action(tr('Automatically converting ":format" format image ":src" to format ":target"', [
//            ':format' => $this->format,
//            ':src'    => $this->source,
//            ':target' => Config::get('cdn.images.convert.' . $this->format)
//        ]));
//
//        try {
//            if (!file_exists($target)) {
//                Log::warning(tr('Modified format target ":target" does not exist, converting original source', [
//                    ':target' => $target
//                ]));
//
//                // Ensure target is readable and convert
//                Directory::new($target)->execute()
//                    ->setMode(0660)
//                    ->onDirectoryOnly(function() use ($target) {
//                        Image::new()->convert($this->source)
//                            ->setFile($target)
//                            ->setMethod('custom')
//                            ->setFormat(Config::get('cdn.images.convert.' . $this->format))
//                            ->execute();
//                    });
//            }
//
//            // Convert src back to URL again
//            $this->file_src = $target;
//            $this->src      = UrlBuilder::getImg($target_part);
//
//        }catch(Throwable $e) {
//            // Failed to upgrade image. Use the original image
//            $e->makeWarning(true);
//            $e->addMessages(tr('Failed to auto convert image ":src" to format ":format". Leaving image as-is', [
//                ':src'    => $this->src,
//                ':format' => Config::get('cdn.images.convert.' . $this->format)
//            ]));
//
//            Notification($e);
//        }
//    }
}
