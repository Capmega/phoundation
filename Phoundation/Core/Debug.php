<?php

/**
 * Class Debug
 */
class Debug {
    /**
     * Returns a backtrace
     *
     * @return array
     */
    public static function backtrace(string $filters = 'args', bool $skip_own = true): array
    {
        if(!debug()){
            return array();
        }

        $filters = array_force($filters);
        $trace   = array();

        foreach(debug_backtrace() as $key => $value){
            if($skip_own and ($key <= 1)){
                continue;
            }

            foreach($filters as $filter){
                unset($value[$filter]);
            }

            $trace[] = $value;
        }

        return $trace;
    }



    /**
     * Returns the filename from where this call was made
     *
     * @param int $trace
     * @return string
     */
    public static function currentFile(int $trace = 0): string
    {
        $backtrace = debug_backtrace();

        if(!isset($backtrace[$trace + 1])){
            return '-';
        }

        return isset_get($backtrace[$trace + 1]['file'], '-');
    }



    /**
     * Returns the line number from where this call was made
     *
     * @param int $trace
     * @return string
     */
    public static function currentFunction(int $trace = 0): string
    {
        $backtrace = debug_backtrace();

        if(!isset($backtrace[$trace + 1])){
            return -1;
        }

        return isset_get($backtrace[$trace + 1]['function'], '-');
    }



    /**
     * Returns the line number from where this call was made
     *
     * @param int $trace
     * @return int
     */
    public static function currentLine(int $trace = 0): int
    {
        $backtrace = debug_backtrace();

        if(!isset($backtrace[$trace + 1])){
            return -1;
        }

        return isset_get($backtrace[$trace + 1]['line'], -1);
    }
}