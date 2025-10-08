<?php

declare(strict_types=1);

namespace App\Modules\ForgeTailwind\Traits;

trait TailwindTrait
{
    public function purge(string $cssPath, string $configPath, string $extraCss = ''): string
    {
        $currentFileHash = $this->hashWatchedFiles($this->contentGlobs);

        $cacheData = $this->loadCache();
        $used = [];

        foreach ($this->contentGlobs as $pattern) {
            foreach ($this->recursiveGlob($pattern) as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $mtime = filemtime($file);
                $cachedMtime = $this->fileCache[$file] ?? 0;

                if ($mtime === $cachedMtime && isset($cacheData['files'][$file])) {
                    $used += $cacheData['files'][$file];
                } else {
                    $classes = $this->collectClassesFromFile($file);
                    $used += $classes;
                    $this->fileCache[$file] = $mtime;
                }
            }
        }

        $this->log("Found " . count($used) . " unique classes in HTML", 'TailwindPurger');

        $css = file_get_contents($cssPath);
        $clean = $this->filterCss($css, $used);
        $clean = $this->applyTokens($clean, $this->tokens);
        $extra = $this->applyTokens($extraCss, $this->tokens);
        $clean .= $extra;

        $clean = $this->removeDuplicateRules($clean);
        $min = $this->minify($clean);

        $this->saveCache($currentFileHash, $used, $this->fileCache, $min);
        return $min;
    }

    private function contentHash(string $cssPath, string $configPath): string
    {
        $h = hash_init('xxh3');
        hash_update_file($h, $cssPath);
        if (is_file($configPath)) {
            hash_update_file($h, $configPath);
        }
        foreach ($this->contentGlobs as $pattern) {
            foreach ($this->recursiveGlob($pattern) as $f) {
                hash_update($h, $f . filemtime($f));
            }
        }
        return hash_final($h);
    }

    private function normalizeClass(string $raw): string
    {
        return preg_replace('/([:\\[\\]\\.#])/', '\\\\$1', $raw);
    }

    private function collectUsedClasses(): array
    {
        $used = [];
        foreach ($this->contentGlobs as $pattern) {
            $files = $this->recursiveGlob($pattern);

            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $content = file_get_contents($file);
                if ($content === false) {
                    continue;
                }

                $matches = [];
                preg_match_all('~class(?:Name)?\s*=\s*(["\'])(.*?)\1~s', $content, $matches);

                if (!empty($matches[2])) {
                    foreach ($matches[2] as $chunk) {
                        foreach (preg_split('~\s+~', $chunk) as $cls) {
                            $cls = trim($cls);
                            if ($cls === '') {
                                continue;
                            }

                            $norm = $this->normalizeClass($cls);
                            $used[$norm] = true;
                        }
                    }
                }
            }
        }

