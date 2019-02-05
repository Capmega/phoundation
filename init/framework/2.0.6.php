<?php
/*
 * Remove old handler files
 */
load_libs('git');
log_console(tr('Cleaning up garbage (Old library handler files)'), 'cyan', false);

if(git_status(ROOT)){
    throw new bException(tr('This init file needs to make code changes which will be automatically committed. Please commit all code before continuing running this init'), 'failed');
}

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

if(git_status(ROOT)){
    git_commit('Removed garbage (Old library handler files)', ROOT);
}
?>