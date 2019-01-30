<?php
/*
 * Analytics library
 *
 * This is an empty template library file
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Return analytics tracking code with the specified $sites_id using the configured provider
 *
 * The output of this function must be added before the </head> tag, preferably using $params[extra] in c_html_header()
 *
 * This function will return analytics code from the provider specified in $_CONFIG[analytics][provider]
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package analytics
 * @see analytics_google()
 * @see analytics_matomo()
 * @version 1.27.1: Added function and documentation
 *
 * @params string $sites_id
 * @return string The HTML required to have the client register with your matomo tracking site
 */
function analytics($sites_id){
    try{
        switch($_CONFIG['analytics']['provider']){
            case 'google':
                return analytics_google($sites_id);

            case 'matomo':
                return analytics_matomo($sites_id);

            default:
                throw new bException(tr('analytics(): Unknown analytics provider ":provider" specified, see $_CONFIG[analytics][provider]', array(':provider' => $_CONFIG['analytics']['provider'])), 'unknown');
        }

    }catch(Exception $e){
        throw new bException('analytics(): Failed', $e);
    }
}



/*
 * Return matomo analytics tracking code with the specified $sites_id
 *
 * The output of this function must be added before the </head> tag, preferably using $params[extra] in c_html_header()
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package analytics
 * @see analytics()
 * @see analytics_google()
 * @version 1.27.1: Added function and documentation
 *
 * @params string $sites_id
 * @return string The HTML required to have the client register with your matomo tracking site
 */
function analytics_matomo($sites_id){
    try{
        if(!$sites_id){
            throw new bException(tr('analytics_matomo(): No sites_id specified'), 'not-specified');
        }

        if(!is_natural($sites_id)){
            throw new bException(tr('analytics_matomo(): Invalid sites_id ":sites_id" specified', array(':sites_id' => $sites_id)), 'not-specified');
        }

        return '    <script type="text/javascript">
                      var _paq = _paq || [];
                      /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
                      _paq.push(["trackPageView"]);
                      _paq.push(["enableLinkTracking"]);
                      (function() {
                        _paq.push(["setTrackerUrl", u+"piwik.php"]);
                        _paq.push(["setSiteId", "'.$sites_id.'"]);
                        var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0];
                        g.type="text/javascript"; g.async=true; g.defer=true; g.src="'.cdn_domain('/js/matomo/piwik.js').'"; s.parentNode.insertBefore(g,s);
                      })();
                    </script>
                    <noscript><p><img src="//analytics.capmega.com/piwik.php?idsite='.$sites_id.'&amp;rec=1" style="border:0;" alt="" /></p></noscript>';

    }catch(Exception $e){
        throw new bException('analytics_matomo(): Failed', $e);
    }
}



/*
 * Return Google analytics tracking code with the specified $sites_id
 *
 * The output of this function must be added before the </head> tag, preferably using $params[extra] in c_html_header()
 *
 * This script will load the google tracking library from your own servers, avoiding caching issues with google servers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package analytics
 * @see analytics()
 * @see analytics_matomo()
 * @version 1.27.1: Added function and documentation
 *
 * @params string $sites_id
 * @return string The HTML required to have the client register with google analytics
 */
function analytics_google($code){
    global $_CONFIG;

    try{
        $retval = ' <script>
                        (function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){
                        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                        })(window,document,"script","'.cdn_domain('/js/google/analytics.js').'","ga");

                        ga("create", "'.$code.'", "auto");
                        ga("send", "pageview");
                    </script>';

        return $retval;

    }catch(Exception $e){
        throw new bException('analytics_google(): Failed', $e);
    }
}
?>
