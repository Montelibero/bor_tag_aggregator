<?php

namespace MTLA\BSN;

class Account
{
    private string $id;

    private ActiveTagCollection $Tags;

    public function __construct(string $id)
    {
        $this->id = trim($id);
        $this->Tags = new ActiveTagCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->getId();
    }

    public function getTags(): ActiveTagCollection
    {
        return $this->Tags;
    }
}