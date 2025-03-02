<?php

namespace Forge\Core\Routing;

use Forge\Http\Request;
use Forge\Http\Response;

class CoreRouter
{
    public function handleRequest(Request $request): Response
    {
        $uri = $request->getUri();

        if ($uri === '/' || $uri === "") {
            return $this->renderWelcomePage();
        }
        return (new Response())->setContent('Not Found (Core Router)')->setStatusCode(404);
    }

    private function renderWelcomePage(): Response
    {
        $welcomePagePath = __DIR__ . '/views/welcome.php';

        include_once $welcomePagePath;

        if (!file_exists($welcomePagePath)) {
            error_log("Welcome page view not found at: " . $welcomePagePath);
            return (new Response())->setContent('Error: Welcome page view not found!')->setStatusCode(500);
        }

        $welcomeHtml = file_get_contents($welcomePagePath);
        return (new Response())->setContent($welcomeHtml)->setHeader('Content-Type', 'text/html');
    }
}