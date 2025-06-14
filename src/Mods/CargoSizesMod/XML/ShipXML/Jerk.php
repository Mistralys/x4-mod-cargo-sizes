<?php

declare(strict_types=1);

namespace Mistralys\X4\Mods\CargoSizesMod\XML\ShipXML;

class Jerk
{
    private float $strafe;
    private float $angular;
    private JerkForward $forward;
    private JerkBoost $boost;
    private JerkTravel $travel;

    public function __construct(float $strafe, float $angular, JerkForward $forward, JerkBoost $boost, JerkTravel $travel)
    {
        $this->strafe = $strafe;
        $this->angular = $angular;
        $this->forward = $forward;
        $this->boost = $boost;
        $this->travel = $travel;
    }

    public function getStrafe(): float
    {
        return $this->strafe;
    }

    public function getAngular(): float
    {
        return $this->angular;
    }

    public function getForward(): JerkForward
    {
        return $this->forward;
    }

    public function getBoost(): JerkBoost
    {
        return $this->boost;
    }

    public function getTravel(): JerkTravel
    {
        return $this->travel;
    }
}
