<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Middlewares;

use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Session\SessionInterface;

final class ForgeWireMiddleware extends Middleware
{
    public function __construct(
        private SessionInterface $session
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        if ($request->hasHeader('X-ForgeWire')) {
            $response = $this->extractLayoutIslands($response);
        } else {
            $content = $response->getContent();
            $componentIdsInResponse = $this->trackComponentsInResponse($content);
            $this->cleanupStaleComponents($componentIdsInResponse);
        }

        return $response;
    }

    /**
     * Track components found in the response HTML
     *
     * @return array<int, string> Component IDs found in the HTML
     */
    private function trackComponentsInResponse(string $html): array
    {
        if (!preg_match_all('/fw:id=["\']([^"\']+)["\']/', $html, $matches)) {
            return [];
        }

        $now = time();
        $componentIds = [];
        foreach ($matches[1] as $componentId) {
            $activeKey = "forgewire:active:{$componentId}";
            $this->session->set($activeKey, $now);
            $componentIds[] = $componentId;
        }

        return $componentIds;
    }

    /**
     * Clean up components that are no longer present on the current page
     * or haven't been seen recently.
     *
     * @param array<int, string> $currentComponentIds Component IDs present in the current HTML response
     */
    private function cleanupStaleComponents(array $currentComponentIds): void
    {
        $allKeys = $this->session->all();
        $now = time();
        $staleThreshold = (int) (config('forge_wire.stale_threshold', 300));

        $componentIds = [];
        foreach ($allKeys as $key => $_) {
            if (preg_match('/^forgewire:active:([^:]+)$/', $key, $matches)) {
                $componentIds[$matches[1]] = true;
                continue;
            }
            if (preg_match('/^forgewire:([^:]+):class$/', $key, $matches)) {
                $componentIds[$matches[1]] = true;
                continue;
            }
            if (preg_match('/^forgewire:([^:]+)$/', $key, $matches)) {
                $componentIds[$matches[1]] = true;
            }
        }

        $currentSet = array_flip($currentComponentIds);

        foreach (array_keys($componentIds) as $componentId) {
            $activeKey = "forgewire:active:{$componentId}";
            $lastSeen = $this->session->get($activeKey);

            $isNotOnCurrentPage = !isset($currentSet[$componentId]);
            $isStaleByTime = $lastSeen === null || ($now - (int) $lastSeen) > $staleThreshold;

            if ($isNotOnCurrentPage || $isStaleByTime) {
                $this->removeComponent($componentId);
            }
        }

        $componentIdSet = array_flip(array_keys($componentIds));

        foreach ($allKeys as $key => $value) {
            if (!str_starts_with($key, 'forgewire:processed:')) {
                continue;
            }

            $timestamp = is_numeric($value) ? (int) $value : null;
            if ($timestamp === null || ($now - $timestamp) > $staleThreshold) {
                $this->session->remove($key);
            }
        }

        foreach ($allKeys as $key => $value) {
            if (preg_match('/^forgewire:shared-group:(.+):components$/', $key, $matches)) {
                $componentClass = $matches[1];
                $components = is_array($value) ? $value : [];
                $components = array_values(array_filter(
                    $components,
                    fn($id) => isset($componentIdSet[$id])
                ));

                if (empty($components)) {
                    $this->session->remove($key);
                    $this->session->remove("forgewire:shared-group:{$componentClass}:initialized");
                    $this->session->remove("forgewire:shared:{$componentClass}");
                } else {
                    $this->session->set($key, $components);
                }
            }
        }

        foreach ($allKeys as $key => $value) {
            if (!str_starts_with($key, 'forge_storage:upload:')) {
                continue;
            }

            $createdAt = null;
            if (is_array($value) && isset($value['created_at']) && is_int($value['created_at'])) {
                $createdAt = $value['created_at'];
            }

            if ($createdAt === null || ($now - $createdAt) > $staleThreshold) {
                $this->session->remove($key);
            }
        }
    }

    /**
     * Remove a component and all its related session data
     */
    private function removeComponent(string $componentId): void
    {
        $allKeys = array_keys($this->session->all());
        $prefix = "forgewire:{$componentId}";

        // Remove all keys related to this component (including :actions:* keys)
        foreach ($allKeys as $key) {
            if (str_starts_with($key, $prefix . ':') || $key === $prefix) {
                $this->session->remove($key);
            }
        }

        // Remove from shared groups
        $this->removeFromSharedGroups($componentId);

        // Remove active tracking
        $this->session->remove("forgewire:active:{$componentId}");
    }

