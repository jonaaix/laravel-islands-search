<?php

declare(strict_types=1);

namespace Aaix\LaravelIslandsSearch\Support;

use Composer\InstalledVersions;

class HeroiconSet
{
    /**
     * @param  list<string>  $names
     * @return array<string, array{box: string, stroke: bool, html: string}>
     */
    public function build(array $names): array
    {
        return collect($names)
            ->unique()
            ->mapWithKeys(fn (string $name): array => [$name => $this->definition($name)])
            ->filter()
            ->all();
    }

    /**
     * @return array{box: string, stroke: bool, html: string}|null
     */
    private function definition(string $name): ?array
    {
        $path = InstalledVersions::getInstallPath('blade-ui-kit/blade-heroicons')."/resources/svg/{$name}.svg";

        if (! is_file($path)) {
            return null;
        }

        $svg = (string) file_get_contents($path);

        preg_match('/viewBox="([^"]+)"/', $svg, $viewBox);
        preg_match('/<svg[^>]*>(.*)<\/svg>/s', $svg, $inner);

        return [
            'box' => $viewBox[1] ?? '0 0 24 24',
            'stroke' => str_contains($svg, 'stroke="currentColor"'),
            'html' => trim($inner[1] ?? ''),
        ];
    }
}
