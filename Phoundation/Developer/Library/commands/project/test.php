<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Validator;


/**
 * Script test
 *
 * This is a test script where we can test various new systems
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho info');

CliDocumentation::setHelp('The info script will show detailed information about the current framework, project, database and more


ARGUMENTS


-');

ArgvValidator::new()->validate();


$array = [
    'test-xora'          => ' 1 ',
    'test-xorb'          => ' 1 ',
    'test-numeric'       => ' 1 ',
    'test-string'        => 'blergh',
    'test-list-array'    => [
        'sub-list-array' => [
            'a',
            'b',
            'c',
            'd',
            'e',
        ],
    ],
    'test-array'         => [
        'sub-integer'   => 23974,
        'sub-email'     => 'so.oostenbrink@gmail.com',
        'sub-sub-array' => [
            'sub-sub-email' => 'so.oostenbrink@gmail.com',
        ],
    ],
    'test-not-array'     => 'blergh!',
    'test-not-validated' => 'This entry should be deleted!',
    'test-list-array2'   => [
        'sub-list-array' => [
            'a',
            'b',
            'c',
            'd',
            'e',
        ],
    ],
    'test-name'          => 'so.oostenbrink@gmail.com',
    'test-email'         => 'so.oostenbrink@gmail.com',
];

Validator::new($array)
         ->select('test-xora')->xor('test-xorb')->isArray()
         ->select('test-optional')->isOptional([])->isArray()
         ->select('test-list-array')->recurse()
         ->select('sub-list-array')->hasMinimumElements(3)->each()->hasMinCharacters(1)
         ->validate()
         ->select('test-array')->isArray()->recurse()
         ->select('sub-integer')->isInteger()
         ->select('sub-sub-array')->isArray()->recurse()
         ->select('sub-sub-email')->isEmail()
         ->validate()
         ->select('sub-email')->isString()
         ->validate()
         ->validate();

show($array);


//$array = [
//];
//
//Validator::array($array)
//    ->select('test-name')->isString()
//    ->select('test-numeric')->isNumeric()
//    ->select('test-string')->isString()
//////        ->select('test-not-array')->isArray()->recurse()
////        ->select('sub-integer')->isInteger()
////        ->select('sub-email')->isString()
////        ->validate()
//    ->select('test-integer')->isInteger()
//    ->select('test-list-array')->hasMinimumElements(3)->each()->hasMinSize(4)
//    ->select('test-email')->isEmail()
//    ->validate();
//
//show($array);
//


//$result = Processes::create('ping')
//    ->addArgument('1.1.1.1')
//    ->setTimeout(1)
//    ->executeBackground();
//
//$result = Processes::create('ping')
//    ->addArgument('8.8.8.8')
//    ->setTimeout(1)
//    ->executeBackground();
//
//ProcessCommands::local()->psFull(1);
//
//Workers::create('ping')
//    ->addArgument('$server_restrictions$')
//    ->setTimeout(10)
//    ->setKey('$server_restrictions$')
//    ->setWaitWorkerFinish(true)
//    ->setValues([
//        '1.1.1.1',
//        '8.8.8.8',
//        'google.com',
//        'reddit.com',
//        'cnn.com',
//        '1.1.1.1',
//        '8.8.8.8',
//        'google.com',
//        'reddit.com',
//        'cnn.com',
//        'reddit.com',
//        'cnn.com'
//    ])->start();
//
//FilesystemCommands::local()->which('chmod');
//FilesystemCommands::local()->chmod(DIRECTORY_ROOT . 'README.md', 0o440);
//FilesystemCommands::local()->mkdir(DIRECTORY_ROOT . 'a/b/c/d/e', 0770,true);
//FilesystemCommands::local()->mkdir(DIRECTORY_ROOT . 'a/b/c/d/e', 0770,true);
//file_put_contents(DIRECTORY_ROOT . 'a/b/c/d/e/f', 'This the first test file');
//FilesystemCommands::local()->delete(DIRECTORY_ROOT . 'a/b/e');
//FilesystemCommands::local()->delete(DIRECTORY_ROOT . 'a/b/c', true, true);
