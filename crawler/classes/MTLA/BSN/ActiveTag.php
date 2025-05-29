<?php

namespace MTLA\BSN;

class ActiveTag
{
    private Account $From;
    private Tag $Tag;
    private Account $To;

    public function __construct(Account $From, Tag $Tag, Account $To)
    {
        $this->From = $From;
        $this->Tag = $Tag;
        $this->To = $To;
    }

    public function getFrom(): Account
    {
        return $this->From;
    }

    public function getTag(): Tag
    {
        return $this->Tag;
    }

    public function getTo(): Account
    {
        return $this->To;
    }

    public function getTagName(): string
    {
        return $this->Tag->getName();
    }

    public function getFullName(): string
    {
        return $this->From->getId() . '-' . $this->To->getId() . '-' . $this->Tag->getName();
    }
}