<?php

namespace Forge\Modules\ForgeViewEngine;

class ViewFinder
{
    /**
     * @param array<int,mixed> $paths
     */
    public function __construct(
        private array  $paths,
        private string $basePath
    )
    {
    }

    public function find(string $view): ?string
    {
        foreach ($this->getSearchPaths() as $path) {
            $parts = explode('.', $view);
            if (count($parts) > 1) {
                $folderPath = $path . '/' . implode('/', array_slice($parts, 0, -1));
                $fileName = end($parts);
                $fullPath = "{$folderPath}/{$fileName}.php";
            } else {
                $fullPath = "{$path}/{$view}.php";
            }
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        return null;
    }

    private function getSearchPaths(): array
    {
        return array_map(fn($p) => $this->basePath . '/' . $p, $this->paths);
    }
}
