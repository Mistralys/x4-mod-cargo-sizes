<?php

declare(strict_types=1);

namespace Mistralys\X4;

function dec(float|int $value, int $decimals): string
{
    return number_format($value, $decimals, '.', '');
}

function dec1(float|int $value): string
{
    return dec($value, 1);
}

function dec2(float|int $value): string
{
    return dec($value, 2);
}

function dec3(float|int $value): string
{
    return dec($value, 3);
}
