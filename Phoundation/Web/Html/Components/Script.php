<?php
/**
 * Class Script
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 * @see       InputSelect
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Data\Traits\TraitDataMinify;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Interfaces\ScriptInterface;
use Phoundation\Web\Html\Enums\EnumAttachJavascript;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Requests\Response;

class Script extends Element implements ScriptInterface
{
    use TraitDataMinify;

    /**
     * Keeps track on where this script will be attached to
     *
     * @var EnumAttachJavascript $attach
     */
    protected EnumAttachJavascript $attach = EnumAttachJavascript::footer;

    /**
     * Async script load
     *
     * @var bool $async
     */
    protected bool $async = false;

    /**
     * If true will move this javascript to an external file which then will be loaded for this page
     *
     * @var bool $to_file
     */
    protected bool $to_file = true;

    /**
     * URL for the script
     *
     * @var string|null $src
     */
    protected ?string $src = null;

    /**
     * Defer script load
     *
     * @var bool $defer
     */
    protected bool $defer = false;

    /**
     * What event to wrap this script into
     *
     * @var EnumJavascriptWrappers $javascript_wrapper
     */
    protected EnumJavascriptWrappers $javascript_wrapper = EnumJavascriptWrappers::dom_content;


    /**
     * Returns if this script is loaded async
     *
     * @return bool
     */
    public function getAsync(): bool
    {
        return $this->async;
    }


    /**
     * Sets if this script is loaded async
     *
     * @param bool $async
     *
     * @return static
     */
    public function setAsync(bool $async): static
    {
        $this->async = $async;

        return $this;
    }


    /**
     * Returns if this script is loaded from a file instead of included internally
     *
     * @return bool
     */
    public function getToFile(): bool
    {
        return $this->to_file;
    }


    /**
     * Sets if this script is loaded from a file instead of included internally
     *
     * @param bool $to_file
     *
     * @return static
     */
    public function setToFile(bool $to_file): static
    {
        $this->to_file = $to_file;

        return $this;
    }


    /**
     * Returns the script src
     *
     * @return string
     */
    public function getSrc(): string
    {
        return $this->src;
    }


    /**
     * Sets the script src
     *
     * @param string $src
     *
     * @return static
     */
    public function setSrc(string $src): static
    {
        $this->src = $src;

        return $this;
    }


    /**
     * Returns where this script is attached to the document
     *
     * @return EnumAttachJavascript
     */
    public function getAttach(): EnumAttachJavascript
    {
        return $this->attach;
    }


    /**
     * Sets where this script is attached to the document
     *
     * @param EnumAttachJavascript $attach
     *
     * @return static
     */
    public function setAttach(EnumAttachJavascript $attach): static
    {
        $this->attach = $attach;

        return $this;
    }


    /**
     * Returns if this script is loaded defer
     *
     * @return bool
     */
    public function getDefer(): bool
    {
        return $this->defer;
    }


    /**
     * Sets if this script is loaded defer
     *
     * @param bool $defer
     *
     * @return static
     */
    public function setDefer(bool $defer): static
    {
        $this->defer = $defer;

        return $this;
    }


    /**
     * Returns the event wrapper code for this script
     *
     * @return EnumJavascriptWrappers
     */
    public function getJavascriptWrapper(): EnumJavascriptWrappers
    {
        return $this->javascript_wrapper;
    }


    /**
     * Sets the event wrapper code for this script
     *
     * @param EnumJavascriptWrappers $javascript_wrapper
     *
     * @return static
     */
    public function setJavascriptWrapper(EnumJavascriptWrappers $javascript_wrapper): static
    {
        $this->javascript_wrapper = $javascript_wrapper;

        return $this;
    }


    /**
     * Generates and returns the HTML string for a <script> element
     *
     * @note: If web.javascript.delay is configured true, it will return an empty string and add the string to the
     *        footer script tags list instead so that it will be loaded at the end of the page for speed
     * @return string|null
     */
    public function render(): ?string
    {
        $render = '';
        if ($this->content) {
            // Apply event wrapper
            switch ($this->javascript_wrapper) {
                case EnumJavascriptWrappers::dom_content:
                    $render = 'document.addEventListener("DOMContentLoaded", function(e) {
                              ' . $this->content . '
                           });' . PHP_EOL;
                    break;
                case EnumJavascriptWrappers::window:
                    $render = 'window.addEventListener("load", function(e) {
                              ' . $this->content . '
                           });' . PHP_EOL;
                    break;
                case EnumJavascriptWrappers::function:
                    $render = '$(function() {
                              ' . $this->content . '
                           });' . PHP_EOL;
                    break;
                case EnumJavascriptWrappers::none:
                    // No wrapping
                    $render = $this->content . PHP_EOL;
                    break;
                default:
                    // TODO: This should be impossible to reach, remove?
                    throw new OutOfBoundsException(tr('Unknown event wrapper ":value" specified', [
                        ':value' => $this->javascript_wrapper,
                    ]));
            }
        } else {
            $this->content = '';
        }
        // Where should this script be attached?
        switch ($this->attach) {
            case EnumAttachJavascript::here:
                return '<script type="text/javascript"' . ($this->async ? ' async' : '') . ($this->defer ? ' defer' : '') . ($this->src ? ' src="' . $this->src . '"' : '') . '>' . $render . '</script>' . PHP_EOL;
            case EnumAttachJavascript::header:
                Response::addToHeader('javascript', [
                    'type'    => 'text/javascript',
                    'content' => $render,
                ]);

                return null;
            case EnumAttachJavascript::footer:
                Response::addToFooter([
                    'type'    => 'text/javascript',
                    'content' => $render,
                ], 'javascript');

                return null;
        }
