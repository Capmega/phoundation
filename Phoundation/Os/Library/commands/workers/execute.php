<?php

/**
 * Command workers execute
 *
 * This command will execute a parent worker that will execute its task through multiple parallel child workers
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Workers\Worker;


CliDocumentation::setUsage('./pho workers execute "ping" -c 100 -w 10 -a "1.1.1.1"');

CliDocumentation::setHelp('This command executes either a specific task (if specified) or will try to execute all 
pending tasks that should be executed


ARGUMENTS


TASK                                    The specific task code to execute

-c,--cycles CYCLES                      The amount of time this commands needs to be executed

-w,--workers WORKERS                    The amount of parallel workers to use


OPTIONAL ARGUMENTS


[-s,--server SERVER_NAME]               The server on which each worker should execute their task

[-a,--arguments ARGUMENTS]              A comma separated list of arguments for each worker

[-v,--variables VARIABLES]              A comma separated list of key / value variables that should be applied on the 
                                        command for each worker

[-e,--environment-variables VARIABLES]  A comma separated list of environment variables that should be set before 
                                        executing each worker
      
[-t,--timeout TIMEOUT]                  The amount of seconds that each worker should be allowed to execute the task

[-s,--sudo USERNAME]                    The username to use to execute each worker with sudo

[-t,--term ???]                         ???

[-w,--wait SECONDS]                     The amount of seconds to wait for the workers to start the next worker

[--no-cache ???]                        ???

[--io-nice-level LEVEL]                 The nice level for the IO operations of the workers
   
[--nice-level LEVEL]                    The CPU nice level for the workers

[--accepted-exit-codes CODES]           A list of accepted exit codes for the workers that should be considered as 
                                        successful

[--min-workers AMOUNT]                  The minimum and maximum amount of workers that should be used to execute the
                                        task

[--max-workers AMOUNT]                  The maximum and maximum amount of workers that should be used to execute the 
                                        task
');

CliDocumentation::setAutoComplete([
    'arguments' => [
        0              => true,
        '-c,--cycles'  => true,
        '-w,--workers' => true,
    ],
]);


// Get arguments
$argv = ArgvValidator::new()
                     ->select('command', true)->hasMaxCharacters(8192)
                     ->select('-c,--cycles', true)->isInteger()->isPositive()
                     ->select('-w,--workers', true)->isInteger()->isPositive()
                     ->select('-a,--arguments', true)->isOptional()->hasMaxCharacters(8192)->sanitizeForceArray()->eachField()->isString()
//                     ->select('-s,--server', true)->isOptional()
//                     ->select('-v,--variables', true)->isOptional()
//                     ->select('-e,--environment-variables', true)->isOptional()
//                     ->select('-t,--timeout', true)->isOptional()
//                     ->select('-s,--sudo', true)->isOptional()
//                     ->select('-t,--term', true)->isOptional()
//                     ->select('-w,--wait', true)->isOptional()
//                     ->select('--no-cache', true)->isOptional()
//                     ->select('--io-nice-level', true)->isOptional()
//                     ->select('--nice-level', true)->isOptional()
//                     ->select('--accepted-exit-codes', true)->isOptional()
//                     ->select('--min-workers', true)->isOptional()->isPositive()
//                     ->select('--max-workers', true)->isOptional()->isPositive()
                     ->validate();


// Execute the command with workers
Worker::new($argv['command'])
      ->setCycles($argv['cycles'])
      ->setArguments($argv['arguments'])
      ->setMinimumWorkers($argv['workers'])
      ->setMaximumWorkers($argv['workers'])
//      ->setVariables($this->getVariables())
//      ->setEnvironmentVariables($this->getEnvironmentVariables())
//      ->setEnvironmentVariables($this->getEnvironmentVariables())
//      ->setAcceptedExitCodes($this->getAcceptedExitCodes())
//      ->setTimeout($this->getTimeout())
//      ->setWait($this->getWait())
//      ->setNice($this->getNice())
//      ->setIoNiceClass($this->getIonice())
//      ->setIoNiceLevel($this->getIoniceLevel())
//      ->setNoCache($this->getNocache())
//      ->setSudo($this->getSudo())
//      ->setTerm($this->getTerm())
//      ->setInputRedirect($this->getInputRedirect())
//      ->setOutputRedirect($this->getOutputRedirect())
      ->start();

