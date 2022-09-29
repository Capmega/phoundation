<?php
global $core;

try{
    if (is_array($execute)) {
        /*
         * Reverse key sort to ensure that there are keys that contain at least parts of other keys will not be used incorrectly
         *
         * example:
         *
         * array(category    => test,
         *       category_id => 5)
         *
         * Would cause the query to look like `category` = "test", `category_id` = "test"_id
         */
        krsort($execute);

        if (is_object($query)) {
            /*
             * Query to be debugged is a PDO statement, extract the query
             */
            if (!($query instanceof PDOStatement)) {
                throw new CoreException(tr('debug_sql(): Object of unknown class ":class" specified where PDOStatement was expected', array(':class' => get_class($query))), 'invalid');
            }

            $query = $query->queryString;
        }

        foreach($execute as $key => $value) {
            if (is_string($value)) {
                $value = addslashes($value);
                $query = str_replace($key, '"'.(!is_scalar($value) ? ' ['.tr('NOT SCALAR').'] ' : '').str_log($value).'"', $query);

            } elseif (is_null($value)) {
                $query = str_replace($key, ' '.tr('NULL').' ', $query);

            } elseif (is_bool($value)) {
                $query = str_replace($key, str_boolean($value), $query);

            } else {
                if (!is_scalar($value)) {
                    throw new CoreException(tr('debug_sql(): Specified key ":key" has non-scalar value ":value"', array(':key' => $key, ':value' => $value)), 'invalid');
                }

                $query = str_replace($key, $value, $query);
            }
        }
    }

    if ($return_only) {
        return $query;
    }

    if (empty($core->register['clean_debug'])) {
        $query = str_replace("\n", ' ', $query);
        $query = str_nodouble($query, ' ', '\s');
    }

    /*
     * VERYVERBOSE already logs the query, don't log it again
     */
    if (!VERYVERBOSE) {
        log_file(Strings::endsWith($query, ';'), 'debug-sql');
    }

    return show(Strings::endsWith($query, ';'), 6);

}catch(Exception $e) {
    throw new CoreException('debug_sql(): Failed', $e);
}
?>