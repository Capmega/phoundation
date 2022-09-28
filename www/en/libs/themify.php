<?php
/*
 * Themify library
 *
 * This library is used
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Load the themify CDN files to the client.
 *
 * This function will use html_load_css() to inject <link> tags containing the required themify CSS files into the HTML that is sent to the client. The client will then automatically load these CSS files
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @see html_load_css()
 * @package themify
 * @version 2.7.15: Added function and documentation
 *
 * @return void
 */
function themify_load(){
    try{
        html_load_css('themify-icons');

    }catch(Exception $e){
        throw new CoreException('themify_load(): Failed', $e);
    }
}



/*
 * Create HTML <select> with all themify font classes
 */
function themify_select($params) {
    try{
        array_params($params);

        $html = cache_read('select'.isset_get($params['selected']), 'themify');
        if($html) return $html;

        load_libs('html');

        $params['resource'] = themify_array();

        $html = html_select($params);

        return cache_write($html, 'select'.isset_get($params['selected']), 'themify');

    }catch(Exception $e){
        throw new CoreException('themify_select(): Failed', $e);
    }
}



/*
 * Create a target, but don't put anything in it, and return path+filename without extension
 */
function themify_exists($class) {
    return in_array($class, themify_array());
}



/*
 * Return an array with all themify items
 */
