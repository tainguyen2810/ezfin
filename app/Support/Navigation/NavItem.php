<?php

namespace App\Support\Navigation;

final readonly class NavItem
{
    public function __construct(
        public string $label,
        public string $route,
        public int $order = 100,
        public ?string $feature = null,
    ) {}

    public function withFeature(string $feature): self
    {
        return new self($this->label, $this->route, $this->order, $feature);
    }
}
