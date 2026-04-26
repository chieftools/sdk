<?php

namespace ChiefTools\SDK\UI\CommandPalette;

readonly class Item
{
    public function __construct(
        public string $id,
        public string $type,
        public string $title,
        public string $url,
        public string $category,
        public ?string $subtitle = null,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $iconUrl = null,
        public ?string $target = null,
        public int $score = 500,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'type'        => $this->type,
            'title'       => $this->title,
            'url'         => $this->url,
            'category'    => $this->category,
            'subtitle'    => $this->subtitle,
            'description' => $this->description,
            'icon'        => $this->icon,
            'icon_url'    => $this->iconUrl,
            'target'      => $this->target,
            'score'       => $this->score,
            'order'       => 10000 - $this->score,
        ];
    }
}
