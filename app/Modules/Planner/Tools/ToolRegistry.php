<?php

namespace App\Modules\Planner\Tools;

/** Danh sách công cụ đã triển khai. Chỉ công cụ có trong đây mới có trang và xuất hiện trên /cong-cu. */
final class ToolRegistry
{
    /** @var array<string, Tool> */
    private array $tools = [];

    public function register(Tool $tool): void
    {
        $this->tools[$tool->slug()] = $tool;
    }

    /** @return array<string, Tool> */
    public function all(): array
    {
        return $this->tools;
    }

    public function find(string $slug): ?Tool
    {
        return $this->tools[$slug] ?? null;
    }
}
