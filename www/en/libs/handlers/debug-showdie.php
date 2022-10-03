<?php
try {
    if ($trace_offset === null) {
        if (PLATFORM_HTTP) {
            $trace_offset = 5;

        } else {
            $trace_offset = 4;
        }
    }

    if (!Debug::enabled()) {
        return $data;
    }

    show($data, $trace_offset);

    /*
     * Ensure that the shutdown function doesn't try to show the 404 page
     */
    unregister_shutdown('route_404');

    die();

}catch(Exception $e) {
    throw new CoreException(tr('showdie(): Failed'), $e);
}
?>