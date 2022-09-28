<?php
/*
 * Analytics library
 *
 * This library provides client analytics plugins.
 *
 * Currently supported providers are google analytics, matomo analytics
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package analytics
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package analytics
 *
 * @return void
 */
function analytics_library_init(){
    try{
        load_config('analytics');

    }catch(Exception $e){
        throw new CoreException('analytics_library_init(): Failed', $e);
    }
}



/*
 * Return analytics tracking code with the specified $sites_id using the configured provider
 *
 * The output of this function must be added before the </head> tag, preferably using $params[extra] in c_html_header()
 *
 * This function will return analytics code from the provider specified in $_CONFIG[analytics][provider]
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package analytics
 * @see analytics_google()
 * @see analytics_matomo()
 * @version 1.27.1: Added function and documentation
 *
 * @params string $sites_id The website id code provided by the analytics provider for this website
 * @params null string $provider The analytics provider that should be used
 * @return string The HTML required to have the client register with your matomo tracking site
 */
function analytics($sites_id, $provider = null){
    global $_CONFIG;

    try{
        if(!$provider){
            $provider = $_CONFIG['analytics']['provider'];
        }

        switch($provider){
            case 'google':
                return analytics_google($sites_id);

            case 'matomo':
                return analytics_matomo($sites_id);

            default:
                throw new CoreException(tr('analytics(): Unknown analytics provider ":provider" specified, see $_CONFIG[analytics][provider] or calling function', array(':provider' => $_CONFIG['analytics']['provider'])), 'unknown');
        }

    }catch(Exception $e){
        throw new CoreException('analytics(): Failed', $e);
    }
}



/*
 * Return matomo analytics tracking code with the specified $sites_id
 *
 * The output of this function must be added before the </head> tag, preferably using $params[extra] in c_html_header()
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package analytics
 * @see analytics()
 * @see analytics_google()
 * @see analytics_bing()
 * @version 1.27.1: Added function and documentation
 *
 * @params string $sites_id
 * @return string The HTML required to have the client register with your matomo tracking site
 */
function analytics_matomo($sites_id){
    global $_CONFIG;

    try{
        if(!$sites_id){
            throw new CoreException(tr('analytics_matomo(): No sites_id specified'), 'not-specified');
        }

        if(!is_natural($sites_id)){
            throw new CoreException(tr('analytics_matomo(): Invalid sites_id ":sites_id" specified', array(':sites_id' => $sites_id)), 'not-specified');
        }

        if(empty($_CONFIG['analytics']['matomo_domain'])){
            throw new CoreException(tr('analytics_matomo(): No matomo domain configured'), 'not-specified');
        }

        /*
         * Ensure we have the analytics file available on our CDN system
         */
// :TODO: Right now we're only testing this locally, we should test this on the CDN network system! This lookup may be heavy though, so maybe we should do that once every 100 page views or something
        if(!file_exists(ROOT.'www/'.LANGUAGE.'/pub/js/matomo/piwik.js')){
            /*
             * Download the file from google analytics and install it in our
             * local CDN
             */
            $file = file_get_local($_CONFIG['analytics']['matomo_domain'].'/piwik.js');

            file_execute_mode(ROOT.'www/'.LANGUAGE.'/pub/js/', 0770, function($path) use ($file) {
                file_ensure_path($path.'matomo', 0550);

                file_execute_mode($path.'matomo/', 0770, function($path) use ($file) {
                    rename($file, $path.'piwik.js');
                    chmod($path.'piwik.js', 0440);
                });
            });
        }

        return '    <script type="text/javascript">
                      var _paq = _paq || [];
                      /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
                      _paq.push(["trackPageView"]);
                      _paq.push(["enableLinkTracking"]);
                      (function() {
                        _paq.push(["setTrackerUrl", "'.$_CONFIG['analytics']['matomo_domain'].'/piwik.php"]);
                        _paq.push(["setSiteId", "'.$sites_id.'"]);
                        var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0];
                        g.type="text/javascript"; g.async=true; g.defer=true; g.src="'.cdn_domain('/js/matomo/piwik.js').'"; s.parentNode.insertBefore(g,s);
                      })();
                    </script>
                    <noscript><p><img src="//'.$_CONFIG['analytics']['matomo_domain'].'/piwik.php?idsite='.$sites_id.'&amp;rec=1" style="border:0;" alt="" /></p></noscript>';

    }catch(Exception $e){
        throw new CoreException('analytics_matomo(): Failed', $e);
    }
}



/*
 * Return Google analytics tracking code with the specified $sites_id
 *
 * The output of this function must be added before the </head> tag, preferably using $params[extra] in c_html_header()
 *
 * This script will load the google tracking library from your own servers, avoiding caching issues with google servers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package analytics
 * @see analytics()
 * @see analytics_matomo()
 * @see analytics_bing()
 * @version 1.27.1: Added function and documentation
 * @version 2.5.158: Removed local caching until CDN service is available.
 *
 * @params string $sites_id
 * @return string The HTML required to have the client register with google analytics
 */
function analytics_google($sites_id){
    try{
        if(!$sites_id){
            throw new CoreException(tr('analytics_google(): No sites_id specified'), 'not-specified');
        }

        /*
         * Ensure we have the analytics file available on our CDN system
         */
        if(!file_exists(ROOT.'www/'.LANGUAGE.'/pub/js/google/analytics.js')){
            /*
             * Download the file from google analytics and install it in our
             * local CDN
             */
            $file = file_get_local('https://www.google-analytics.com/analytics.js');

            file_execute_mode(ROOT.'www/'.LANGUAGE.'/pub/js/', 0770, function($path) use ($file) {
                file_ensure_path($path.'google', 0550);

                file_execute_mode($path.'google/', 0770, function($path) use ($file) {
                    rename($file, $path.'analytics.js');
                    chmod($path.'analytics.js', 0440);
                });
            });
        }

        $retval = ' <script>
                        (function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){
                        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                        })(window,document,"script","'.cdn_domain('/js/google/analytics.js').'","ga");
                        ga("create", "'.$sites_id.'", "auto");
                        ga("send", "pageview");
                    </script>';

// :TODO: Determine if this should be implemented at all
//        $retval = '<script async src="https://www.googletagmanager.com/gtag/js?id='.$sites_id.'"></script>
//                   <script>
//                       window.dataLayer = window.dataLayer || [];
//                       function gtag(){dataLayer.push(arguments);}
//                       gtag("js", new Date());
//
//                       gtag("config", "'.$sites_id.'");
//                   </script>';

        return $retval;

    }catch(Exception $e){
        throw new CoreException('analytics_google(): Failed', $e);
    }
}
