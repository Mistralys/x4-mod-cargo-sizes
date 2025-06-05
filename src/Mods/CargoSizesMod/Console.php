<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

class Console
{
    public static function header(string $message, ...$args) : void
    {
        if(!empty($args)) {
            $message = vsprintf($message, $args);
        }

        echo str_repeat('-', 60) . PHP_EOL;
        echo $message . PHP_EOL;
        echo str_repeat('-', 60) . PHP_EOL;
    }

    public static function line1(string $message, ...$args) : void
    {
        self::line('- '.$message, ...$args);
    }

    public static function line2(string $message, ...$args) : void
    {
        self::line('  - '.$message, ...$args);
    }

    public static function line(string $message, ...$args) : void
    {
        if(!empty($args)) {
            $message = vsprintf($message, $args);
        }

        echo $message . PHP_EOL;
    }

    public static function nl() : void
    {
        echo PHP_EOL;
    }
}
