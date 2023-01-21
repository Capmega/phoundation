<?php

namespace Phoundation\Cli;

class Documentation
{
    /**
     * Script help text
     *
     * @var string|null $help
     */
    protected static ?string $help = null;

    /**
     * Script usage information
     *
     * @var string|null $usage
     */
    protected static ?string $usage = null;



    /**
     * Returns the help text
     *
     * @return string
     */
    public static function getHelp(): string
    {
        return self::$help;
    }



    /**
     * Sets the help text
     *
     * @param string $help
     * @return void
     */
    public static function setHelp(string $help): void
    {
        self::$help = $help;
    }



    /**
     * Returns the usage text
     *
     * @return string
     */
    public static function getUsage(): string
    {
        return self::$usage;
    }



    /**
     * Sets the usage text
     *
     * @param string $usage
     * @return void
     */
    public static function setUsage(string $usage): void
    {
        self::$usage = $usage;
    }
}