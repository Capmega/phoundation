<?php
global $_CONFIG, $core;

try {
    load_libs('numbers');

    if (!debug()) return '';

    if ($_CONFIG['debug']['bar'] === false) {
        return '';

    } elseif ($_CONFIG['debug']['bar'] === 'limited') {
        if (empty($_SESSION['user']['id']) or !has_rights("debug")) {
            /*
             * Only show debug bar to authenticated users with "debug" right
             */
            return false;
        }

    } elseif ($_CONFIG['debug']['bar'] === true) {
        /*
         * Show debug bar!
         */

    } else {
        throw new CoreException(tr('debug_bar(): Unknown configuration option ":option" specified. Please specify true, false, or "limited"', array(':option' => $_CONFIG['debug']['bar'])), 'unknown');
    }

    /*
     * Add debug bar javascript directly to the footer, as this debug bar is
     * added AFTER html_generate_js() and so won't be processed anymore
     */
    $core->register['footer'] .= html_script('$("#debug-bar").click(function(e) { $("#debug-bar").find(".list").toggleClass("hidden"); });');

    /*
     * Setup required variables
     */
    usort($core->register['debug_queries'], 'debug_bar_sort');
    $usage = getrusage();
    $files = get_included_files();


    /*
     * Build HTML
     */
    $html = '<div class="debug" id="debug-bar">
                '.($_CONFIG['cache']['method'] ? '(CACHE='.$_CONFIG['cache']['method'].') ' : '').count($core->register('debug_queries')).' / '.number_format(microtime(true) - STARTTIME, 6).'
                <div class="hidden list">
                    <div style="width:100%; background: #2d3945; text-align: center; font-weight: bold; padding: 3px 0 3px;">
                        '.tr('Debug report').'
                    </div>
                    <table style="width:100%">
                        <thead>
                            <tr>
                                <th colspan="3">'.tr('Query information (Ordered by slowest first, fastest last)').'</th>
                            </tr>
                            <tr>
                                <th>'.tr('Time').'</th>
                                <th>'.tr('Function').'</th>
                                <th>'.tr('Query').'</th>
                            </tr>
                        </thead>
                        <tbody>';

    /*
     * Add query statistical data ordered by slowest queries first
     */
    foreach ($core->register['debug_queries'] as $query) {
        $html .= '      <tr>
                            <td>'.number_format($query['time'], 6).'</td>
                            <td>'.$query['function'].'</td>
                            <td>'.$query['query'].'</td>
                        </tr>';
    }

    $html .= '          </tbody>
                    </table>';

    /*
     * Show some basic statistics
     */
    $html .= '      <table style="width:100%">
                        <thead>
                            <tr>
                                <th colspan="2">'.tr('General information').'</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>'.tr('Peak memory usage').'</td>
                                <td>'.human_readable(memory_get_peak_usage()).'</td>
                            </tr>
                            <tr>
                                <td>'.tr('Execution time').'</td>
                                <td>'.tr(':time milliseconds', array(':time' => number_format((microtime(true) - STARTTIME) * 1000, 2))).'</td>
                            </tr>
                            <tr>
                                <td>'.tr('CPU usage system').'</td>
                                <td>'.tr(':time microseconds', array(':time' => number_format($usage['ru_stime.tv_usec'], 0, '.', ','))).'</td>
                            </tr>
                            <tr>
                                <td>'.tr('Included files count').'</td>
                                <td>'.count($files).'</td>
                            </tr>
                        </tbody>
                    </table>';

    /*
     * Show all included files
     */
    $html .= '      <table style="width:100%">
                        <thead>
                            <tr>
                                <th colspan="2">'.tr('Included files (In loaded order)').'</th>
                            </tr>
                        </thead>
                        <thead>
                            <tr>
                                <th>'.tr('Number').'</th>
                                <th>'.tr('File').'</th>
                            </tr>
                        </thead>
                        <tbody>';

    foreach ($files as $id => $file) {
        $html .= '      <tr>
                            <td>'.($id + 1).'</td>
                            <td>'.$file.'</td>
                        </tr>';
    }

    $html .= '          </tbody>
                    </table>';

    $html .= '  </div>
             </div>';

    $html  = str_replace(':query_count'   , count($core->register('debug_queries'))      , $html);
    $html  = str_replace(':execution_time', number_format(microtime(true) - STARTTIME, 6), $html);

    return $html;

}catch(Exception $e) {
    throw new CoreException(tr('debug_bar(): Failed'), $e);
}
?>