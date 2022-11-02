<?php
/*
 * HTML library, containing all sorts of HTML functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package html
 */



/*
 * Only allow execution on shell scripts
 */
function html_only() {
    if (!PLATFORM_HTTP) {
        throw new CoreException('html_only(): This can only be done over HTML', 'htmlonly');
    }
}



/*
 *
 */
function html_echo($html) {
    global $_CONFIG;

    try {
        if (ob_get_contents()) {
            if (Debug::production()) {
                throw new CoreException(tr('html_echo(): Output buffer is not empty'), 'not-empty');
            }

            log_console(tr('html_echo(): Output buffer is not empty'), 'yellow');
        }

        echo $html;
        die();

    }catch(Exception $e) {
        throw new CoreException('html_echo(): Failed', $e);
    }
}



/*
 *
 */
function html_safe($html) {
    try {
        return htmlentities($html);

    }catch(Exception $e) {
        throw new CoreException('html_safe(): Failed', $e);
    }
}



/*
 * Generate and return the HTML footer
 */
function html_iefilter($html, $filter) {
    try {
        if (!$filter) {
            return $html;
        }

        if ($mod = Strings::until(Strings::from($filter, '.'), '.')) {
            return "\n<!--[if ".$mod.' IE '.Strings::fromReverse($filter, '.')."]>\n\t".$html."\n<![endif]-->\n";

        } elseif ($filter == 'ie') {
            return "\n<!--[if IE ]>\n\t".$html."\n<![endif]-->\n";
        }

        return "\n<!--[if IE ".Strings::from($filter, 'ie')."]>\n\t".$html."\n<![endif]-->\n";

    }catch(Exception $e) {
        throw new CoreException('html_iefilter(): Failed', $e);
    }
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
function html_meta($meta) {
    global $_CONFIG, $core;

    try {
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
            notify(new CoreException(tr('html_meta(): No meta title specified for script ":script" (BAD SEO!)', array(':script' => $core->register['script'])), 'warning/not-specified'));

        } elseif (strlen($meta['title']) > 65) {
            $meta['title'] = str_truncate($meta['title'], 65);
            notify(new CoreException(tr('html_meta(): Specified meta title ":title" is larger than 65 characters', array(':title' => $meta['title'])), 'warning/invalid'));
        }

        if (empty($meta['description'])) {
            $meta['description'] = domain(true);
            notify(new CoreException(tr('html_meta(): No meta description specified for script ":script" (BAD SEO!)', array(':script' => $core->register['script'])), 'warning/not-specified'));

        } elseif (strlen($meta['description']) > 155) {
            $meta['description'] = str_truncate($meta['description'], 155);
            notify(new CoreException(tr('html_meta(): Specified meta description ":description" is larger than 155 characters', array(':description' => $meta['description'])), 'warning/invalid'));
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
            notify(new CoreException(tr('html_header(): Meta viewport tag is not specified'), 'warning/not-specified'));
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
                notify(new CoreException(tr('html_meta(): Found $meta[:key], this should be $meta[og][:ogkey], ignoring', array(':key' => $key, ':ogkey' => Strings::from($key, 'og:'))), 'warning/invalid'));

            } else {
                $return .= '<meta name="'.$key.'" content="'.$value.'">';
            }
        }

        return $return;

    }catch(Exception $e) {
        /*
         * Only notify since this is not a huge issue on production
         */
        notify($e);
    }
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
function html_og($og, $meta) {
    global $_CONFIG, $core;

    try {
        Arrays::ensure($meta, 'title,description');
        Arrays::ensure($og, 'description,url,image');
        array_default($og, 'url'        , domain(true));
        array_default($og, 'site_name'  , $_CONFIG['name']);
        array_default($og, 'title'      , $meta['title']);
        array_default($og, 'image'      , (isset($_CONFIG['logo']['og']) ? cdn_domain($_CONFIG['logo']['og']) : ''));
        array_default($og, 'description', $meta['description']);
        array_default($og, 'locale'     , $core->register['locale']);
        array_default($og, 'type'       , 'website');

        $return = '';

        if (strlen($og['description']) > 65) {
            $og['description'] = str_truncate($og['description'], 65);
            notify(new CoreException(tr('html_og(): Specified OG description ":description" is larger than 65 characters, truncating to correct size', array(':description' => $og['description'])), 'warning/invalid'));
        }

        if (strlen($og['title']) > 35) {
            $og['title'] = str_truncate($og['title'], 35);
            notify(new CoreException(tr('html_og(): Specified OG title ":title" is larger than 35 characters, truncating to correct size', array(':title' => $og['title'])), 'warning/invalid'));
        }

        $og['locale'] = Strings::until($og['locale'], '.');

        foreach ($og as $property => $content) {
            if (empty($content)) {
                notify(new CoreException(tr('html_og(): Missing property content for meta og key ":property". Please add this data for SEO!', array(':property' => $property)), 'warning/not-specified'));
            }

            $return .= '<meta property="og:'.$property.'" content="'.$content.'">';
        }

        return $return;

    }catch(Exception $e) {
        /*
         * Only notify since this is not a huge issue on production
         */
        notify($e);
    }
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
function html_footer() {
    global $_CONFIG, $core;

    try {
        $html = '';

        if (Debug::enabled()) {
            $html .= debug_bar();
        }

        return $html;

    }catch(Exception $e) {
        throw new CoreException('html_footer(): Failed', $e);
    }
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
function html_end() {
    global $core;

    try {
        if ($core->register['footer']) {
            return $core->register['footer'].'</body></html>';
        }

        return '</body></html>';

    }catch(Exception $e) {
        throw new CoreException('html_end(): Failed', $e);
    }
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
function html_flash($class = null) {
    global $_CONFIG, $core;

    try {
        if (!PLATFORM_HTTP) {
            throw new CoreException('html_flash(): This function can only be executed on a webserver!');
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
            array_default($flash, 'class', null);

            if ($flash['class'] and ($flash['class'] != $class)) {
                continue;
            }

            array_default($flash, 'title', null);
            array_default($flash, 'type' , null);
            array_default($flash, 'html' , null);
            array_default($flash, 'text' , null);

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
                    throw new CoreException(tr('html_flash(): Unknown html flash type ":type" specified. Please check your $_CONFIG[flash][type] configuration', array(':type' => $_CONFIG['flash']['type'])), 'unknown');
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

    }catch(Exception $e) {
        throw new CoreException('html_flash(): Failed', $e);
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
function html_flash_set($params, $type = 'info', $class = null) {
    global $_CONFIG, $core;

    try {
        if (!PLATFORM_HTTP) {
            throw new CoreException(tr('html_flash_set(): This function can only be executed on a webserver!'), 'invalid');
        }

        if (!$params) {
            /*
             * Wut? no message?
             */
            throw new CoreException(tr('html_flash_set(): No messages specified'), 'not-specified');
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

            throw new CoreException(tr('html_flash_set(): Invalid call data ":data", should contain at least "text" or "html" or "title"!', array(':data' => $params)), 'invalid');
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
        throw new CoreException('html_flash_set(): Failed', $e);
    }
}



///*
// * Returns true if there is an HTML message with the specified class
// */
//function html_flash_class($class = null) {
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
//        throw new CoreException('html_flash_class(): Failed', $e);
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
function html_a($params) {
    try {
        array_params ($params, 'href');
        array_default($params, 'name'  , '');
        array_default($params, 'target', '');
        array_default($params, 'rel'   , '');

        switch ($params['target']) {
            case '_blank':
                $params['rel'] .= ' noreferrer noopener';
                break;
        }

        if (empty($params['href'])) {
            throw new CoreException('html_a(): No href specified', 'not-specified');
        }

        if ($params['name']) {
            $params['name'] = ' name="'.$params['name'].'"';
        }

        if ($params['class']) {
            $params['class'] = ' class="'.$params['class'].'"';
        }

        $return = '<a href="'.$params['href'].'"'.$params['name'].$params['class'].$params['rel'].'">';

        return $return;

    }catch(Exception $e) {
        throw new CoreException('html_a(): Failed', $e);
    }
}



/*
 * Return HTML for a submit button
 * If the button should not cause validation, then use "no_validation" true
 */
function html_submit($params, $class = '') {
    static $added;

    try {
        array_params ($params, 'value');
        array_default($params, 'name'         , 'dosubmit');
        array_default($params, 'class'        , $class);
        array_default($params, 'no_validation', false);
        array_default($params, 'value'        , 'submit');

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

    }catch(Exception $e) {
        throw new CoreException('html_submit(): Failed', $e);
    }
}




/*
 * Return favicon HTML
 */
function html_favicon($icon = null, $mobile_icon = null, $sizes = null, $precomposed = false) {
    global $_CONFIG, $core;

    try {
        array_params($params, 'icon');
        array_default($params, 'mobile_icon', $mobile_icon);
        array_default($params, 'sizes'      , $sizes);
        array_default($params, 'precomposed', $precomposed);

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

    }catch(Exception $e) {
        throw new CoreException('html_favicon(): Failed', $e);
    }
}



/*
 * Create HTML for an HTML step process bar
 */
function html_list($params, $selected = '') {
    try {
        if (!is_array($params)) {
            throw new CoreException('html_list(): Specified params is not an array', 'invalid');
        }

        if (empty($params['steps']) or !is_array($params['steps'])) {
            throw new CoreException('html_list(): params[steps] is not specified or not an array', 'invalid');
        }

        array_default($params, 'selected'    , $selected);
        array_default($params, 'class'       , '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'show_counter', false);
        array_default($params, 'use_list'    , true);

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

    }catch(Exception $e) {
        throw new CoreException('html_list(): Failed', $e);
    }
}



/*
 *
 */
function html_status_select($params) {
    try {
        array_params ($params, 'name');
        array_default($params, 'name'    , 'status');
        array_default($params, 'none'    , '');
        array_default($params, 'resource', false);
        array_default($params, 'selected', '');

        return html_select($params);

    }catch(Exception $e) {
        throw new CoreException('html_status_select(): Failed', $e);
    }
}



/*
 *
 */
function html_hidden($source, $key = 'id') {
    try {
        return '<input type="hidden" name="'.$key.'" value="'.isset_get($source[$key]).'">';

    }catch(Exception $e) {
        throw new CoreException('html_hidden(): Failed', $e);
    }
}



// :OBSOLETE: This is now done in http_headers
///*
// * Create the page using the custom library c_page function and add content-length header and send HTML to client
// */
//function html_send($params, $meta, $html) {
//    $html = c_page($params, $meta, $html);
//
//    header('Content-Length: '.mb_strlen($html));
//    echo $html;
//    die();
//}



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
function html_img_src($src, &$external = null, &$file_src = null, &$original_src = null, $section = 'pub') {
    global $_CONFIG;

    try {
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
                    $file_src = PATH_ROOT.'www/'.LANGUAGE.$file_part;

                } else {
                    $file_src = PATH_ROOT.'data/content'.$file_part;
                }

            } elseif (str_contains($src, domain(''))) {
                /*
                 * Here, mistakenly, the main domain was used for CDN data
                 */
                $file_part = Strings::startsWith(Strings::from($src, domain('')), '/');
                $file_src  = PATH_ROOT.'data/content'.$file_part;
                $external  = false;

                notify(new CoreException(tr('html_img(): The main domain ":domain" was specified for CDN data, please correct this issue', array(':domain' => domain(''))), 'warning/invalid'));

            } else {
                $file_src  = $src;
                $external  = true;
            }

        } else {
            /*
             * Assume all images are PUB images
             */
            $file_part = '/pub'.Strings::startsWith($src, '/');
            $file_src  = PATH_ROOT.'www/'.LANGUAGE.$file_part;
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

    }catch(Exception $e) {
        throw new CoreException('html_img_src(): Failed', $e);
    }
}



/*
 * Create and return an img tag that contains at the least src, alt, height and width
 * If height / width are not specified, then html_img() will try to get the height / width
 * data itself, and store that data in database for future reference
 */
function html_img($params, $alt = null, $width = null, $height = null, $extra = '') {
    global $_CONFIG, $core;
    static $images, $cache = array();

    try {
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
        array_default($params, 'lazy'   , $_CONFIG['cdn']['img']['lazy_load']);
        array_default($params, 'tag'    , 'img');
        array_default($params, 'section', 'pub');

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

            throw new CoreException(tr('html_img(): No src for image with alt text ":alt"', array(':alt' => $params['alt'])), 'no-image');
        }

        if (!Debug::production()) {
            if (!$params['src']) {
                throw new CoreException(tr('html_img(): No image src specified'), 'not-specified');
            }

            if (!$params['alt']) {
                throw new CoreException(tr('html_img(): No image alt text specified for src ":src"', array(':src' => $params['src'])), 'not-specified');
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
                        $file  = file_move_to_target($file_src, PATH_TMP, false, true);
                        $image = getimagesize(PATH_TMP.$file);

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
                        file_delete(PATH_TMP.$file);
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
                    notify(new CoreException(tr('Detected invalid "width" parameter specification for image ":src", and failed to get real image width too, ignoring "width" attribute', array(':width' => $params['width'], ':src' => $params['src'])), 'warning/invalid'));
                    $params['width'] = null;

                } else {
                    notify(new CoreException(tr('Detected invalid "width" parameter specification for image ":src", forcing real image width ":real" instead', array(':width' => $params['width'], ':real' => $image['width'], ':src' => $params['src'])), 'warning/invalid'));
                    $params['width'] = $image['width'];
                }
            }

            if (!is_numeric($params['height']) and ($params['height'] > 0)) {
                if (!$image['height']) {
                    notify(new CoreException(tr('Detected invalid "height" parameter specification for image ":src", and failed to get real image height too, ignoring "height" attribute', array(':height' => $params['height'], ':src' => $params['src'])), 'warning/invalid'));
                    $params['height'] = null;

                } else {
                    notify(new CoreException(tr('Detected invalid "height" parameter specification for image ":src", forcing real image height ":real" instead', array(':height' => $params['height'], ':real' => $image['height'], ':src' => $params['src'])), 'warning/invalid'));
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
                    if (!file_exists(PATH_ROOT.'www/'.LANGUAGE.'/pub/js/jquery.lazy/jquery.lazy.js')) {
                        /*
                         * jquery.lazy is not available, auto install it.
                         */
                        $file = download('https://github.com/eisbehr-/jquery.lazy/archive/master.zip');
                        $path = cli_unzip($file);

                        File::executeMode(PATH_ROOT.'www/en/pub/js', 0770, function() use ($path) {
                            file_delete(PATH_ROOT.'www/'.LANGUAGE.'/pub/js/jquery.lazy/', PATH_ROOT.'www/'.LANGUAGE.'/pub/js/');
                            rename($path.'jquery.lazy-master/', PATH_ROOT.'www/'.LANGUAGE.'/pub/js/jquery.lazy');
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
                                throw new CoreException(tr('html_img(): Unknown lazy_img option ":key" specified. Please check the $_CONFIG[lazy_img] configuration!', array(':key' => $key)), 'unknown');
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
                    notify(new CoreException(tr('html_img(): Failed to install or load jquery.lazy'), $e));
                }
            }

            $html .= '<'.$params['tag'].' data-src="'.$params['src'].'" alt="'.htmlentities($params['alt']).'"'.$params['width'].$params['height'].$params['extra'].'>';

            return $html;
        }

        return '<'.$params['tag'].' src="'.$params['src'].'" alt="'.htmlentities($params['alt']).'"'.$params['width'].$params['height'].$params['extra'].'>';

    }catch(Exception $e) {
        throw new CoreException(tr('html_img(): Failed for src ":src"', array(':src' => isset_get($params['src']))), $e);
    }
}



/*
 * Create and return a video container that has at the least src, alt, height and width
 */
function html_video($params) {
    global $_CONFIG;

    try {
        Arrays::ensure($params, 'src,width,height,more,type');
        array_default($params, 'controls', true);

        if (!Debug::production()) {
            if (!$params['src']) {
                throw new CoreException(tr('html_video(): No video src specified'), 'not-specified');
            }
        }

// :INVESTIGATE: Is better getting default width and height dimensions like in html_img()
// But in this case, we have to use a external "library" to get this done
// Investigate the best option for this!
        if (!$params['width']) {
            throw new CoreException(tr('html_video(): No width specified'), 'not-specified');
        }

        if (!is_natural($params['width'])) {
            throw new CoreException(tr('html_video(): Invalid width ":width" specified', array(':width' => $params['width'])), 'invalid');
        }

        if (!$params['height']) {
            throw new CoreException(tr('html_video(): No height specified'), 'not-specified');
        }

        if (!is_natural($params['height'])) {
            throw new CoreException(tr('html_video(): Invalid height ":height" specified', array(':height' => $params['height'])), 'invalid');
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
            $params['src']  = PATH_ROOT.'www/en'.Strings::startsWith($params['src'], '/');
            $params['type'] = mime_content_type($params['src']);

        } else {
            if (preg_match('/^'.str_replace('/', '\/', str_replace('.', '\.', domain())).'\/.+$/ius', $params['src'])) {
                /*
                 * This is a local video with domain specification
                 */
                $params['src']  = PATH_ROOT.'www/en'.Strings::startsWith(Strings::from($params['src'], domain()), '/');
                $params['type'] = mime_content_type($params['src']);

            } elseif (!Debug::production()) {
                /*
                 * This is a remote video
                 * Remote videos MUST have height and width specified!
                 */
                if (!$params['height']) {
                    throw new CoreException(tr('html_video(): No height specified for remote video'), 'not-specified');
                }

                if (!$params['width']) {
                    throw new CoreException(tr('html_video(): No width specified for remote video'), 'not-specified');
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
                        throw new CoreException(tr('html_video(): Unknown type ":type" specified for remote video', array(':type' => $params['type'])), 'unknown');
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

    }catch(Exception $e) {
        if (!Debug::production()) {
            throw new CoreException('html_video(): Failed', $e);
        }

        notify($e);
    }
}



/*
 *
 */
function html_autosuggest($params) {
    static $sent = array();

    try {
        Arrays::ensure($params);
        array_default($params, 'class'          , '');
        array_default($params, 'input_class'    , 'form-control');
        array_default($params, 'name'           , '');
        array_default($params, 'id'             , $params['name']);
        array_default($params, 'placeholder'    , '');
        array_default($params, 'required'       , false);
        array_default($params, 'tabindex'       , html_tabindex());
        array_default($params, 'extra'          , '');
        array_default($params, 'value'          , '');
        array_default($params, 'source'         , '');
        array_default($params, 'maxlength'      , '');
        array_default($params, 'filter_selector', '');
        array_default($params, 'selector'       , 'form.autosuggest');

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

    }catch(Exception $e) {
        throw new CoreException(tr('html_autosuggest(): Failed'), $e);
    }
}



/*
 * This function will minify the given HTML by removing double spaces, and strip white spaces before and after tags (except space)
 * Found on http://stackoverflow.com/questions/6225351/how-to-minify-php-page-html-output, rewritten for use in base project
 */
function html_minify($html) {
    global $_CONFIG;

    try {
        if ($_CONFIG['cdn']['min']) {
            load_libs('minify');
            return minify_html($html);
        }

        /*
         * Don't do anything. This way, on non debug systems, where this is
         * used to minify HTML output, we can still see normal HTML that is
         * a bit more readable.
         */
        return $html;

    }catch(Exception $e) {
        throw new CoreException(tr('html_minify(): Failed'), $e);
    }
}



/*
 * Generate and return a randon name for the specified $name, and store the
 * link between the two under "group"
 */
 function html_translate($name) {
    static $translations = array();

     try {
        if (!isset($translations[$name])) {
            $translations[$name] = '__HT'.$name.'__'.substr(unique_code('sha256'), 0, 16);
        }

        return $translations[$name];

     }catch(Exception $e) {
         throw new CoreException(tr('html_translate(): Failed'), $e);
     }
 }



/*
 * Return the $_POST value for the translated specified key
 */
function html_untranslate() {
    try {
        $count = 0;

        foreach ($_POST as $key => $value) {
            if (substr($key, 0, 4) == '__HT') {
                $_POST[Strings::until(substr($key, 4), '__')] = $_POST[$key];
                unset($_POST[$key]);
                $count++;
            }
        }

        return $count;

    }catch(Exception $e) {
        throw new CoreException(tr('html_untranslate(): Failed'), $e);
    }
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
function html_fix_checkbox_values() {
    try {
        foreach ($_POST as $key => $value) {
            if (substr($key, 0, 4) === '__CB') {
                if (!array_key_exists(substr($key, 4), $_POST)) {
                    $_POST[substr($key, 4)] = $value;
                }

                unset($_POST[$key]);
            }
        }

     }catch(Exception $e) {
         throw new CoreException(tr('html_fix_checkbox_values(): Failed'), $e);
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
function html_form($params = null) {
    global $_CONFIG;

    try {
        Arrays::ensure($params, 'extra');
        array_default($params, 'id'    , 'form');
        array_default($params, 'name'  , $params['id']);
        array_default($params, 'method', 'post');
        array_default($params, 'action', domain(true));
        array_default($params, 'class' , 'form-horizontal');
        array_default($params, 'csrf'  , $_CONFIG['security']['csrf']['enabled']);

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

    }catch(Exception $e) {
        throw new CoreException(tr('html_form(): Failed'), $e);
    }
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
function html_set_js_cdn_url() {
    global $_CONFIG, $core;

    try {
        $core->register['header'] = html_script('var cdnprefix="'.cdn_domain().'"; var site_prefix="'.domain().'";', false);

    }catch(Exception $e) {
        throw new CoreException(tr('html_set_js_cdn_url(): Failed'), $e);
    }
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
function html_filter_tags($html, $tags, $exception = false) {
    try {
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
                throw new CoreException('html_filter_tags(): Found HTML tags ":tags" which are forbidden', array(':tags', implode(', ', $list)), 'forbidden');
            }

            foreach ($list as $item) {
                $item->parentNode->removeChild($item);
            }
        }

        $html = $dom->saveHTML();
        return $html;

    }catch(Exception $e) {
        throw new CoreException('html_filter_tags(): Failed', $e);
    }
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
function html_loader_screen($params) {
    try {
        array_params($params);
        array_default($params, 'page_selector'        , '');
        array_default($params, 'text'                 , '');
        array_default($params, 'text_style'           , '');
        array_default($params, 'image_src'            , '');
        array_default($params, 'image_alt'            , tr('Loader screen'));
        array_default($params, 'image_width'          , null);
        array_default($params, 'image_height'         , null);
        array_default($params, 'image_top'            , '100px');
        array_default($params, 'image_left'           , null);
        array_default($params, 'image_right'          , null);
        array_default($params, 'image_bottom'         , null);
        array_default($params, 'image_style'          , 'position:relative;');
        array_default($params, 'screen_line_height'   , 0);
        array_default($params, 'screen_background'    , 'white');
        array_default($params, 'screen_color'         , 'black');
        array_default($params, 'screen_remove'        , true);
        array_default($params, 'screen_text_align'    , 'center');
        array_default($params, 'screen_vertical_align', 'middle');
        array_default($params, 'screen_style_extra'   , '');
        array_default($params, 'transition_time'      , 300);
        array_default($params, 'transition_style'     , 'fade');
        array_default($params, 'test_loader_screen'   , false);

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
                    throw new CoreException(tr('html_loader_screen(): Unknown screen transition value ":value" specified', array(':value' => $params['test_loader_screen'])), 'unknown');
            }
        }

        return $html;

    }catch(Exception $e) {
        throw new CoreException('html_loader_screen(): Failed', $e);
    }
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
function html_strip_attributes($source, $allowed_attributes = null) {
    try {
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

    }catch(Exception $e) {
        throw new CoreException(tr('html_strip_attributes(): Failed'), $e);
    }
}
