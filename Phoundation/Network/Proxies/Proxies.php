<?php

namespace Phoundation\Network\Proxies;

use Phoundation\Network\Exception\NetworkException;
use Phoundation\Utils\Json;



/**
 * Class Proxies
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
class Proxies
{
    public static function list($url, $file = '', $server_restrictionsurl = null): array
    {
        if (!$server_restrictionsurl) {
            $server_restrictionsurl = $_CONFIG['curl']['proxies'];
        }

        if (is_array($server_restrictionsurl)) {
            $server_restrictionsurl = array_random_value($server_restrictionsurl);
        }

        if (is_array($url)) {
            throw new NetworkException(tr('curl_get_proxy(): No URL specified'), 'not-specified');
        }

        if (!$server_restrictionsurl) {
            throw new NetworkException(tr('curl_get_proxy(): No proxy server URL(s) specified'), 'not-specified');
        }

        log_console(tr('Using proxy ":proxy"', array(':proxy' => Strings::cut(Strings::Log($server_restrictionsurl), '://', '/'))), 'VERBOSE');

        $data = curl_get(array('url'        => Strings::endsWith($server_restrictionsurl, '?apikey='.$_CONFIG['curl']['apikey'].'&url=').urlencode($url),
            'getheaders' => false,
            'proxies'    => false));

        if (!trim($data['data'])) {
            throw new NetworkException(tr('curl_get_proxy(): Proxy returned no data. Is proxy correctly configured? Proxy domain resolves correctly?'), 'no-data');
        }

        if (substr($data['data'], 0, 12) !== 'PROXY_RESULT') {
            throw new NetworkException(tr('curl_get_proxy(): Proxy returned invalid data ":data" from proxy ":proxy". Is proxy correctly configured? Proxy domain resolves correctly?', array(':data' => Strings::Log($data), ':proxy' => Strings::cut(Strings::Log($server_restrictionsurl), '://', '/'))), 'not-specified');
        }

        $data         = substr($data['data'], 12);
        $data         = Json::decode($data);
        $data['data'] = base64_decode($data['data']);

        if ($file) {
            /*
             * Write the data to the specified file
             */
            file_put_contents($file, $data['data']);
        }

        return $data;
    }

    

    /**
     * @param $url
     * @param $file
     * @param $server_restrictionsurl
     * @return Proxy
     */
    public static function getRandom($url, $file = '', $server_restrictionsurl = null): Proxy
    {
        global $_CONFIG;

        try {
            if (!$server_restrictionsurl) {
                $server_restrictionsurl = $_CONFIG['curl']['proxies'];
            }

            if (is_array($server_restrictionsurl)) {
                $server_restrictionsurl = array_random_value($server_restrictionsurl);
            }

            if (is_array($url)) {
                throw new NetworkException(tr('curl_get_proxy(): No URL specified'), 'not-specified');
            }

            if (!$server_restrictionsurl) {
                throw new NetworkException(tr('curl_get_proxy(): No proxy server URL(s) specified'), 'not-specified');
            }

            log_console(tr('Using proxy ":proxy"', array(':proxy' => Strings::cut(Strings::Log($server_restrictionsurl), '://', '/'))), 'VERBOSE');

            $data = curl_get(array('url'        => Strings::endsWith($server_restrictionsurl, '?apikey='.$_CONFIG['curl']['apikey'].'&url=').urlencode($url),
                'getheaders' => false,
                'proxies'    => false));

            if (!trim($data['data'])) {
                throw new NetworkException(tr('curl_get_proxy(): Proxy returned no data. Is proxy correctly configured? Proxy domain resolves correctly?'), 'no-data');
            }

            if (substr($data['data'], 0, 12) !== 'PROXY_RESULT') {
                throw new NetworkException(tr('curl_get_proxy(): Proxy returned invalid data ":data" from proxy ":proxy". Is proxy correctly configured? Proxy domain resolves correctly?', array(':data' => Strings::Log($data), ':proxy' => Strings::cut(Strings::Log($server_restrictionsurl), '://', '/'))), 'not-specified');
            }

            $data         = substr($data['data'], 12);
            $data         = json_decode_custom($data);
            $data['data'] = base64_decode($data['data']);

            if ($file) {
                /*
                 * Write the data to the specified file
                 */
                file_put_contents($file, $data['data']);
            }

            return $data;
    }
}