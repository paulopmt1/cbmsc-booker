<?php

namespace App\Entity;

interface BaseEntityInterface extends \JsonSerializable
{
    public function getId(): ?int;
}
