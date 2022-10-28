<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Core\Log;
use Phoundation\Filesystem\File;
use Phoundation\Http\Html\Exception\HtmlException;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Uglify;
use Throwable;



/**
 * Class Script
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 * @see Select
 */
class Script extends Element
{
    /**
     * Generates and returns the HTML string for a <script> element
     *
     * @note: If web.javascript.delay is configured true, it will return an empty string and add the string to the
     *        footer script tags list instead so that it will be loaded at the end of the page for speed
     * @return string
     */
    public function render(): string
    {
        /*
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
        static $count = 0;

        array_params($script, 'script');
        array_default($script, 'event'  , $event);
        array_default($script, 'extra'  , $extra);
        array_default($script, 'type'   , $type);
        array_default($script, 'to_file', null);
        array_default($script, 'list'   , 'scripts');
        array_default($script, 'delayed', $_CONFIG['cdn']['js']['load_delayed']);

        if ($script['to_file'] === null) {
            // The option if this javascript should be written to an external file should be taken from the
            // configuration
            $script['to_file'] = $_CONFIG['cdn']['js']['internal_to_file'];
        }

        if (!$script['script']) {
            // No javascript was specified, Notification developers
            Notification::create()
                ->setException(new HtmlException(tr('No javascript code specified')))
                ->send();
            return '';
        }

        switch ($script['script'][0]) {
            case '>':
                // Keep this script internal! This is required when script contents contain session sensitive data, or
                // may even change per page
                $return            = '<script type="'.$type.'" src="'.cdn_domain('js/'.substr($script['script'], 1)).'"'.($extra ? ' '.$extra : '').'></script>';
                $script['to_file'] = false;
                break;

            case '!':
                // Keep this script internal! This is required when script contents contain session sensitive data, or
                // may even change per page
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
                    // Don't wrap the specified script in an event wrapper
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

        // Store internal script in external files, or keep them internal?
        if ($script['to_file']) {
            try {
                // Create the cached file names
                $base = 'cached-'.substr($core->register['script'], 0, -4).'-'.($core->register['script_file'] ? $core->register['script_file'].'-' : '').$count;
                $file = ROOT.'www/'.LANGUAGE.(Core::getCallType('admin') ? '/admin' : '').'/pub/js/'.$base;

                Log::action(tr('Creating externally cached javascript file ":file"', [':file' => $file.'.js']));

                // Check if the cached file exists and is not too old.
                if (file_exists($file.'.js')) {
                    if (!filesize($file.'.js')) {
                        // The javascript file is empty
                        Log::warning(tr('Deleting externally cached javascript file ":file" because the file is 0 bytes', [':file' => $file.'.js']));

                        File::executeMode(ROOT.'www/'.LANGUAGE.'/pub/js', 0770, function() use ($file) {
                            file_chmod($file.'.js,'.$file.'.min.js', 'ug+w', ROOT.'www/'.LANGUAGE.'/pub/js');
                            file_delete([
                                'patterns'       => $file.'.js,'.$file.'.min.js',
                                'force_writable' => true,
                                'restrictions'   => ROOT.'www/'.LANGUAGE.'/pub/js'
                            ]);
                        });

                    } elseif (($_CONFIG['cdn']['cache_max_age'] > 60) and ((filemtime($file.'.js') + $_CONFIG['cdn']['cache_max_age']) < time())) {
                        // External cached file is too old
                        Log::warning(tr('Deleting externally cached javascript file ":file" because the file cache time expired', [':file' => $file.'.js']));

                        File::executeMode(ROOT.'www/'.LANGUAGE.'/pub/js', 0770, function() use ($file) {
                            file_delete([
                                'patterns'       => $file.'.js,'.$file.'.min.js',
                                'force_writable' => true,
                                'restrictions'   => ROOT.'www/'.LANGUAGE.'/pub/js'
                            ]);
                        });
                    }
                }

                // If file does not exist, create it now. Check again if it exist, because the previous function may
                // have possibly deleted it
                if (!file_exists($file.'.js')) {
                    File::executeMode(dirname($file), 0770, function() use ($file, $return) {
                        Log::action(tr('Writing internal javascript to externally cached file ":file"', [':file' => $file.'.js']));
                        file_put_contents($file.'.js', $return);
                    });
                }

                // Always minify the file. On local machines where minification is turned off this is not a problem,
                // it should take almost zero resources, and it will immediately test minification for production as
                // well.
                if (!file_exists($file.'.min.js')) {
                    try {
                        Uglify::js($file.'.js');

                    }catch(Throwable $e) {
                        // Minify process failed. Notification and fall back on a plain copy
                        Notification::create()
                            ->setException($e)
                            ->send();

                        copy($file.'.js', $file.'.min.js');
                    }
                }

                // Add the file to the html javascript load list
                Html::loadJs($base, $script['list']);

                $count++;
                return '';

            }catch(Throwable $e) {
                // Moving internal javascript to external files failed, Notification developers
                Notification::create()
                    ->setException($e)
                    ->send();

                // Add a <script> element because now we'll include it into the HTML anyway
                $return = ' <script type="'.$type.'"'.($extra ? ' '.$extra : '').'>
                        '.$return.'
                    </script>';
            }
        }

        // Javascript is included into the webpage directly
        // Core::register[script] tags are added all at the end of the page for faster loading
        if (!$script['delayed']) {
            return $return;
        }

        // If delayed, add it to the footer, else return it directly for inclusion at the point where the
        // Html::script() function wascalled
        if (isset($core->register['script_delayed'])) {
            $core->register['script_delayed'] .= $return;

        } else {
            $core->register['script_delayed']  = $return;
        }

        $count++;
        return '';
    }
}