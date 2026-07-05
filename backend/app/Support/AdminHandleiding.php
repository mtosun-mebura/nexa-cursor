<?php

namespace App\Support;

class AdminHandleiding
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function pages(): array
    {
        $pages = config('admin-handleiding.pages', []);

        return collect($pages)
            ->sortBy(fn (array $page) => $page['order'] ?? 999)
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function page(string $slug): ?array
    {
        $page = config("admin-handleiding.pages.{$slug}");

        return is_array($page) ? $page : null;
    }
}
