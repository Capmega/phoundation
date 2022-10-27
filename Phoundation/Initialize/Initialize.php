<?php

namespace Phoundation\Initialize;

use Phoundation\Cache\Cache;
use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Core\Tmp;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Developer\Debug;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Exception\Exceptions;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Initialize library
 *
 * This library can initialize all other libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Initialize
 */
class Initialize
{
    /**
     * The constant indicating the path for Phoundation libraries
     */
    const CLASS_PATH_SYSTEM  = ROOT . 'Phoundation';

    /**
     * The constant indicating the path for PLugin libraries
     */
    const CLASS_PATH_PLUGINS = ROOT . 'Plugins';

    /**
     * If true, this system is in initialization mode
     *
     * @var bool
     */
    protected static bool $initializing = false;



    /**
     * Execute a complete systems initialization
     *
     * @param bool $system
     * @param bool $plugins
     * @param string|null $comments
     * @param string|null $library
     * @return void
     */
    public static function execute(bool $system = true, bool $plugins = true, ?string $comments = null, ?string $library = null): void
    {
        self::$initializing = true;

        if (FORCE) {
            self::force();
        }

        if ($library) {
            // Init only the specified library
            $library = strtolower($library);
            $path    = self::findLibraryPath($library, $system, $plugins);

            self::executeLibrary($library, $path, $comments);

        } else {
            // Wipe all temporary data
            Tmp::clear();

            // Wipe all cache data
            Cache::clear();

            // Ensure the system database exists
            self::ensureSystemsDatabase();

            // Go over all system libraries and initialize them, then do the same for the plugins
            self::executeLibraries($system, $plugins);
        }

        // Initialization done!
        self::$initializing = false;
    }



    /**
     * Returns a list with all libraries
     *
     * @param bool $system
     * @param bool $plugins
     * @return array
     */
    public static function listLibraries(bool $system = true, bool $plugins = true): array
    {
        if (!$system and !$plugins) {
            throw new OutOfBoundsException(tr('Both system and plugin library paths are filtered out'));
        }

        $return = [];

        // List system libraries
        if ($system) {
            $return = array_merge($return, self::listLibraryPaths(self::CLASS_PATH_SYSTEM));
        }

        // List plugin libraries
        if ($plugins) {
            try {
                $return = array_merge($return, self::listLibraryPaths(self::CLASS_PATH_PLUGINS));

            } catch (NotExistsException $e) {
                // The plugins path does not exist. No biggie, note it in the logs and create it for next time.
                mkdir(self::CLASS_PATH_PLUGINS, Config::get('filesystem.mode.default.directory', 0750));
            }
        }

        return $return;
    }



    /**
     * Returns a list with all libraries and their version information
     *
     * @param bool $system
     * @param bool $plugins
     * @return array
     */
    public static function listLibraryVersions(bool $system = true, bool $plugins = true): array
    {
        $return  = [];
        $libraries = self::listLibraries($system, $plugins);

        foreach ($libraries as $path => $library) {
            // Get version information for this library
            $version = self::listLibraryVersion($path, $library);

            if ($version === null) {
                // This library has no version control
                unset($libraries[$path]);
                continue;
            }

            $return[$path] = $version;
        }

        return $return;
    }



    /**
     * Returns true if the system is initializing
     *
     * @return bool
     */
    public static function isInitializing(): bool
    {
        return self::$initializing;
    }



    /**
     * Returns the database version for the specified library
     *
     * @param string $library
     * @param string $path
     * @return string|null
     */
    public static function getDatabaseVersion(string $library, string $path): ?string
    {
        if ($library === 'initialize') {
            // The init library does not have init support itself
            return null;
        }

        return self::getlibraryInit($path)?->getDatabaseVersion();
    }



    /**
     * Returns the code version for the specified library
     *
     * @param string $library
     * @param string $path
     * @return string|null
     */
    public static function getLibraryVersion(string $library, string $path): ?string
    {
        if ($library === 'initialize') {
            // The init library does not have init support itself
            return null;
        }

        return self::getlibraryInit($path)?->getVersion();
    }



    /**
     * Returns the next available init version for the specified library
     *
     * @param string $library
     * @param string $path
     * @return string|null
     */
    public static function getLibraryNextVersion(string $library, string $path): ?string
    {
        if ($library === 'initialize') {
            // The init library does not have init support itself
            return null;
        }

        return self::getlibraryInit($path)?->getNextExecutionVersion();
    }


    /**
     * Returns a list with all libraries and their version information
     *
     * @param string $path
     * @param string $library
     * @return array|null
     */
    public static function listLibraryVersion(string $path, string $library): ?array
    {
        $version = self::getLibraryVersion($library, $path);

        if ($version === null) {
            // This library has no version control
            return null;
        }

        return [
            'name'              => $library,
            'code_version'      => $version,
            'database_version'  => self::getDatabaseVersion($library, $path),
            'next_init_version' => self::getLibraryNextVersion($library, $path)
        ];
    }



