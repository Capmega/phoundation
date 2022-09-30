<?php
/*
 * Graph library
 *
 * This is the graph front-end library. It will pass the graph functiosn to the various backends, depending on configuration
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package graph
 */



/*
 * Generate and return graphs
 *
 * @author Camilo Rodriguez <crodriguez@capmega.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package graph
 *
 * @$params params $params config for the selected type of grapth
 * @$params string  $params[provider] the graph provider library to use
 * @return  string The HTML for the graph
 */
function graph_generate(array $params = array()) {
    try {
        switch($params['provider']) {
            case 'morris':
                load_libs('graph-morris');
                return graph_morris_generate($params);

            case 'high-charts':
                load_libs('graph-highcharts');
                return graph_hightcharts_generate($params);

            default:
                throw new CoreException(tr('graph_generate(): Unknown graph provider ":provider" specified', array(':provider' => $type)), 'unknown');
                break;
        }

     }catch(Exception $e) {
        throw new CoreException('graph_generate(): Failed', $e);
    }
}
?>