function themify_array(){
    return array('ti-arrow-up'                      => 'ti-arrow-up',
                 'ti-arrow-right'                   => 'ti-arrow-right',
                 'ti-arrow-left'                    => 'ti-arrow-left',
                 'ti-arrow-down'                    => 'ti-arrow-down',
                 'ti-arrows-vertical'               => 'ti-arrows-vertical',
                 'ti-arrows-horizontal'             => 'ti-arrows-horizontal',
                 'ti-angle-up'                      => 'ti-angle-up',
                 'ti-angle-right'                   => 'ti-angle-right',
                 'ti-angle-left'                    => 'ti-angle-left',
                 'ti-angle-down'                    => 'ti-angle-down',
                 'ti-angle-double-up'               => 'ti-angle-double-up',
                 'ti-angle-double-right'            => 'ti-angle-double-right',
                 'ti-angle-double-left'             => 'ti-angle-double-left',
                 'ti-angle-double-down'             => 'ti-angle-double-down',
                 'ti-move'                          => 'ti-move',
                 'ti-fullscreen'                    => 'ti-fullscreen',
                 'ti-arrow-top-right'               => 'ti-arrow-top-right',
                 'ti-arrow-top-left'                => 'ti-arrow-top-left',
                 'ti-arrow-circle-up'               => 'ti-arrow-circle-up',
                 'ti-arrow-circle-right'            => 'ti-arrow-circle-right',
                 'ti-arrow-circle-left'             => 'ti-arrow-circle-left',
                 'ti-arrow-circle-down'             => 'ti-arrow-circle-down',
                 'ti-arrows-corner'                 => 'ti-arrows-corner',
                 'ti-split-v'                       => 'ti-split-v',
                 'ti-split-v-alt'                   => 'ti-split-v-alt',
                 'ti-split-h'                       => 'ti-split-h',
                 'ti-hand-point-up'                 => 'ti-hand-point-up',
                 'ti-hand-point-right'              => 'ti-hand-point-right',
                 'ti-hand-point-left'               => 'ti-hand-point-left',
                 'ti-hand-point-down'               => 'ti-hand-point-down',
                 'ti-back-right'                    => 'ti-back-right',
                 'ti-back-left'                     => 'ti-back-left',
                 'ti-exchange-vertical'             => 'ti-exchange-vertical',
                 'ti-wand'                          => 'ti-wand',
                 'ti-save'                          => 'ti-save',
                 'ti-save-alt'                      => 'ti-save-alt',
                 'ti-direction'                     => 'ti-direction',
                 'ti-direction-alt'                 => 'ti-direction-alt',
                 'ti-user'                          => 'ti-user',
                 'ti-link'                          => 'ti-link',
                 'ti-unlink'                        => 'ti-unlink',
                 'ti-trash'                         => 'ti-trash',
                 'ti-target'                        => 'ti-target',
                 'ti-tag'                           => 'ti-tag',
                 'ti-desktop'                       => 'ti-desktop',
                 'ti-tablet'                        => 'ti-tablet',
                 'ti-mobile'                        => 'ti-mobile',
                 'ti-email'                         => 'ti-email',
                 'ti-star'                          => 'ti-star',
                 'ti-spray'                         => 'ti-spray',
                 'ti-signal'                        => 'ti-signal',
                 'ti-shopping-cart'                 => 'ti-shopping-cart',
                 'ti-shopping-cart-full'            => 'ti-shopping-cart-full',
                 'ti-settings'                      => 'ti-settings',
                 'ti-search'                        => 'ti-search',
                 'ti-zoom-in'                       => 'ti-zoom-in',
                 'ti-zoom-out'                      => 'ti-zoom-out',
                 'ti-cut'                           => 'ti-cut',
                 'ti-ruler'                         => 'ti-ruler',
                 'ti-ruler-alt-2'                   => 'ti-ruler-alt-2',
                 'ti-ruler-pencil'                  => 'ti-ruler-pencil',
                 'ti-ruler-alt'                     => 'ti-ruler-alt',
                 'ti-bookmark'                      => 'ti-bookmark',
                 'ti-bookmark-alt'                  => 'ti-bookmark-alt',
                 'ti-reload'                        => 'ti-reload',
                 'ti-plus'                          => 'ti-plus',
                 'ti-minus'                         => 'ti-minus',
                 'ti-close'                         => 'ti-close',
                 'ti-pin'                           => 'ti-pin',
                 'ti-pencil'                        => 'ti-pencil',
                 'ti-pencil-alt'                    => 'ti-pencil-alt',
                 'ti-paint-roller'                  => 'ti-paint-roller',
                 'ti-paint-bucket'                  => 'ti-paint-bucket',
                 'ti-na'                            => 'ti-na',
                 'ti-medall'                        => 'ti-medall',
                 'ti-medall-alt'                    => 'ti-medall-alt',
                 'ti-marker'                        => 'ti-marker',
                 'ti-marker-alt'                    => 'ti-marker-alt',
                 'ti-lock'                          => 'ti-lock',
                 'ti-unlock'                        => 'ti-unlock',
                 'ti-location-arrow'                => 'ti-location-arrow',
                 'ti-layout'                        => 'ti-layout',
                 'ti-layers'                        => 'ti-layers',
                 'ti-layers-alt'                    => 'ti-layers-alt',
                 'ti-key'                           => 'ti-key',
                 'ti-image'                         => 'ti-image',
                 'ti-heart'                         => 'ti-heart',
                 'ti-heart-broken'                  => 'ti-heart-broken',
                 'ti-hand-stop'                     => 'ti-hand-stop',
                 'ti-hand-open'                     => 'ti-hand-open',
                 'ti-hand-drag'                     => 'ti-hand-drag',
                 'ti-flag'                          => 'ti-flag',
                 'ti-flag-alt'                      => 'ti-flag-alt',
                 'ti-flag-alt-2'                    => 'ti-flag-alt-2',
                 'ti-eye'                           => 'ti-eye',
                 'ti-import'                        => 'ti-import',
                 'ti-export'                        => 'ti-export',
                 'ti-cup'                           => 'ti-cup',
                 'ti-crown'                         => 'ti-crown',
                 'ti-comments'                      => 'ti-comments',
                 'ti-comment'                       => 'ti-comment',
                 'ti-comment-alt'                   => 'ti-comment-alt',
                 'ti-thought'                       => 'ti-thought',
                 'ti-clip'                          => 'ti-clip',
                 'ti-check'                         => 'ti-check',
                 'ti-check-box'                     => 'ti-check-box',
                 'ti-camera'                        => 'ti-camera',
                 'ti-announcement'                  => 'ti-announcement',
                 'ti-brush'                         => 'ti-brush',
                 'ti-brush-alt'                     => 'ti-brush-alt',
                 'ti-palette'                       => 'ti-palette',
                 'ti-briefcase'                     => 'ti-briefcase',
                 'ti-bolt'                          => 'ti-bolt',
                 'ti-bolt-alt'                      => 'ti-bolt-alt',
                 'ti-blackboard'                    => 'ti-blackboard',
                 'ti-bag'                           => 'ti-bag',
                 'ti-world'                         => 'ti-world',
                 'ti-wheelchair'                    => 'ti-wheelchair',
                 'ti-car'                           => 'ti-car',
                 'ti-truck'                         => 'ti-truck',
                 'ti-timer'                         => 'ti-timer',
                 'ti-ticket'                        => 'ti-ticket',
                 'ti-thumb-up'                      => 'ti-thumb-up',
                 'ti-thumb-down'                    => 'ti-thumb-down',
                 'ti-stats-up'                      => 'ti-stats-up',
                 'ti-stats-down'                    => 'ti-stats-down',
                 'ti-shine'                         => 'ti-shine',
                 'ti-shift-right'                   => 'ti-shift-right',
                 'ti-shift-left'                    => 'ti-shift-left',
                 'ti-shift-right-alt'               => 'ti-shift-right-alt',
                 'ti-shift-left-alt'                => 'ti-shift-left-alt',
                 'ti-shield'                        => 'ti-shield',
                 'ti-notepad'                       => 'ti-notepad',
                 'ti-server'                        => 'ti-server',
                 'ti-pulse'                         => 'ti-pulse',
                 'ti-printer'                       => 'ti-printer',
                 'ti-power-off'                     => 'ti-power-off',
                 'ti-plug'                          => 'ti-plug',
                 'ti-pie-chart'                     => 'ti-pie-chart',
                 'ti-panel'                         => 'ti-panel',
                 'ti-package'                       => 'ti-package',
                 'ti-music'                         => 'ti-music',
                 'ti-music-alt'                     => 'ti-music-alt',
                 'ti-mouse'                         => 'ti-mouse',
                 'ti-mouse-alt'                     => 'ti-mouse-alt',
                 'ti-money'                         => 'ti-money',
                 'ti-microphone'                    => 'ti-microphone',
                 'ti-menu'                          => 'ti-menu',
                 'ti-menu-alt'                      => 'ti-menu-alt',
                 'ti-map'                           => 'ti-map',
                 'ti-map-alt'                       => 'ti-map-alt',
                 'ti-location-pin'                  => 'ti-location-pin',
                 'ti-light-bulb'                    => 'ti-light-bulb',
                 'ti-info'                          => 'ti-info',
                 'ti-infinite'                      => 'ti-infinite',
                 'ti-id-badge'                      => 'ti-id-badge',
                 'ti-hummer'                        => 'ti-hummer',
                 'ti-home'                          => 'ti-home',
                 'ti-help'                          => 'ti-help',
                 'ti-headphone'                     => 'ti-headphone',
                 'ti-harddrives'                    => 'ti-harddrives',
                 'ti-harddrive'                     => 'ti-harddrive',
                 'ti-gift'                          => 'ti-gift',
                 'ti-game'                          => 'ti-game',
                 'ti-filter'                        => 'ti-filter',
                 'ti-files'                         => 'ti-files',
                 'ti-file'                          => 'ti-file',
                 'ti-zip'                           => 'ti-zip',
                 'ti-folder'                        => 'ti-folder',
                 'ti-envelope'                      => 'ti-envelope',
                 'ti-dashboard'                     => 'ti-dashboard',
                 'ti-cloud'                         => 'ti-cloud',
                 'ti-cloud-up'                      => 'ti-cloud-up',
                 'ti-cloud-down'                    => 'ti-cloud-down',
                 'ti-clipboard'                     => 'ti-clipboard',
                 'ti-calendar'                      => 'ti-calendar',
                 'ti-book'                          => 'ti-book',
                 'ti-bell'                          => 'ti-bell',
                 'ti-basketball'                    => 'ti-basketball',
                 'ti-bar-chart'                     => 'ti-bar-chart',
                 'ti-bar-chart-alt'                 => 'ti-bar-chart-alt',
                 'ti-archive'                       => 'ti-archive',
                 'ti-anchor'                        => 'ti-anchor',
                 'ti-alert'                         => 'ti-alert',
                 'ti-alarm-clock'                   => 'ti-alarm-clock',
                 'ti-agenda'                        => 'ti-agenda',
                 'ti-write'                         => 'ti-write',
                 'ti-wallet'                        => 'ti-wallet',
                 'ti-video-clapper'                 => 'ti-video-clapper',
                 'ti-video-camera'                  => 'ti-video-camera',
                 'ti-vector'                        => 'ti-vector',
                 'ti-support'                       => 'ti-support',
                 'ti-stamp'                         => 'ti-stamp',
                 'ti-slice'                         => 'ti-slice',
                 'ti-shortcode'                     => 'ti-shortcode',
                 'ti-receipt'                       => 'ti-receipt',
                 'ti-pin2'                          => 'ti-pin2',
                 'ti-pin-alt'                       => 'ti-pin-alt',
                 'ti-pencil-alt2'                   => 'ti-pencil-alt2',
                 'ti-eraser'                        => 'ti-eraser',
                 'ti-more'                          => 'ti-more',
                 'ti-more-alt'                      => 'ti-more-alt',
                 'ti-microphone-alt'                => 'ti-microphone-alt',
                 'ti-magnet'                        => 'ti-magnet',
                 'ti-line-double'                   => 'ti-line-double',
                 'ti-line-dotted'                   => 'ti-line-dotted',
                 'ti-line-dashed'                   => 'ti-line-dashed',
                 'ti-ink-pen'                       => 'ti-ink-pen',
                 'ti-info-alt'                      => 'ti-info-alt',
                 'ti-help-alt'                      => 'ti-help-alt',
                 'ti-headphone-alt'                 => 'ti-headphone-alt',
                 'ti-gallery'                       => 'ti-gallery',
                 'ti-face-smile'                    => 'ti-face-smile',
                 'ti-face-sad'                      => 'ti-face-sad',
                 'ti-credit-card'                   => 'ti-credit-card',
                 'ti-comments-smiley'               => 'ti-comments-smiley',
                 'ti-time'                          => 'ti-time',
                 'ti-share'                         => 'ti-share',
                 'ti-share-alt'                     => 'ti-share-alt',
                 'ti-rocket'                        => 'ti-rocket',
                 'ti-new-window'                    => 'ti-new-window',
                 'ti-rss'                           => 'ti-rss',
                 'ti-rss-alt'                       => 'ti-rss-alt',
                 'ti-control-stop'                  => 'ti-control-stop',
                 'ti-control-shuffle'               => 'ti-control-shuffle',
                 'ti-control-play'                  => 'ti-control-play',
                 'ti-control-pause'                 => 'ti-control-pause',
                 'ti-control-forward'               => 'ti-control-forward',
                 'ti-control-backward'              => 'ti-control-backward',
                 'ti-volume'                        => 'ti-volume',
                 'ti-control-skip-forward'          => 'ti-control-skip-forward',
                 'ti-control-skip-backward'         => 'ti-control-skip-backward',
                 'ti-control-record'                => 'ti-control-record',
                 'ti-control-eject'                 => 'ti-control-eject',
                 'ti-paragraph'                     => 'ti-paragraph',
                 'ti-uppercase'                     => 'ti-uppercase',
                 'ti-underline'                     => 'ti-underline',
                 'ti-text'                          => 'ti-text',
                 'ti-Italic'                        => 'ti-Italic',
                 'ti-smallcap'                      => 'ti-smallcap',
                 'ti-list'                          => 'ti-list',
                 'ti-list-ol'                       => 'ti-list-ol',
                 'ti-align-right'                   => 'ti-align-right',
                 'ti-align-left'                    => 'ti-align-left',
                 'ti-align-justify'                 => 'ti-align-justify',
                 'ti-align-center'                  => 'ti-align-center',
                 'ti-quote-right'                   => 'ti-quote-right',
                 'ti-quote-left'                    => 'ti-quote-left',
                 'ti-layout-width-full'             => 'ti-layout-width-full',
                 'ti-layout-width-default'          => 'ti-layout-width-default',
                 'ti-layout-width-default-alt'      => 'ti-layout-width-default-alt',
                 'ti-layout-tab'                    => 'ti-layout-tab',
                 'ti-layout-tab-window'             => 'ti-layout-tab-window',
                 'ti-layout-tab-v'                  => 'ti-layout-tab-v',
                 'ti-layout-tab-min'                => 'ti-layout-tab-min',
                 'ti-layout-slider'                 => 'ti-layout-slider',
                 'ti-layout-slider-alt'             => 'ti-layout-slider-alt',
                 'ti-layout-sidebar-right'          => 'ti-layout-sidebar-right',
                 'ti-layout-sidebar-none'           => 'ti-layout-sidebar-none',
                 'ti-layout-sidebar-left'           => 'ti-layout-sidebar-left',
                 'ti-layout-placeholder'            => 'ti-layout-placeholder',
                 'ti-layout-menu'                   => 'ti-layout-menu',
                 'ti-layout-menu-v'                 => 'ti-layout-menu-v',
                 'ti-layout-menu-separated'         => 'ti-layout-menu-separated',
                 'ti-layout-menu-full'              => 'ti-layout-menu-full',
                 'ti-layout-media-right'            => 'ti-layout-media-right',
                 'ti-layout-media-right-alt'        => 'ti-layout-media-right-alt',
                 'ti-layout-media-overlay'          => 'ti-layout-media-overlay',
                 'ti-layout-media-overlay-alt'      => 'ti-layout-media-overlay-alt',
                 'ti-layout-media-overlay-alt-2'    => 'ti-layout-media-overlay-alt-2',
                 'ti-layout-media-left'             => 'ti-layout-media-left',
                 'ti-layout-media-left-alt'         => 'ti-layout-media-left-alt',
                 'ti-layout-media-center'           => 'ti-layout-media-center',
                 'ti-layout-media-center-alt'       => 'ti-layout-media-center-alt',
                 'ti-layout-list-thumb'             => 'ti-layout-list-thumb',
                 'ti-layout-list-thumb-alt'         => 'ti-layout-list-thumb-alt',
                 'ti-layout-list-post'              => 'ti-layout-list-post',
                 'ti-layout-list-large-image'       => 'ti-layout-list-large-image',
                 'ti-layout-line-solid'             => 'ti-layout-line-solid',
                 'ti-layout-grid4'                  => 'ti-layout-grid4',
                 'ti-layout-grid3'                  => 'ti-layout-grid3',
                 'ti-layout-grid2'                  => 'ti-layout-grid2',
                 'ti-layout-grid2-thumb'            => 'ti-layout-grid2-thumb',
                 'ti-layout-cta-right'              => 'ti-layout-cta-right',
                 'ti-layout-cta-left'               => 'ti-layout-cta-left',
                 'ti-layout-cta-center'             => 'ti-layout-cta-center',
                 'ti-layout-cta-btn-right'          => 'ti-layout-cta-btn-right',
                 'ti-layout-cta-btn-left'           => 'ti-layout-cta-btn-left',
                 'ti-layout-column4'                => 'ti-layout-column4',
                 'ti-layout-column3'                => 'ti-layout-column3',
                 'ti-layout-column2'                => 'ti-layout-column2',
                 'ti-layout-accordion-separated'    => 'ti-layout-accordion-separated',
                 'ti-layout-accordion-merged'       => 'ti-layout-accordion-merged',
                 'ti-layout-accordion-list'         => 'ti-layout-accordion-list',
                 'ti-widgetized'                    => 'ti-widgetized',
                 'ti-widget'                        => 'ti-widget',
                 'ti-widget-alt'                    => 'ti-widget-alt',
                 'ti-view-list'                     => 'ti-view-list',
                 'ti-view-list-alt'                 => 'ti-view-list-alt',
                 'ti-view-grid'                     => 'ti-view-grid',
                 'ti-upload'                        => 'ti-upload',
                 'ti-download'                      => 'ti-download',
                 'ti-loop'                          => 'ti-loop',
                 'ti-layout-sidebar-2'              => 'ti-layout-sidebar-2',
                 'ti-layout-grid4-alt'              => 'ti-layout-grid4-alt',
                 'ti-layout-grid3-alt'              => 'ti-layout-grid3-alt',
                 'ti-layout-grid2-alt'              => 'ti-layout-grid2-alt',
                 'ti-layout-column4-alt'            => 'ti-layout-column4-alt',
                 'ti-layout-column3-alt'            => 'ti-layout-column3-alt',
                 'ti-layout-column2-alt'            => 'ti-layout-column2-alt',
                 'ti-flickr'                        => 'ti-flickr',
                 'ti-flickr-alt'                    => 'ti-flickr-alt',
                 'ti-instagram'                     => 'ti-instagram',
                 'ti-google'                        => 'ti-google',
                 'ti-github'                        => 'ti-github',
                 'ti-facebook'                      => 'ti-facebook',
                 'ti-dropbox'                       => 'ti-dropbox',
                 'ti-dropbox-alt'                   => 'ti-dropbox-alt',
                 'ti-dribbble'                      => 'ti-dribbble',
                 'ti-apple'                         => 'ti-apple',
                 'ti-android'                       => 'ti-android',
                 'ti-yahoo'                         => 'ti-yahoo',
                 'ti-trello'                        => 'ti-trello',
                 'ti-stack-overflow'                => 'ti-stack-overflow',
                 'ti-soundcloud'                    => 'ti-soundcloud',
                 'ti-sharethis'                     => 'ti-sharethis',
                 'ti-sharethis-alt'                 => 'ti-sharethis-alt',
                 'ti-reddit'                        => 'ti-reddit',
                 'ti-microsoft'                     => 'ti-microsoft',
                 'ti-microsoft-alt'                 => 'ti-microsoft-alt',
                 'ti-linux'                         => 'ti-linux',
                 'ti-jsfiddle'                      => 'ti-jsfiddle',
                 'ti-joomla'                        => 'ti-joomla',
                 'ti-html5'                         => 'ti-html5',
                 'ti-css3'                          => 'ti-css3',
                 'ti-drupal'                        => 'ti-drupal',
                 'ti-wordpress'                     => 'ti-wordpress',
                 'ti-tumblr'                        => 'ti-tumblr',
                 'ti-tumblr-alt'                    => 'ti-tumblr-alt',
                 'ti-skype'                         => 'ti-skype',
                 'ti-youtube'                       => 'ti-youtube',
                 'ti-vimeo'                         => 'ti-vimeo',
                 'ti-vimeo-alt'                     => 'ti-vimeo-alt',
                 'ti-twitter'                       => 'ti-twitter',
                 'ti-twitter-alt'                   => 'ti-twitter-alt',
                 'ti-linkedin'                      => 'ti-linkedin',
                 'ti-pinterest'                     => 'ti-pinterest',
                 'ti-pinterest-alt'                 => 'ti-pinterest-alt',
                 'ti-themify-logo'                  => 'ti-themify-logo',
                 'ti-themify-favicon'               => 'ti-themify-favicon',
                 'ti-themify-favicon-alt'           => 'ti-themify-favicon-alt');
}
?>