    /**
     * Ensure that the systems database exists
     *
     * @return void
     */
    protected static function ensureSystemsDatabase(): void
    {
        if (!sql('system', false)->schema()->database()->exists()) {
            sql('system', false)->schema()->database()->create();
        }

        sql('system', false)->use();
    }



    /**
     * Returns a list with all libraries for the specified path
     *
     * @param string $path
     * @return array
     */
    protected static function listLibraryPaths(string $path): array
    {
        $return  = [];
        $path    = Strings::endsWith($path, '/');

        if (!file_exists($path)) {
            throw new NotExistsException(tr('The specified libraray base path ":path" does not exist', [
                ':path' => $path
            ]));
        }

        $libraries = scandir($path);

        foreach ($libraries as $library) {
            // Skip hidden files, current and parent directory
            if ($library[0] === '.') {
                continue;
            }

            $file = $path . $library . '/';

            // Library paths MUST be directories
            if (is_dir($file)) {
                $return[$file] = strtolower($library);
            }
        }

        return $return;
    }



    /**
     * Initialize all libraries for system and plugins
     *
     * @param bool $system
     * @param bool $plugins
     * @return void
     */
    protected static function executeLibraries(bool $system = true, bool $plugins = true, ?string $comments = null): void
    {
        // Get a list of all available libraries and their versions
        $libraries = self::listLibraryVersions($system, $plugins);

        // Keep initializing libraries until none of them have inits available anymore
        while ($libraries) {
            // Order to have the nearest next init version first
            self::orderLibraries($libraries);
print_r($libraries);
            foreach ($libraries as $path => &$library) {
                if (!$library['next_init_version']) {
                    // This library has nothing more to initialize, remove it from the list
                    Log::success(tr('Finished updates for library ":library"', [':library' => $library['name']]));
                    unset($libraries[$path]);
                    break;
                }

                // Execute the update inits for this library and update the library information and start over
                self::executeLibrary($library['name'], $path, $library['next_init_version'], $comments);
                $library = self::listLibraryVersion($path, $library['name']);
                break;
            }
        }
    }



    /**
     * Order the libraries by next_init_version first
     *
     * @param array $libraries
     * @return void
     */
    protected static function orderLibraries(array &$libraries): void
    {
        // Remove libraries that have nothing to execute anymore
        foreach ($libraries as $path => $library) {
            if ($library['next_init_version'] === null) {
                unset($libraries[$path]);
            }
        }

        // Order
        uasort($libraries, function ($a, $b) {
            return version_compare($a['next_init_version'], $b['next_init_version']);
        });
    }



    /**
     * Initialize the specified library
     *
     * @param string $library
     * @param string $path
     * @param string $version
     * @param string|null $comments
     * @return string|null The next init version available for this library, if any. NULL if none are availabe anymore
     */
    protected static function executeLibrary(string $library, string $path, string $version, ?string $comments = null): ?string
    {
        // TODO Check later if we should be able to let init initialize itself
        if ($library === 'initialize') {
            // Never initialize the Init library itself!
            Log::warning(tr('Not processing library ":library", it has no versioning control available', [
                ':library' => $library
            ]));

            return false;
        }

        // Get the init object (may return NULL if not exist!)
        $init = self::getlibraryInit($path);

        if ($init === null) {
            // This library has no Init available, skip!
            Log::warning(tr('Not processing library ":library", it has no versioning control available', [
                ':library' => $library
            ]));
            return false;
        }

        return $init->updateOne($version, $comments);
    }



    /**
     * Returns the Init object for the specified library path
     *
     * @param string $path
     * @return Init|null
     */
    protected static function getlibraryInit(string $path): ?Init
    {
        $file = Strings::slash($path) . 'Init.php';

        if (!file_exists($file)) {
            // There is no init class available
            return null;
        }

        include_once($file);
        $init_class_path = Debug::getClassPath($file);
        $init = new $init_class_path();

        if (!($init instanceof Init)) {
            Log::Warning(tr('The Init.php file for the library ":library" is invalid, it should be an instance of the class Phoundation\Init\Init. This Init.php file will be ignored', [
                ':library' => Strings::from($path, ROOT)
            ]));
            return null;
        }

        return $init;
    }



    /**
     * Returns the path for the specified library
     *
     * @note If the specified library exists both as a system library and a plugin, an OutOfBoundsException exception
     *       will be thrown
     * @throws OutOfBoundsException|NotExistsException
     * @param string $library
     * @param bool $system
     * @param bool $plugin
     * @return string
     */
    protected static function findLibraryPath(string $library, bool $system = true, bool $plugin = true): string
    {
        $return = null;
        $paths  = [];

        if ($system) {
            $paths[] = self::CLASS_PATH_SYSTEM;
        }

        if ($plugin) {
            $paths[] = self::CLASS_PATH_PLUGINS;
        }

        if (empty($paths)) {
            throw new OutOfBoundsException(tr('Neither system not plugin paths specified to search'));
        }

        $directory = Strings::capitalize($library);

        // Library must exist in either SYSTEM or PLUGINS paths
        foreach($paths as $path) {
            // Library must exist and be a directory
            if (file_exists($path . $directory)) {
                if (is_dir($path . $directory)) {
                    if ($return) {
                        throw new OutOfBoundsException(tr('The specified library ":library" is both a system library and a plugin', [
                            ':library' => $library
                        ]));
                    }

                    $return = $path . $directory . '/';
                }
            }
        }

        if ($return) {
            return $return;
        }

        throw Exceptions::NotExistsException(tr('The specified library does not exist'))->makeWarning();
    }


