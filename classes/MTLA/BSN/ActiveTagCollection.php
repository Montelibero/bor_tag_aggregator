<?php

namespace MTLA\BSN;

class ActiveTagCollection
{
    /** @var ActiveTag[] */
    private array $active_tags = [];
    /** @var ActiveTag[] */
    private array $by_from = [];
    /** @var ActiveTag[] */
    private array $by_to = [];
    /** @var ActiveTag[] */
    private array $by_name = [];

    public function addActiveTag(ActiveTag $ActiveTag): void
    {
        $full_name = $ActiveTag->getFullName();
        if (array_key_exists($full_name, $this->active_tags)) {
            throw new \RuntimeException('ActiveTag already exists: ' . $full_name);
        }

        $this->active_tags[$full_name] = $ActiveTag;


    }

    /**
     * @return ActiveTag[]
     */
    public function getActiveTags(): array
    {
        return $this->active_tags;
    }

    public function getActiveTag(string $name): ?ActiveTag
    {
        return $this->active_tags[$name] ?? null;
    }
}