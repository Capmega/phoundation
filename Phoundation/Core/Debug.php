<?php

/**
 * Class Debug
 */
class Debug {
    /**
     *
     *
     * @param int $trace
     * @return mixed|string|null
     */
    public static function currentFile(int $trace = 0) {
        $backtrace = debug_backtrace();

        if(!isset($backtrace[$trace + 1])){
            return 'no_current_file';
        }

        return isset_get($backtrace[$trace + 1]['file']);
    }
}