    /**
     * Execute a forced initialization.
     *
     * This will drop the system database and initialize the sytem from scratch
     *
     * @return void
     * @throws \Throwable
     */
    protected static function force(): void
    {
        if (Debug::production()) {
            throw new AccessDeniedException(tr('For safety reasons, init force is NOT allowed on production environment!'));
        }

        if (!str_is_version(FORCE)) {
            if (!is_bool(FORCE)) {
                throw new SqlException(tr('Invalid "force" sub parameter ":force" specified. "force" can only be followed by a valid init version number', [':force' => FORCE]));
            }

            sql()->schema()->database()->drop();
            sql()->schema()->database()->create();
            sql()->use();
        }
    }





























































//    /**
//     * Execute database initialization
//     *
//     * @param string|null $project_from
//     * @param string|null $framework_from
//     * @return void
//     */
//    public static function initialize(?string $project_from = null, ?string $framework_from = null): void
//    {
//        global $_CONFIG, $core;
//
//        try {
//            // Are we allowed to init?
//            if (!Config::get('init.enabled.cli', false)) {
//                throw new InitException('This platform is not authorized to do init()', 'denied');
//            }
//
//            if (version_compare(PHP_VERSION, Core::PHP_MINIMUM_VERSION) < 1) {
//                throw new InitException(tr('This version of base requires at minimum PHP version ":required", anything below is not supported. The current version of PHP installed is ":installed" If you wish to force to run this version of base on this version of PHP, lower the required version defined with the constant Core::PHP_MINIMUM_VERSION in the top of ROOT/libs/startup.php', [
//                    ':required' => Core::PHP_MINIMUM_VERSION,
//                    ':installed' => PHP_VERSION
//                ]));
//            }
//
//            // Check tmp dir configuration
//            Path::ensure(TMP.'www');
//            touch(TMP.'www/.donotdelete');
//
//            // To do the init, we need the database version data. The database version check is ONLY executed on
//            // sql()->getVersion(), so connect to DB to force this check and get the DB version constants
//            Sql()->init();
//
//            if (empty($_CONFIG['db']['core']['init'])) {
//                throw new InitException(tr('Core database init system has been disabled in $_CONFIG[db][core][init]'), 'no-init');
//            }
//
//            if (!empty($core->register['time_zone_fail'])) {
//                // MySQL has no time_zone data, first initialize that, then reconnect
//                Mysql::getInstance()->importTimezones();
//            }
//
//            // Determine framework DB version (either from DB, or from command line)
//            $codeversions = []
//                'PROJECT'   => PROJECTDBVERSION,
//                'FRAMEWORK' => FRAMEWORKDBVERSION
//            ];
//
//            if ($framework_from) {
//                // We're (probably) redoing earlier versions, so remove registrations from earlier versions
//                sql()->query('DELETE FROM `versions`
//                                    WHERE (SUBSTRING(`framework`, 1, 1) != "-")
//                                      AND (INET_ATON(CONCAT(`framework`, REPEAT(".0", 3 - CHAR_LENGTH(`framework`) + CHAR_LENGTH(REPLACE(`framework`, ".", ""))))) >= INET_ATON(CONCAT("' . $framework_from.'", REPEAT(".0", 3 - CHAR_LENGTH("' . $framework_from.'") + CHAR_LENGTH(REPLACE("' . $framework_from.'", ".", ""))))))');
//                $codeversions['FRAMEWORK'] = sql()->get('SELECT `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;', 'framework');
//            }
//
//            // Determine project DB version (either from DB, or from command line)
//            if ($project_from) {
//                // We're (probably) doing earlier versions, so remove registrations from earlier versions
//                sql()->query('DELETE FROM `versions`
//                                    WHERE (SUBSTRING(`project`, 1, 1) != "-")
//                                      AND (INET_ATON(CONCAT(`project`, REPEAT(".0", 3 - CHAR_LENGTH(`project`) + CHAR_LENGTH(REPLACE(`project`, ".", ""))))) >= INET_ATON(CONCAT("' . $project_from .'", REPEAT(".0", 3 - CHAR_LENGTH("' . $project_from .'") + CHAR_LENGTH(REPLACE("' . $project_from .'", ".", ""))))))');
//                $codeversions['PROJECT'] = sql()->get('SELECT   `project`
//                                                             FROM     `versions`
//                                                             ORDER BY `id`
//                                                             DESC LIMIT 1;', 'project');
//            }
//
//            if (!$codeversions['FRAMEWORK'] or FORCE) {
//                // We're at 0, we must initialize everything!
//                // This point is just to detect that we need to init below. Dont init anything yet here. Create a fake
//                // user session in case some init parts require some username
//                if (empty($_SESSION['user'])) {
//                    $_SESSION['user'] = array('id'       => null,
//                        'name'     => 'System Init',
//                        'username' => 'init',
//                        'email'    => 'init@' . $_CONFIG['domain'],
//                        'rights'   => array('admin', 'users', 'rights'));
//                }
//
//            } elseif (!FORCE and (Core::FRAMEWORKCODEVERSION == $codeversions['FRAMEWORK']) and (PROJECTCODEVERSION == $codeversions['PROJECT'])) {
//                // Fetch me a pizza, all is just fine!
//                Log::success('The framework code and project code versions matches the database versions, so all is fine!');
//                $noinit = true;
//            }
//
//            if (version_compare(Core::FRAMEWORKCODEVERSION, $codeversions['FRAMEWORK']) < 0) {
//                if (!Strings::isVersion(Core::FRAMEWORKCODEVERSION)) {
//                    throw new InitException(tr('Cannot continue, the FRAMEWORK code version ":framework_version" (Defined at the top of ":file") is invalid', [
//                        ':framework_version' => Core::FRAMEWORKCODEVERSION,
//                        ':file' => ROOT.'/Phoundation/Core/Core.php'
//                    ]));
//                }
//
//                throw new InitException(tr('Cannot continue, the FRAMEWORK code version ":code" is OLDER (LOWER) than the database version ":db", the project is running with either old code or a too new database!', [
//                    ':code' => Core::FRAMEWORKCODEVERSION,
//                    ':db' => FRAMEWORKDBVERSION
//                ]));
//            }
//
//            if (version_compare(PROJECTCODEVERSION, $codeversions['PROJECT']) < 0) {
//                if (!Strings::isVersion(PROJECTCODEVERSION)) {
//                    throw new InitException(tr('Cannot continue, the PROJECT code version ":version" (Defined in ":file") is invalid', [
//                        ':version' => PROJECTCODEVERSION, '
//                        :file' => ROOT.'/config/project.php'
//                    ]));
//                }
//
//                throw new InitException(tr('Cannot continue, the PROJECT code version ":code" is OLDER (LOWER) than the database version ":db", the project is running with either old code or a too new database!', [
//                    ':code' => PROJECTCODEVERSION,
//                    ':db' => PROJECTDBVERSION
//                ]));
//            }
//
//            // Clear all cache
//            Cache::clear();
//
//            // From this point on, we are doing an init
//            if (FORCE) {
//                if (!is_bool(FORCE) and !Strings::isVersion(FORCE)) {
//                    throw new InitException('Invalid "force" sub parameter "'.Strings::log(FORCE).'" specified. "force" can only be followed by a valid init version number', 'invalidforce');
//                }
//
//                $init = 'forced init';
//
//            } else {
//                $init = 'init';
//            }
//
//            if (empty($noinit)) {
//                if (FORCE) {
//                    Log::information(tr('Starting ":init" FORCED from version ":force" for ":name" using PHP ":version"', [
//                        ':init'    => $init,
//                        ':force'   => FORCE,
//                        ':name'    => $_CONFIG['name'],
//                        ':version' => phpversion()
//                    ]));
//
//                } else {
//                    Log::information(tr('Starting ":init" for ":name" using PHP ":version"', [
//                        ':init'    => $init,
//                        ':name'    => $_CONFIG['name'],
//                        ':version' => phpversion()
//                    ]));
//                }
//
//                // Check MySQL timezone availability
//                if (!sql()->get('SELECT CONVERT_TZ("2012-06-07 12:00:00", "GMT", "America/New_York") AS `time`', 'time')) {
//                    Log::warning(tr('No timezone data found in MySQL, importing timezone data files now'));
//                    Log::information(tr('Please fill in MySQL root password in the following "Enter password:" request'));
//                    Log::warning(tr('You may ignore any "Warning: Unable to load \'/usr/share/zoneinfo/........\' as time zone. Skipping it." messages'));
//
//                    Mysql::getInstance()->importTimezones();
//                }
//
//                define('INITPATH', Strings::slash(realpath(ROOT.'init')));
//
//                $versions = [
//                    'framework' => $codeversions['FRAMEWORK'],
//                    'project'   => $codeversions['PROJECT']
//                ];
//
//                // ALWAYS First init framework, then project
//                foreach (array('framework', 'project') as $type) {
//                    Log::action(tr('Starting ":type" init', [':type' => $type]));
//
//                    // Get path for the init type (either init/framework or init/project) and then get a list of all
//                    // init files for the init type, and walk over each init file, and see if it needs execution or not
//                    $initpath  = INITPATH.Strings::slash($type);
//                    $files     = scandir($initpath);
//                    $utype     = strtoupper($type);
//                    $dbversion = ((FORCE and Strings::isVersion(FORCE)) ? FORCE : $codeversions[$utype]);
//
//                    // Cleanup and order list
//                    foreach ($files as $key => $file) {
//                        // Skip garbage
//                        if (($file == '.') or ($file == '..')) {
//                            unset($files[$key]);
//                            continue;
//                        }
//
//                        if ((file_extension($file) != 'php') or !Strings::isVersion(Strings::until($file, '.php'))) {
//                            Log::warning(tr('Skipping unknown file ":file"', [':file' => $file]));
//                            unset($files[$key]);
//                            continue;
//                        }
//
//                        $files[$key] = substr($file, 0, -4);
//                    }
//
//                    usort($files, 'version_compare');
//
//                    // Go over each init file, see if it needs execution or not
//                    foreach ($files as $file) {
//                        $version = $file;
//                        $file    = $file.'.php';
//
//                        if (version_compare($version, constant($utype.'CODEVERSION')) >= 1) {
//                            // This init file has a higher version number than the current code, so it should not yet be
//                            // executed (until a later time that is)
//                            Log::warning(tr('Skipped future init file ":version"', [':version' => $version]));
//
//                        } else {
//                            if (($dbversion === 0) or (version_compare($version, $dbversion) >= 1)) {
//                                // This init file is higher than the DB version, but lower than the code version, so it must be executed
//                                try {
//                                    if (file_exists($hook = $initpath.'hooks/pre_' . $file)) {
//                                        Log::action(tr('Executing newer init "pre" hook file with version ":version"', [
//                                            ':version' => $version
//                                        ]));
//
//                                        include_once($hook);
//                                    }
//
//                                }catch(Throwable $e) {
//                                    // INIT FILE FAILED!
//                                    throw new InitException(tr('Init "pre" hook file ":file" failed', [
//                                        ':type' => $type,
//                                        ':file' => $file
//                                    ]), $e);
//                                }
//
//                                try {
//                                    Log::action(tr('Executing newer init file with version ":version"', array(':version' => $version)), 'Debug::enabled()/cyan');
//                                    init_include($initpath.$file);
//
//                                }catch(Exception $e) {
//                                    /*
//                                     * INIT FILE FAILED!
//                                     */
//                                    throw new InitException('init(' . $type.'): Init file "' . $file.'" failed', $e);
//                                }
//
//                                try {
//                                    if (file_exists($hook = $initpath.'hooks/post_' . $file)) {
//                                        Log::action((tr('Executing newer init "post" hook file with version ":version"', array(':version' => $version)), 'Debug::enabled()/cyan');
//                                        include_once($hook);
//                                    }
//
//                                }catch(Exception $e) {
//                                    // INIT FILE FAILED!
//                                    throw new InitException('init(' . $type.'): Init "post" hook file "' . $file.'" failed', $e);
//                                }
//
//                                $versions[$type] = $version;
//
//                                self::addVersionRecord($versions['framework'], $versions['project']);
//
//                                Log::success('Finished init version "' . $version.'"', 'green');
//
//                            } else {
//                                // This init file has already been executed so we can skip it.
//                                Log::warning('Skipped older init file "' . $version.'"');
//                            }
//                        }
//                    }
//
//                    /*
//                     * There are no more init files. If the last executed init file has a lower
//                     * version than the code version still, then update the DB version to the
//                     * code version now.
//                     *
//                     * This way, the code version can be upped without having to add empty init files.
//                     */
//                    if (version_compare(constant($utype.'CODEVERSION'), $versions[$type]) > 0) {
//                        Log::warning(tr('Last init file was ":version" while code version is still higher at ":higher"', [':version' => $versions[$type], ':higher' => constant($utype.'CODEVERSION')]));
//                        Log::warning(tr('Updating database version to code version manually'));
//
//                        $versions[$type] = constant($utype.'CODEVERSION');
//
//                        self::addVersionRecord($versions['framework'], $versions['project']);
//                    }
//
//                    // Finished one init part (either type framework or type project)
//                    Log::success('Finished init', 'green');
//                }
//            }
//
//            if (Debug::production()) {
//                Log::action(tr('Removing data symlink or files in all languages'));
//
//                if (Config::get('languages.supported', [])) {
//                    foreach (Config::get('languages.supported', []) as $language => $name) {
//                        file_delete(ROOT.'www/'.substr($language, 0, 2).'/data', ROOT.'www/'.substr($language, 0, 2));
//                    }
//                }
//
//                Log::success(tr('Finished data symlink cleanup'));
//            }
//
//            Log::success(tr('Finished init sequence'));
//
//        }catch(Exception $e) {
//            switch ($e->getCode()) {
//                case 'invalidforce':
//                    foreach ($e->getMessages() as $message) {
//                        Log::notice($message);
//                    }
//
//                    die(1);
//
//                case 'validation':
//                    // In init mode, all validation warnings are fatal!
//                    $e->setWarning(false);
//            }
//
//            throw new InitException('Failed', $e);
//        }
//    }
//
//
//
//    /**
//     * There is a version difference between either the framework code and database versions, or the projects code and
//     * database versions. Determine which one differs, and how, so we can diplay the correct error
//     *
//     * Differences may be:
//     *
//     * Project or framework database may be older than the code
//     * Project or framework database may be newer than the code
//     *
//     * This function is only meant to display the correct error
//     *
//     * @return bool
//     */
//    public static function processVersionDiff(): bool
//    {
//        switch (Core::readRegister('system', 'script')) {
//            case 'base/info':
//                // no-break
//            case 'base/init':
//                // no-break
//            case 'base/sync':
//                // no-break
//            case 'base/update':
//                // no-break
//            case 'base/version':
//                return false;
//        }
//
//        $compare_project   = version_compare(PROJECTCODEVERSION  , PROJECTDBVERSION);
//        $compare_framework = version_compare(Core::FRAMEWORKCODEVERSION, FRAMEWORKDBVERSION);
//
//        if (PROJECTDBVERSION === 0) {
//            $versionerror     = 'Database is empty';
//
//        } else {
//            if ($compare_framework > 0) {
//                $versionerror = (empty($versionerror) ? "" : "\n") . tr('Framework core database ":db" version ":dbversion" is older than code version ":codeversion"', [
//                    ':db'          => Config::get('databases.sql.instances.system.name'),
//                    ':dbversion'   => FRAMEWORKDBVERSION,
//                    ':codeversion' => Core::FRAMEWORKCODEVERSION
//                ]));
//
//            } elseif ($compare_framework < 0) {
//                $versionerror = (empty($versionerror) ? "" : "\n") . tr('Framework core database ":db" version ":dbversion" is older than code version ":codeversion"', [
//                    ':db'          => Config::get('databases.sql.instances.system.name'),
//                    ':dbversion'   => FRAMEWORKDBVERSION,
//                    ':codeversion' => Core::FRAMEWORKCODEVERSION
//                ]);
//            }
//
//            if ($compare_project > 0) {
//                $versionerror = (empty($versionerror) ? "" : "\n") . tr('Project core database ":db" version ":dbversion" is older than code version ":codeversion"', [
//                    ':db'          => Config::get('databases.sql.instances.system.name'),
//                    ':dbversion'   => PROJECTDBVERSION,
//                    ':codeversion' => PROJECTCODEVERSION
//                ]);
//
//            } elseif ($compare_project < 0) {
//                $versionerror = (empty($versionerror) ? "" : "\n") . tr('Project core database ":db" version ":dbversion" is newer than code version ":codeversion"!', [
//                    ':db'          => Config::get('databases.sql.instances.system.name'),
//                    ':dbversion'   => PROJECTDBVERSION,
//                    ':codeversion' => PROJECTCODEVERSION
//                ]);
//            }
//        }
//
//        if (PLATFORM_HTTP or !Scripts::argument('--no-version-check')) {
//            throw Exceptions::InitException(tr('Please run script ROOT/scripts/base/init because ":error"', [':error' => $versionerror]))->makeWarning();
//        }
//
//        return true;
//    }
//
//
//
//    /**
//     * Version check failed. Check why
//     *
//     * Basically, this function is ONLY executed if we are executing the init script. The version check failed,
//     * which PROBABLY was because the database is empty at this point, but we cannot be 100% sure of that. This
//     * function will just make sure that the version check did not fail because of other reason, so that we can
//     * safely continue with system init
//     */
//    public static function processVersionFail(Throwable $e): void
//    {
//        $r = sql()->query('SHOW TABLES WHERE `Tables_in_' . Config::get('databases.sql.instances.system.name').'` = "versions";');
//
//        if ($r->rowCount($r)) {
//            throw new InitException('Failed version detection', $e);
//        }
//
//        define('FRAMEWORKDBVERSION', 0);
//        define('PROJECTDBVERSION'  , 0);
//
//        Log::warning(tr('No versions table found, assumed empty database'));
//    }
//
//
//
//    /**
//     * Execute specified hook file
//     *
//     * @param string $hook
//     * @param false $disabled
//     * @param array|null $params
//     * @return array|null
//     */
//    protected static function executeHook(string $hook, bool $disabled = false, ?array $params = null): ?array
//    {
//        if ($disabled) {
//            Log::warning(tr('Not executing hook ":hook", hook execution is disabled', [':hook' => $hook]));
//            return null;
//        }
//
//        try {
//            File::checkReadable(ROOT.'scripts/hooks/' . $hook);
//        } catch (FilesystemException $e) {
//            Log::warning(tr('Not executing hook ":hook", the file does not exist', [':hook' => $hook]));
//            return null;
//        }
//
//        return Processes::createCliScript('hooks/' . $hook)
//            ->setArguments($params)
//            ->executeReturnArray();
//    }
//
//
//
//    /**
//     * Upgrade the specified part of the specified version
//     *
//     * @param string $version
//     * @param string $part
//     * @return string
//     * @throws InitException
//     */
//    public static function versionUpgrade(string $version, string $part): string
//    {
//        if (!Strings::isVersion($version)) {
//            throw new InitException('Specified version is not a valid n.n.n version format');
//        }
//
//        $version = explode('.', $version);
//
//        switch ($part) {
//            case 'major':
//                $version[0]++;
//                break;
//
//            case 'minor':
//                $version[1]++;
//                break;
//
//            case 'revision':
//                $version[2]++;
//                break;
//
//            default:
//                throw new InitException(tr('Unknown version part type ":part" specified. Please specify one of "major", "minor", or "revision"', [
//                    ':part' => $part
//                ]));
//        }
//
//        return implode('.', $version);
//    }
//
//
//
//    /**
//     * Initialize the specified section.
//     *
//     * The section must be available as a directory with the name of the section in the ROOT/init path. If (for example)
//     * the section is called "mail", the init section in ROOT/init/mail will be executed. The section name will FORCE
//     * all sql()->query() calls to use the connector with the $section name.
//     *
//     * @param string $section
//     * @param string $version
//     * @return void
//     * @throws Throwable
//     */
//    public static function section(string $section, string $version): void
//    {
//        $path = ROOT.'init/' . $section.'/';
//
//        if (!file_exists($path)) {
//            throw new InitException(tr('init_section(): Specified section ":section" path ":path" does not exist', array(':section' => $section, ':path' => $path)), 'not-exists');
//        }
//
//        $connector = sql()->getConfiguration($section);
//
//        if (!$connector) {
//            throw new InitException(tr('init_section(): Specified section ":section" does not have a connector configuration. Please check $_CONFIG[db] or the `sql()->connectors` table', array(':section' => $section)), 'not-exists');
//        }
//
//        // Set the default connector to the connector for the specified section
//        Core::readRegister('sql()->connector', $section);
//        $exists = sql_get('SHOW TABLES LIKE "versions"', true);
//
//        if ($exists) {
//            if ($version) {
//                // Reset the versions table to the specified version
//                sql()->query('DELETE FROM `versions`
//                                    WHERE (SUBSTRING(`version`, 1, 1) != "-")
//                                      AND (INET_ATON(CONCAT(`version`, REPEAT(".0", 3 - CHAR_LENGTH(`version`) + CHAR_LENGTH(REPLACE(`version`, ".", ""))))) >= INET_ATON(CONCAT("' . $version.'", REPEAT(".0", 3 - CHAR_LENGTH("' . $version.'") + CHAR_LENGTH(REPLACE("' . $version.'", ".", ""))))))');
//            }
//
//            $dbversion = sql()->get('SELECT `version` FROM `versions` ORDER BY `id` DESC LIMIT 1', true);
//
//            if ($dbversion === null) {
//                // No version data found, we're at 0.0.0
//                $dbversion = '0.0.0';
//            }
//
//        } else {
//            // No version table found, we're at 0.0.0
//            $dbversion = '0.0.0';
//        }
//
//        Log::action(tr('Starting ":section" init at version ":version"', [
//            ':section' => $section,
//            ':version' => $dbversion
//        ]));
//
//        $files = scandir($path);
//
//        // Cleanup and order file list
//        foreach ($files as $key => $file) {
//            // Skip garbage
//            if (($file == '.') or ($file == '..')) {
//                unset($files[$key]);
//                continue;
//            }
//
//            if ((file_extension($file) != 'php') or !Strings::isVersion(Strings::until($file, '.php'))) {
//                Log::warning(tr('Skipping unknown file ":file"', [':file' => $file]));
//                unset($files[$key]);
//                continue;
//            }
//
//            $files[$key] = substr($file, 0, -4);
//        }
//
//        usort($files, 'version_compare');
//
//        // Go over each init file, see if it needs execution or not
//        foreach ($files as $file) {
//            $version = $file;
//            $file    = $file.'.php';
//
//            if (version_compare($version, $connector['version']) >= 1) {
//                // This init file has a higher version number than the current code, so it should not yet be executed (until a later time that is)
//                Log::warning(tr('Skipped future section ":section" init file ":version"', [
//                    ':version' => $version,
//                    ':section' => $section
//                ]));
//
//            } else {
//                if (($dbversion === 0) or (version_compare($version, $dbversion) >= 1)) {
//                    /*
//                     * This init file is higher than the DB version, but lower than the code version, so it must be executed
//                     */
//                    try {
//                        if (file_exists($hook = $path.'hooks/pre_' . $file)) {
//                            Log::action(tr('Executing newer init "pre" hook file with version ":version"', [':version' => $version]));
//                            include_once($hook);
//                        }
//
//                    }catch(Exception $e) {
//                        // INIT FILE FAILED!
//                        throw new InitException('init(' . $section.'): Init "pre" hook file "' . $file.'" failed', $e);
//                    }
//
//                    try {
//                        Log::action(tr('Executing newer init file with version "' . $version.'"', [':version' => $version]));
//                        init_include($path.$file, $section);
//
//                    }catch(Throwable $e) {
//                        // INIT FILE FAILED!
//                        throw new InitException('init(' . $section.'): Init file "' . $file.'" failed', $e);
//                    }
//
//                    try {
//                        if (file_exists($hook = $path.'hooks/post_' . $file)) {
//                            Log::action(tr('Executing newer init "post" hook file with version ":version"', [':version' => $version]));
//                            include_once($hook);
//                        }
//
//                    }catch(Throwable $e) {
//                        // INIT FILE FAILED!
//                        throw new InitException('init(' . $section.'): Init "post" hook file "' . $file.'" failed', $e);
//                    }
//
//                    sql()->query('INSERT INTO `versions` (`version`) VALUES (:version)', [':version' => $version]);
//                    Log::success(tr('Finished init version ":version"', [':version' => $version]));
//
//                } else {
//                    // This init file has already been executed so we can skip it.
//                    if (Debug::enabled()) {
//                        Log::warning(tr('Skipped older init file ":version"', [':version' => $version]));
//                    }
//                }
//            }
//        }
//
//        /*
//         * There are no more init files. If the last executed init file has a lower
//         * version than the code version still, then update the DB version to the
//         * code version now.
//         *
//         * This way, the code version can be upped without having to add empty init files.
//         */
//        if (version_compare($dbversion, $version) > 0) {
//            Log::warning(tr('Last init file was ":version" while code version is still higher at ":higher"', [
//                ':version' => $version,
//                ':higher' => $connector['version']
//            ]));
//            Log::warning(tr('Updating database version to code version manually'));
//
//            $version = $connector['version'];
//
//            sql()->query('INSERT INTO `versions` (`version`) VALUES (:version)', [':version' => $version]);
//        }
//
//        // Finished one init part (either type version or type project)
//        Log::success(tr('Finished section ":section" init', [':section' => $section]));
//
//        // Reset the default connector
//        Core::readRegister('sql()->connector', null);
//    }
//
//
//
//    /**
//     * Reset the database version back to the current code version in case it is ahead. Any extra entries in the
//     * versions table AFTER the current version will be wiped. This option does NOT allow to reset the database version
//     * in case the current code version is ahead. For that, a normal init must be executed
//     *
//     * @version 2.5.2: Added function and documentation
//     * @return int The amount of entries removed from the `versions` table
//     */
//    public static function reset(): int
//    {
//        $versions = sql()->query('SELECT `id`, `framework`, `project` FROM `versions`');
//        $erase    = sql()->prepare('DELETE FROM `versions` WHERE `id` = :id');
//        $changed  = 0;
//
//        while ($version = sql()->fetch($versions)) {
//            if (version_compare($version['framework'], Core::FRAMEWORKCODEVERSION) > 0) {
//                $erase->execute(array(':id' => $version['id']));
//                $changed++;
//                continue;
//            }
//
//            if (version_compare($version['project'], PROJECTCODEVERSION) > 0) {
//                $erase->execute(array(':id' => $version['id']));
//                $changed++;
//            }
//        }
//
//        return $changed;
//    }
//
//
//
//    /**
//     * Get the verion of the init file with the highest version for the specified section
//     *
//     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
//     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
//     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
//     * @category Function reference
//     * @package init
//     * @version 2.5.2: Added function and documentation
//     *
//     * @param string $section The section (either "framework", "project", or a custom one) in which to find the highest init file version
//     * @return string The highest init file version available for the specified section
//     */
//    public static function getHighestFileVersion(string $section): string
//    {
//        switch ($section) {
//            case 'framework':
//                // no-break
//            case 'project':
//                // These are the default sections, these are okay
//                break;
//
//            default:
//                // Custom section, check if it exists
//                if (!file_exists(ROOT.'init/' . $section)) {
//                    throw new InitException(tr('The specified custom init section ":section" does not exist', [':section' => $section]));
//                }
//        }
//
//        $version = '0.0.0';
//        $files   = scandir(ROOT.'init/' . $section);
//
//        foreach ($files as $file) {
//            if (($file === '.') or ($file === '..')) {
//                continue;
//            }
//
//            $file = Strings::untilReverse($file, '.php');
//
//            if (version_compare($file, $version) === 1) {
//                $version = $file;
//            }
//        }
//
//        return $version;
//    }
//
//
//
//    /**
//     * Add a record to the versions table
//     *
//     * @param string $framework
//     * @param string $project
//     * @return void
//     * @throws Throwable
//     */
//    protected function addVersionRecord(string $framework, string $project): void
//    {
//        sql()->query('INSERT INTO `versions` (`framework`, `project`)
//                            VALUES                 (:framework , :project )', [
//            ':framework' => $framework,
//            ':project'   =>$project
//        ]);
//    }
}