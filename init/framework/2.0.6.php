<?php
/*
 * Remove old handler files
 */
load_libs('git');

if(git_is_available() and git_is_repository()){
    if(git_status()){
        throw new CoreException(tr('This init file needs to make code changes which will be automatically committed. Please commit all code before continuing running this init'), 'warning/failed');
    }
}

log_console(tr('Cleaning up garbage (Old library handler files)'), 'cyan', false);

foreach(scandir(ROOT.'libs/handlers') as $file){
    if(($file == '.') or ($file == '..')){
        continue;
    }

    /*
     * Remove startup- files
     * Remove files with _
     */
    if(preg_match('/^startup-.+/', $file) or preg_match('/.+_.+/', $file)){
        file_delete(ROOT.'libs/handlers/'.$file, ROOT.'libs/handlers');
        cli_dot();
    }
}

cli_dot(false);

if(git_is_available() and git_is_repository()){
    if(git_status()){
        git_add();
        git_commit('Removed garbage (Old library handler files)');
    }
}
?>