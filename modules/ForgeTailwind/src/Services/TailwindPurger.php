<?php

declare(strict_types=1);

namespace App\Modules\ForgeTailwind\Services;

use App\Modules\ForgeTailwind\Traits\CssMinifierTrait;
use App\Modules\ForgeTailwind\Traits\TailwindTrait;
use Forge\CLI\Traits\OutputHelper;
use Forge\Traits\FileHelper;

class TailwindPurger
{
    use CssMinifierTrait;
    use OutputHelper;
    use FileHelper;
    use TailwindTrait;

    private const CACHE_FILE = BASE_PATH . '/storage/framework/cache/tailwind.json';

    /** @var array<string, mixed> */
    private array $contentGlobs;

    /** @var array<string, bool> */
    private array $whitelist;

    /** @var array<string, string> */
    private array $tokens;

    /** @var array<string, int> file => mtime */
    private array $fileCache = [];

    public function __construct(array $globs = [], array $whitelist = [], array $tokens = [])
    {
        $this->contentGlobs = $globs ?: [
            BASE_PATH . '/app/resources/views/**/*.php',
        ];

        $defaultWhitelist = ['html', 'body', ':root', '*', '::before', '::after',
            '@keyframes', '@font-face', 'button', 'dark',
            'hover', 'focus', 'focus-visible', 'active', 'disabled'];
        $this->whitelist = array_flip(array_map([$this, 'normalizeClass'], array_merge($defaultWhitelist, $whitelist)));
        $this->tokens = $tokens;

        $this->loadFileCache();
    }

    private function extractClasses(string $content): array
    {
        $used = [];
        preg_match_all('~class(?:Name)?\s*=\s*(["\'])(.*?)\1~s', $content, $matches);
        if (!empty($matches[2])) {
            foreach ($matches[2] as $chunk) {
                foreach (preg_split('~\s+~', $chunk) as $cls) {
                    $cls = trim($cls);
                    if ($cls === '') {
                        continue;
                    }
                    $used[$this->normalizeClass($cls)] = true;
                }
            }
        }
        return $used;
    }

    private function collectClassesFromFile(string $file): array
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }

        $classes = [];
        preg_match_all('~class(?:Name)?\s*=\s*(["\'])(.*?)\1~s', $content, $matches);

        foreach ($matches[2] as $chunk) {
            foreach (preg_split('~\s+~', $chunk) as $cls) {
                $cls = trim($cls);
                if ($cls === '') {
                    continue;
                }
                $classes[$this->normalizeClass($cls)] = true;
            }
        }

        return $classes;
    }

    private function loadFileCache(): void
    {
        if (!is_file(self::CACHE_FILE)) {
            return;
        }
        $data = json_decode(file_get_contents(self::CACHE_FILE), true);
        $this->fileCache = $data['mtimes'] ?? [];
    }

    private function loadCache(): array
    {
        if (!is_file(self::CACHE_FILE)) {
            return [];
        }
        return json_decode(file_get_contents(self::CACHE_FILE), true);
    }

    private function saveCache(string $hash, array $used, array $mtimes, string $css): void
    {
        @mkdir(dirname(self::CACHE_FILE), 0755, true);
        file_put_contents(self::CACHE_FILE, json_encode([
            'hash' => $hash,
            'files' => $used,
            'mtimes' => $mtimes,
            'css' => $css
        ], JSON_THROW_ON_ERROR));
    }
}
