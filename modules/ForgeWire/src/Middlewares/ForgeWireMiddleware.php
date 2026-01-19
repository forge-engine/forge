<?php

declare(strict_types=1);

namespace App\Modules\ForgeWire\Middlewares;

use Forge\Core\Http\Middleware;
use Forge\Core\Http\Request;
use Forge\Core\Http\Response;
use Forge\Core\Session\SessionInterface;
use Forge\Traits\WireHelper;

final class ForgeWireMiddleware extends Middleware
{
  use WireHelper;

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
