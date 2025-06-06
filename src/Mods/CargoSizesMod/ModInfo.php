<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod;

use AppUtils\FileHelper\FileInfo;

class ModInfo
{
    public const MOD_NAME = 'Extra Cargo Size for Ships';
    public const MOD_HOMEPAGE = 'https://github.com/Mistralys/x4-mod-cargo-sizes';
    public const MOD_NEXUSMODS = 'https://www.nexusmods.com/x4foundations/mods/1713';
    public const MOD_AUTHOR = 'AeonsOfTime';
    public const MOD_DESCRIPTION = 'Provides options to increase the cargo size of transports, miners, auxiliaries and carriers.';

    public static function getVersionFile(): FileInfo
    {
        return FileInfo::factory(__DIR__ . '/../../../mod-version.txt');
    }

    /**
     * Gets the version of the mod.
     * @return string
     */
    public static function getVersion(): string
    {
        return ModInfo::getVersionFile()->getContents();
    }
}
