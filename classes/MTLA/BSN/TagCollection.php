<?php

namespace MTLA\BSN;

class TagCollection
{
    /** @var Tag[] */
    private array $tags = [];

    public function addTag(Tag $Tag): void
    {
        $this->tags[$Tag->getName()] = $Tag;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getTag(string $name): ?Tag
    {
        return $this->tags[$name] ?? null;
    }
}