<?php
/*
 * PHOUNDATION DEFAULT LAZY IMAGE LOADING CONFIGURATION FILE
 *
 * DO NOT MODIFY THIS FILE!
 *
 * This file contains default valuesthat may be overwritten when you perform a
 * system update! Always update the following configuration files if you need to
 * make configuration changes
 *
 * production_lazy_img.php
 * ENVIRONMENT_lazy_img.php (Where ENVIRONMENT is the environment for which you wish to change the configuration)
 *
 * ALL CONFIGURATION ENTRIES ARE ORDERED ALPHABETICALLY, ONLY FUNCTION CALLBACKS ARE AT THE BOTTOM FOR CONVENIENCE
 *
 * ALL NULL ENTRIES WILL BE IGNORED
 *
 * @author Sven Oostenbrink <support@capmega.com>,
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Configuration
 * @see http://jquery.eisbehr.de/lazy/ for more documentation on the jquery.lazy library
 * @package html
 */
$_CONFIG['lazy_img']                                                            = array('auto_destroy'     => true,             // Will automatically destroy the instance when no further elements are available to handle.
                                                                                        'append_scroll'    => null,             // An element to listen on for scroll events, useful when images are stored in a container.
                                                                                        'bind'             => null,             // If set to load Lazy starts working directly after page load. If you want to use Lazy on own events set it to event.
                                                                                        'combined'         => 'false',          // 'true' or 'false'. With this parameter, Lazy will combine the event driven and delayed element loading.
                                                                                        'chainable'        => 'true',           // 'true' or 'false'. By default Lazy is chainable and will return all elements. If set to false Lazy will return the created plugin instance itself for further use.
                                                                                        'default_image'    => null,             // Base64 image string, set as default image source for every image without a predefined source attribute.
                                                                                        'delay'            => null,             // If you want to load all elements at once after page load, then you can specify a delay time in milliseconds.
                                                                                        'effect'           => 'fadeIn',         // Function name of the effect you want to use to show the loaded images, like show or fadein. See jquery effects for more options
                                                                                        'effect_time'      => 200,              // Time in milliseconds the effect should use to view the image.
                                                                                        'enable_throttle'  => null,             // Throttle down the loading calls on scrolling event.
                                                                                        'image_base'       => null,             // If defined this will be used as base path for all images loaded by this instance.
                                                                                        'name'             => null,             // Internal name, used for namespaces and bindings.
                                                                                        'placeholder'      => null,             // Base64 image string, set a background on every element as loading placeholder.
                                                                                        'retina_attribute' => null,             // Name of the image tag attribute, where the path for optional retina image is stored.
                                                                                        'scroll_direction' => 'vertical',       // Determines the handles scroll direction. Possible values are both, vertical and horizontal.
                                                                                        'threshold'        => 200,              // Amount of pixels below the viewport, in which all images gets loaded before the user sees them.
                                                                                        'throttle'         => null,             // Amount of pixels below the viewport, in which all images gets loaded before the user sees them.
                                                                                        'visible_only'     => 'true',           // Determine if only visible elements should be load.


                                                                                        'after_load'       => null,             // Callback function, which will be called after the element was loaded. Has current element and response function as parameters. this is the current Lazy instance.
                                                                                        'on_load'          => null,             // Callback function, which will be called if the element could not be loaded. Has current element and response function as parameters. this is the current Lazy instance.
                                                                                        'before_load'      => null,             // Callback function, which will be called before the element gets loaded. Has current element and response function as parameters. this is the current Lazy instance.
                                                                                        'on_error'         => 'console.log("image lazy load error " + e.data("src"));', // Callback function, which will be called whenever an element could not be handled
                                                                                        'on_finished_all'  => null);            // Callback function, which will be called after all elements was loaded or returned an error. This callback has no parameters. this is the current Lazy instance.