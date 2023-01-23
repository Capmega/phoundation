<?php

namespace Phoundation\Developer;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Class TestDataGenerator
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class TestDataGenerator
{
    /**
     * Returns a random code
     *
     * @return string
     */
    public static function code(int $min = 3, int $max = 6): string
    {
        return Strings::random(mt_rand(3, 6));
    }



    /**
     * Returns a random number
     *
     * @return int
     */
    public static function number(int $min = 0, int $max = 1000000): int
    {
        return (int) Strings::random(mt_rand($min, $max));
    }



    /**
     * Returns a random percentage
     *
     * @return int
     */
    public static function percentage(): int
    {
        return (int) Strings::random(mt_rand(0, 100));
    }



    /**
     * Returns a random name
     *
     * @return string
     */
    public static function name(): string
    {
        return Strings::random(mt_rand(3, 10));
    }



    /**
     * Returns a random domain
     *
     * @return string
     */
    public static function domain(): string
    {
        return Strings::random(mt_rand(3,16) . '.' . pick_random('com', 'org', 'net', 'info'));
    }



    /**
     * Returns a random email address
     *
     * @return string
     */
    public static function email(): string
    {
        return self::name() . '@' . self::domain();
    }



    /**
     * Returns a random amount of lorem ipsum paragraps
     *
     * @return string
     */
    public static function description(int $min = 0, int $max = 6): string
    {
        $amount = mt_rand($min, $max);
        $return = '';

        while ($amount > 0) {
            if ($amount >= 1) {
                $return .= 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Venenatis a condimentum vitae sapien pellentesque habitant morbi. Mattis ullamcorper velit sed ullamcorper morbi tincidunt. Id ornare arcu odio ut sem nulla pharetra diam sit. Eu volutpat odio facilisis mauris sit. Turpis massa tincidunt dui ut ornare lectus sit amet est. Dignissim cras tincidunt lobortis feugiat vivamus at. Neque sodales ut etiam sit amet. Vel fringilla est ullamcorper eget nulla facilisi etiam dignissim. Sed velit dignissim sodales ut eu sem integer vitae. Feugiat in ante metus dictum at tempor commodo. Odio tempor orci dapibus ultrices in iaculis nunc. Ultrices eros in cursus turpis massa. Tellus pellentesque eu tincidunt tortor aliquam nulla. Interdum consectetur libero id faucibus. Nascetur ridiculus mus mauris vitae ultricies leo. Volutpat odio facilisis mauris sit. Nibh nisl condimentum id venenatis a condimentum vitae.' . PHP_EOL;
            }

            if ($amount >= 2) {
                $return .= 'Morbi non arcu risus quis varius quam quisque. Erat nam at lectus urna duis convallis. Integer malesuada nunc vel risus commodo. Imperdiet proin fermentum leo vel orci porta. Id neque aliquam vestibulum morbi blandit cursus risus at ultrices. Hac habitasse platea dictumst vestibulum. At in tellus integer feugiat. Lorem ipsum dolor sit amet consectetur. Quam quisque id diam vel quam elementum pulvinar etiam non. Elementum integer enim neque volutpat ac tincidunt vitae semper quis. Sagittis nisl rhoncus mattis rhoncus urna neque viverra justo nec. Faucibus turpis in eu mi bibendum neque egestas congue quisque. Malesuada pellentesque elit eget gravida cum sociis natoque penatibus. Placerat orci nulla pellentesque dignissim enim. Vitae proin sagittis nisl rhoncus mattis rhoncus. Sit amet porttitor eget dolor. Est ante in nibh mauris cursus mattis molestie. Eu facilisis sed odio morbi.' . PHP_EOL;
            }

            if ($amount >= 3) {
                $return .= 'In dictum non consectetur a erat nam at lectus. Ut faucibus pulvinar elementum integer enim neque. Turpis egestas pretium aenean pharetra magna. Ut ornare lectus sit amet est placerat in egestas. Sit amet risus nullam eget felis eget nunc. In dictum non consectetur a erat. Scelerisque eleifend donec pretium vulputate sapien nec. Non odio euismod lacinia at quis risus sed. Dolor sit amet consectetur adipiscing. Sollicitudin tempor id eu nisl nunc mi ipsum. Quis vel eros donec ac odio tempor orci dapibus ultrices. Aliquet sagittis id consectetur purus. Purus gravida quis blandit turpis. Amet nisl purus in mollis. Viverra tellus in hac habitasse platea dictumst vestibulum. Lacus laoreet non curabitur gravida.' . PHP_EOL;
            }

            if ($amount >= 4) {
                $return .= 'Fermentum odio eu feugiat pretium. At consectetur lorem donec massa. Vulputate eu scelerisque felis imperdiet. Massa tempor nec feugiat nisl pretium fusce id. Quisque id diam vel quam elementum pulvinar etiam non. Sodales ut eu sem integer vitae justo eget magna. Diam in arcu cursus euismod quis viverra. Ultrices in iaculis nunc sed augue lacus viverra. Morbi leo urna molestie at elementum eu. Nibh sit amet commodo nulla facilisi nullam vehicula. Faucibus in ornare quam viverra orci sagittis eu. Amet luctus venenatis lectus magna fringilla urna porttitor rhoncus. Augue neque gravida in fermentum et sollicitudin. Pellentesque habitant morbi tristique senectus et netus et malesuada. Et malesuada fames ac turpis egestas integer eget. Eget nulla facilisi etiam dignissim diam. Id aliquet risus feugiat in ante. Aliquam nulla facilisi cras fermentum odio eu. Vitae sapien pellentesque habitant morbi tristique. Sed egestas egestas fringilla phasellus faucibus scelerisque eleifend.' . PHP_EOL;
            }

            if ($amount >= 5) {
                $return .= 'Tempor nec feugiat nisl pretium fusce. Convallis tellus id interdum velit laoreet id donec. Scelerisque eu ultrices vitae auctor eu augue. Elementum nisi quis eleifend quam adipiscing vitae. Magna ac placerat vestibulum lectus. Tortor pretium viverra suspendisse potenti nullam. Semper viverra nam libero justo laoreet. Scelerisque purus semper eget duis at tellus. Lacus sed turpis tincidunt id. Purus in mollis nunc sed id semper risus. Urna duis convallis convallis tellus id interdum velit laoreet. Semper viverra nam libero justo. Vitae proin sagittis nisl rhoncus mattis rhoncus urna neque viverra.' . PHP_EOL;
            }

            if ($amount >= 6) {
                $return .= 'Imperdiet nulla malesuada pellentesque elit. Et malesuada fames ac turpis egestas sed tempus. Elementum integer enim neque volutpat ac tincidunt vitae semper. Aenean pharetra magna ac placerat vestibulum. Auctor elit sed vulputate mi sit amet. A erat nam at lectus urna duis. Ut sem nulla pharetra diam sit. Elementum curabitur vitae nunc sed velit dignissim sodales ut eu. Non pulvinar neque laoreet suspendisse interdum consectetur libero id faucibus. Risus quis varius quam quisque id. In fermentum et sollicitudin ac orci. Proin libero nunc consequat interdum. Id porta nibh venenatis cras sed felis eget velit. Non quam lacus suspendisse faucibus interdum posuere lorem. Ac tortor vitae purus faucibus ornare suspendisse sed.' . PHP_EOL;
            }

            if ($amount >= 7) {
                $return .= 'Cursus metus aliquam eleifend mi in nulla posuere sollicitudin aliquam. At tellus at urna condimentum mattis pellentesque id nibh. Ullamcorper eget nulla facilisi etiam dignissim diam quis enim lobortis. Arcu felis bibendum ut tristique et egestas quis ipsum suspendisse. Tincidunt lobortis feugiat vivamus at augue eget arcu. Morbi tincidunt ornare massa eget egestas purus viverra. Sed arcu non odio euismod lacinia. Turpis egestas maecenas pharetra convallis posuere. Nulla pharetra diam sit amet nisl suscipit adipiscing bibendum est. Aliquam id diam maecenas ultricies mi eget. Turpis tincidunt id aliquet risus feugiat in ante metus dictum. Pharetra pharetra massa massa ultricies mi.' . PHP_EOL;
            }

            if ($amount >= 8) {
                $return .= 'Justo donec enim diam vulputate ut pharetra sit amet aliquam. Enim neque volutpat ac tincidunt. Lectus urna duis convallis convallis tellus id interdum. Mi bibendum neque egestas congue quisque egestas diam in arcu. Augue eget arcu dictum varius duis at consectetur lorem. Vulputate sapien nec sagittis aliquam malesuada bibendum arcu vitae elementum. Amet justo donec enim diam vulputate ut pharetra. Adipiscing tristique risus nec feugiat. Sed enim ut sem viverra aliquet. Turpis tincidunt id aliquet risus feugiat in. Varius quam quisque id diam vel. Ultrices vitae auctor eu augue ut lectus arcu. Vitae justo eget magna fermentum iaculis eu. Mi sit amet mauris commodo. Venenatis lectus magna fringilla urna porttitor rhoncus dolor purus. Pellentesque elit eget gravida cum sociis natoque penatibus. Vulputate dignissim suspendisse in est ante in. Eget velit aliquet sagittis id consectetur purus ut.' . PHP_EOL;
            }

            if ($amount >= 9) {
                $return .= 'Diam donec adipiscing tristique risus nec. Sit amet venenatis urna cursus eget nunc. Dui sapien eget mi proin sed libero enim sed. Consequat semper viverra nam libero justo. Aenean sed adipiscing diam donec adipiscing tristique risus nec. Enim nec dui nunc mattis enim ut tellus. Sit amet venenatis urna cursus eget nunc scelerisque viverra mauris. Feugiat vivamus at augue eget. Proin fermentum leo vel orci porta non pulvinar neque. Pellentesque habitant morbi tristique senectus et. Purus sit amet volutpat consequat mauris nunc congue nisi vitae. Velit euismod in pellentesque massa. Feugiat sed lectus vestibulum mattis ullamcorper. Lorem ipsum dolor sit amet. Sed euismod nisi porta lorem mollis aliquam ut. Eu tincidunt tortor aliquam nulla facilisi. Odio aenean sed adipiscing diam donec adipiscing tristique. Nisl nisi scelerisque eu ultrices vitae auctor. Donec enim diam vulputate ut pharetra sit. Faucibus vitae aliquet nec ullamcorper sit.' . PHP_EOL;
            }

            if ($amount >= 10) {
                $return .= 'Sed egestas egestas fringilla phasellus faucibus scelerisque eleifend. Quis auctor elit sed vulputate mi sit amet mauris. Ornare suspendisse sed nisi lacus sed viverra tellus. Sapien eget mi proin sed libero enim. Quam adipiscing vitae proin sagittis nisl rhoncus mattis rhoncus urna. Accumsan tortor posuere ac ut consequat semper viverra nam libero. Egestas integer eget aliquet nibh praesent tristique. Risus feugiat in ante metus dictum at. Amet mauris commodo quis imperdiet. Cursus sit amet dictum sit amet justo donec. Ultricies mi eget mauris pharetra et ultrices neque. Eget mi proin sed libero. Commodo nulla facilisi nullam vehicula ipsum. Donec ultrices tincidunt arcu non sodales. Lectus proin nibh nisl condimentum id venenatis a condimentum vitae. Non blandit massa enim nec dui nunc. Nunc pulvinar sapien et ligula ullamcorper. Lobortis feugiat vivamus at augue eget arcu dictum varius. In nulla posuere sollicitudin aliquam ultrices sagittis orci. Vitae purus faucibus ornare suspendisse.' . PHP_EOL;
            }

            $amount -= 10;
        }

        return $return;
    }
}