    /**
     * Remove component from shared groups and clean up empty groups
     */
    private function removeFromSharedGroups(string $componentId): void
    {
        $componentClass = $this->session->get("forgewire:{$componentId}:class");

        if (!$componentClass) {
            return;
        }

        // Find shared groups for this component class
        $groupKey = "forgewire:shared-group:{$componentClass}:components";
        if ($this->session->has($groupKey)) {
            $components = $this->session->get($groupKey, []);
            $components = array_filter($components, fn($id) => $id !== $componentId);
            $components = array_values($components);

            if (empty($components)) {
                // Remove entire shared group if empty
                $this->session->remove($groupKey);
                $this->session->remove("forgewire:shared-group:{$componentClass}:initialized");
                $this->session->remove("forgewire:shared:{$componentClass}");
            } else {
                $this->session->set($groupKey, $components);
            }
        }
    }

    private function extractLayoutIslands(Response $response): Response
    {
        $content = $response->getContent();

        $islands = $this->extractIslandsWithFwId($content);

        if (!empty($islands)) {
            $islandsHtml = implode("\n", $islands);
            return new Response($islandsHtml, $response->getStatusCode(), $response->getHeaders());
        }

        return $response;
    }

    private function extractIslandsWithFwId(string $html): array|string
    {
        $islands = [];

        $pattern = '/<([^\s>]+)[^>]*\s+fw:id=["\']([^"\']+)["\'][^>]*(?:\/>|>)/i';

        if (!preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
            return $islands;
        }

        foreach ($matches[0] as $index => $tagMatch) {
            $componentId = $matches[2][$index][0];
            $fullIsland = $this->extractCompleteElement($html, $tagMatch[0], (int)$tagMatch[1]);

            if ($fullIsland !== null) {
                $islands[] = $fullIsland;
            }
        }

        return $islands;
    }

    private function extractCompleteElement(string $html, string $startTag, int $startPos): ?string
    {
        $rootTagName = strtolower(preg_replace('/<([^\s>]+).*/', '$1', $startTag));

        if (substr(trim($startTag), -2) === '/>') {
            return $startTag;
        }

        $stack = [$rootTagName];
        $pos = $startPos + strlen($startTag);
        $len = strlen($html);
        $result = $startTag;

        $selfClosingTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

        while ($pos < $len && !empty($stack)) {
            $nextTag = strpos($html, '<', $pos);
            if ($nextTag === false) {
                $result .= substr($html, $pos);
                break;
            }

            $result .= substr($html, $pos, $nextTag - $pos);
            $pos = $nextTag;

            if ($pos + 1 < $len && $html[$pos + 1] === '/') {
                $closeEnd = strpos($html, '>', $pos);
                if ($closeEnd === false) {
                    break;
                }

                $closeTag = substr($html, $pos, $closeEnd - $pos + 1);
                if (preg_match('/<\/([^\s>]+)/i', $closeTag, $closeMatch)) {
                    $closeTagName = strtolower($closeMatch[1]);
                    if (!empty($stack) && $closeTagName === $stack[count($stack) - 1]) {
                        array_pop($stack);
                        $result .= $closeTag;
                        $pos = $closeEnd + 1;
                        if (empty($stack)) {
                            break;
                        }
                    } else {
                        $result .= $closeTag;
                        $pos = $closeEnd + 1;
                    }
                } else {
                    $result .= $closeTag;
                    $pos = $closeEnd + 1;
                }
            } else {
                $openEnd = strpos($html, '>', $pos);
                if ($openEnd === false) {
                    break;
                }

                $openTag = substr($html, $pos, $openEnd - $pos + 1);
                $isSelfClosing = substr(trim($openTag), -2) === '/>';

                if (!$isSelfClosing && preg_match('/<([^\s>\/]+)/i', $openTag, $openMatch)) {
                    $openTagName = strtolower($openMatch[1]);
                    if (!in_array($openTagName, $selfClosingTags, true)) {
                        $stack[] = $openTagName;
                    }
                }

                $result .= $openTag;
                $pos = $openEnd + 1;
            }
        }

        return empty($stack) ? $result : null;
    }
}
