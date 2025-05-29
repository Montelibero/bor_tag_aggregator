<?php

namespace MTLA\BSN;

use RuntimeException;

class Tag
{
    private string $name;
    private ?self $Pair = null;

    public function __construct(string $name)
    {
        $this->name = trim($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function setPair(Tag $Tag): void
    {
        $TagPair = $Tag->getPair();
        if ($TagPair !== null && $TagPair !== $this) {
            throw new RuntimeException('Tot correct Pair Tag (' . $TagPair->getName() . ')');
        }

        $this->Pair = $Tag;
    }

    public function getPair(): ?Tag
    {
        return $this->Pair;
    }
}