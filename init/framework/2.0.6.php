<?php
/*
 * Remove old handler files
 */
log_console(tr('Cleaning up garbage (old library handler files)'), 'cyan', false);

foreach(scandir(ROOT.'libs/handlers') as $file){
    if(($file == '.') or ($file == '..')){
        continue;
    }

    /*
     * Remove startup- files
     * Remove files with _
     */
    if(preg_match('/^startup-.+/', $file) or preg_match('/.+_.+/', $file)){
        file_delete(ROOT.'libs/handlers/'.$file);
        cli_dot();
    }
}

cli_dot(false);
?>