        return $used;
    }

    private function shouldKeepRule(string $rule, array $used, array $whitelist)
    {
        $rule = preg_replace('~/\*.*?\*/~s', '', $rule);

        if (preg_match('/^(html|body|:root|\*|::before|::after)/', trim($rule))) {
            return $rule;
        }

        if (preg_match('/\.dark:/', $rule)) {
            if (preg_match_all('/\.dark:([a-zA-Z0-9_\-\\\\:\\[\\]\\.\\#]+)/', $rule, $matches)) {
                foreach ($matches[1] as $cls) {
                    $normalizedClass = $this->normalizeClass($cls);
                    if (isset($used[$normalizedClass]) || isset($whitelist[$normalizedClass])) {
                        return $rule;
                    }
                }
            }
            return false;
        }

        if (preg_match('/^@(keyframes|font-face|import|charset|namespace)/', $rule)) {
            return $rule;
        }

        if (preg_match('/^@media/', $rule)) {
            if (preg_match('/^(@[^{]+){(.*)}$/s', $rule, $m)) {
                $cleanBody = $this->filterCss($m[2], $used);
                if ($cleanBody !== '') {
                    return $m[1] . '{' . $cleanBody . '}';
                }
            }
            return false;
        }

        if (preg_match_all('/\.([a-zA-Z0-9_\-\\\\:\\[\\]\\.\\#]+)/', $rule, $matches)) {
            $keepRule = false;
            foreach ($matches[1] as $cls) {
                if (preg_match('/(sm|md|lg|xl|2xl|3xl|4xl|5xl|6xl):/', $cls)) {
                    if (preg_match('/(?:sm|md|lg|xl|2xl|3xl|4xl|5xl|6xl):(.+)/', $cls, $baseMatch)) {
                        $baseClass = $this->normalizeClass($baseMatch[1]);
                        if (isset($used[$baseClass]) || isset($whitelist[$baseClass])) {
                            $keepRule = true;
                            break;
                        }
                    }
                }

                $normalizedClass = $this->normalizeClass($cls);
                if (isset($used[$normalizedClass]) || isset($whitelist[$normalizedClass])) {
                    $keepRule = true;
                    break;
                }
            }

            return $keepRule ? $rule : false;
        }

        if (preg_match('/^@/', $rule)) {
            if (preg_match('/^(@[^{]+){(.*)}$/s', $rule, $m)) {
                $cleanBody = $this->filterCss($m[2], $used);
                if ($cleanBody !== '') {
                    return $m[1] . '{' . $cleanBody . '}';
                }
            }
            return false;
        }

        return false;
    }

    private function applyTokens(string $css, array $tokens = []): string
    {
        if (empty($tokens)) {
            return $css;
        }

        $replacements = [];
        foreach ($tokens as $key => $value) {
            $replacements["{{{$key}}}"] = $value;
        }

        return str_replace(array_keys($replacements), array_values($replacements), $css);
    }

    private function filterCss(string $css, array $used): string
    {
        $whitelist = $this->whitelist;
        $tokens = preg_split('/({|})/', $css, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        $buffer = '';
        $depth = 0;

        foreach ($tokens as $token) {
            if ($token === '{') {
                $depth++;
                $buffer .= $token;
            } elseif ($token === '}') {
                $depth--;
                $buffer .= $token;

                if ($depth === 0) {
                    $kept = $this->shouldKeepRule($buffer, $used, $whitelist);
                    if ($kept !== false) {
                        $out .= $kept;
                    }
                    $buffer = '';
                }
            } else {
                $buffer .= $token;
            }
        }
        return $out;
    }

    private function hashWatchedFiles(array $globs): string
    {
        $h = hash_init('xxh3');

        foreach ($globs as $pattern) {
            foreach ($this->recursiveGlob($pattern) as $file) {
                if (!is_file($file)) {
                    continue;
                }

                hash_update($h, $file . filemtime($file));
            }
        }

        return hash_final($h);
    }

    private function removeDuplicateRules(string $css): string
    {
        preg_match_all('/([^{]+)({[^}]*})/', $css, $matches, PREG_SET_ORDER);

        $uniqueRules = [];
        $result = '';

        foreach ($matches as $match) {
            $selector = trim($match[1]);
            $declaration = $match[2];

            if (strpos($selector, '@media') === 0) {
                $result .= $match[0];
                continue;
            }

            $key = $selector . '|' . $declaration;

            if (!isset($uniqueRules[$key])) {
                $uniqueRules[$key] = true;
                $result .= $match[0];
            }
        }

        preg_match_all('/@(keyframes|font-face)[^{]*{[^}]*}/', $css, $atRuleMatches);
        if (!empty($atRuleMatches[0])) {
            foreach ($atRuleMatches[0] as $atRule) {
                if (strpos($result, $atRule) === false) {
                    $result .= $atRule;
                }
            }
        }

        return $result;
    }

    private function handleTailwindSource(string $context): bool
    {
        $inputCss = $this->cfg['input_css'];
        $autoDownload = $this->cfg['auto_download'];
        $sourceUrl = $this->cfg['source_url'];
        $offlineFallback = $this->cfg['offline_fallback'];
        $fallbackPath = $this->cfg['fallback_path'];
        $verifyIntegrity = $this->cfg['verify_integrity'];
        $ver = $this->cfg['version'];

        if ($autoDownload && (!is_file($inputCss) || filesize($inputCss) < 200)) {
            $this->info("Downloading Tailwind v{$ver} from {$sourceUrl}...", $context);
            $contents = @file_get_contents($sourceUrl);

            if (!$contents) {
                if ($offlineFallback && is_file($fallbackPath)) {
                    $this->warning("Online fetch failed → using local fallback.", $context);
                    $contents = file_get_contents($fallbackPath);
                } else {
                    $this->error("Cannot download Tailwind and no fallback available.", $context);
                    return false;
                }
            }

            if ($verifyIntegrity) {
                $hash = hash('sha256', $contents);
                $this->info("Downloaded SHA256: {$hash}", $context);
            }

            @mkdir(dirname($inputCss), 0755, true);
            file_put_contents($inputCss, $contents);
            $this->info("Tailwind source saved → {$inputCss}", $context);
            return true;
        }

        if (!is_file($inputCss) && $offlineFallback && is_file($fallbackPath)) {
            $this->warning("Missing Tailwind input file → copying fallback build.", $context);
            @mkdir(dirname($inputCss), 0755, true);
            copy($fallbackPath, $inputCss);
            return true;
        }

        if (is_file($inputCss)) {
            return true;
        }

        return false;
    }
}