//        // TODO GARBAGE BELOW, CLEAN UP
//        /*
//         * @note If $_CONFIG[cdn][js][load_delayed] is true, this function will not return anything, and add the generated HTML to $core->register[script_delayed] instead
//         * @note Even if $_CONFIG[cdn][js][load_delayed] is true, the return value of this function should always be received in a variable, just in case the setting gets changes for whatever reason
//         * @version 1.26.0: Added documentation
//         *
//         * @param params string $script The javascript content
//         * @param boolean $dom_content_loaded If set to true, the $script will be changed to document.addEventListener("DOMContentLoaded", function(e) { :script });
//         * @param string $extra If specified, these extra HTML attributes will be added into the <script> tag
//         * @param string $type The <script type="TYPE"> contents. Defaults to "text/javascript"
//         * @return string The body HTML for a <select> tag, containing all <option> tags
//         */
//        static $count = 0;
//
//        array_params($script, 'script');
//        array_default($script, 'event'  , $event);
//        array_default($script, 'extra'  , $extra);
//        array_default($script, 'type'   , $type);
//        array_default($script, 'to_file', null);
//        array_default($script, 'list'   , 'scripts');
//        array_default($script, 'delayed', $_CONFIG['cdn']['js']['load_delayed']);
//
//        if ($script['to_file'] === null) {
//            // The option if this javascript should be written to an external file should be taken from the
//            // configuration
//            $script['to_file'] = $_CONFIG['cdn']['js']['internal_to_file'];
//        }
//
//        if (!$script['script']) {
//            // No javascript was specified, Notification developers
//            Notification::new()
//                ->setException(new HtmlException(tr('No javascript code specified')))
//                ->send();
//            return '';
//        }
//
//        switch ($script['script'][0]) {
//            case '>':
//                // Keep this script internal! This is required when script contents contain session sensitive data, or
//                // may even change per page
//                $return            = '<script type="'.$type.'" src="'.cdn_domain('js/'.substr($script['script'], 1)).'"'.($extra ? ' '.$extra : '').'></script>';
//                $script['to_file'] = false;
//                break;
//
//            case '!':
//                // Keep this script internal! This is required when script contents contain session sensitive data, or
//                // may even change per page
//                $return            = substr($script['script'], 1);
//                $script['to_file'] = false;
//
//                // no break
//
//            default:
//                /*
//                 * Event wrapper
//                 *
//                 * On what event should this script be executed? Eithere boolean true
//                 * for standard "document ready" or your own jQuery
//                 *
//                 * If false, no event wrapper will be added
//                 */
//                if ($script['event']) {
//                    switch ($script['event']) {
//                        case 'dom_content':
//                            $return = 'document.addEventListener("DOMContentLoaded", function(e) {
//                                  '.$script['script'].'
//                               });';
//                            break;
//
//                        case 'window':
//                            $return = 'window.addEventListener("load", function(e) {
//                                  '.$script['script'].'
//                               });';
//                            break;
//
//                        case 'function':
//                            $return = '$(function() {
//                                  '.$script['script'].'
//                               });';
//                            break;
//
//                        default:
//                            throw new HtmlException(tr('Unknown event value ":value" specified', [
//                                ':value' => $script['event']
//                            ]));
//                    }
//
//                } else {
//                    // Don't wrap the specified script in an event wrapper
//                    $return = $script['script'];
//                }
//
//                if ($script['to_file']) {
//                    $return .= ';';
//
//                } else {
//                    $return  = ' <script type="'.$type.'"'.($extra ? ' '.$extra : '').'>
//                             '.$return.'
//                         </script>';
//                }
//        }
//
//        // Store internal script in external files, or keep them internal?
//        if ($script['to_file']) {
//            try {
//                // Create the cached file names
//                $base = 'cached-'.substr($core->register['script'], 0, -4).'-'.($core->register['script_file'] ? $core->register['script_file'].'-' : '').$count;
//                $file = DIRECTORY_ROOT.'www/'.LANGUAGE.(Core::getCallType('admin') ? '/admin' : '').'/pub/js/'.$base;
//
//                Log::action(tr('Creating externally cached javascript file ":file"', [':file' => $file.'.js']));
//
//                // Check if the cached file exists and is not too old.
//                if (file_exists($file.'.js')) {
//                    if (!filesize($file.'.js')) {
//                        // The javascript file is empty
//                        Log::warning(tr('Deleting externally cached javascript file ":file" because the file is 0 bytes', [':file' => $file.'.js']));
//
//                        Directory::new(DIRECTORY_CDN . LANGUAGE . '/js', DIRECTORY_CDN . LANGUAGE . '/js')->execute()
//                            ->setMode(0770)
//                            ->onDirectoryOnly(function() use ($file) {
//                            file_chmod($file.'.js,'.$file.'.min.js', 'ug+w', DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/js');
//                            file_delete([
//                                'patterns'       => $file.'.js,'.$file.'.min.js',
//                                'force_writable' => true,
//                                'restrictions'   => DIRECTORY_ROOT.'www/'.LANGUAGE.'/pub/js'
//                            ]);
//                        });
//
//                    } elseif (($_CONFIG['cdn']['cache_max_age'] > 60) and ((filemtime($file.'.js') + $_CONFIG['cdn']['cache_max_age']) < time())) {
//                        // External cached file is too old
//                        Log::warning(tr('Deleting externally cached javascript file ":file" because the file cache time expired', [':file' => $file.'.js']));
//                        File::new([$file.'.js', $file.'.min.js'], Restrictions::new(DIRECTORY_CDN . LANGUAGE . 'js', true))->delete();
//                    }
//                }
//
//                // If file does not exist, create it now. Check again if it exist, because the previous function may
//                // have possibly deleted it
//                if (!file_exists($file.'.js')) {
//                    Directory::new(dirname($file), Restrictions::new(DIRECTORY_CDN . LANGUAGE . 'js', true))->execute()
//                        ->setMode(0770)
//                        ->onDirectoryOnly(function() use ($file, $return) {
//                            Log::action(tr('Writing internal javascript to externally cached file ":file"', [':file' => $file.'.js']));
//                            file_put_contents($file.'.js', $return);
//                        });
//                }
//
//                // Always minify the file. On local machines where minification is turned off this is not a problem,
//                // it should take almost zero resources, and it will immediately test minification for production as
//                // well.
//                if (!file_exists($file.'.min.js')) {
//                    try {
//                        Uglify::js($file.'.js');
//
//                    }catch(Throwable $e) {
//                        // Minify process failed. Notification and fall back on a plain copy
//                        Notification::new()
//                            ->setException($e)
//                            ->send();
//
//                        copy($file.'.js', $file.'.min.js');
//                    }
//                }
//
//                // Add the file to the html javascript load list
//                Html::loadJs($base, $script['list']);
//
//                $count++;
//                return '';
//
//            }catch(Throwable $e) {
//                // Moving internal javascript to external files failed, Notification developers
//                Notification::new()
//                    ->setException($e)
//                    ->send();
//
//                // Add a <script> element because now we'll include it into the HTML anyway
//                $return = ' <script type="'.$type.'"'.($extra ? ' '.$extra : '').'>
//                        '.$return.'
//                    </script>';
//            }
//        }
//
//        // Javascript is included into the webpage directly
//        // Core::register[script] tags are added all at the end of the page for faster loading
//        if (!$script['delayed']) {
//            return $return;
//        }
//
//        // If delayed, add it to the footer, else return it directly for inclusion at the point where the
//        // Html::script() function was called
//        if (isset($core->register['script_delayed'])) {
//            $core->register['script_delayed'] .= $return;
//
//        } else {
//            $core->register['script_delayed']  = $return;
//        }
//
//        $count++;
//        return '';
    }
}
