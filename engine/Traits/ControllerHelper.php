<?php
declare(strict_types=1);

namespace Forge\Traits;

use Forge\Core\DI\Container;
use Forge\Core\Http\Response;
use Forge\Core\View\View;

trait ControllerHelper
{
	/**
	 * Helper method to return a JSON response
	 *
	 * @param array $data
	 * @param int $statusCode
	 *
	 * @return Response
	 */
	protected function jsonResponse(array $data, int $statusCode = 200): Response
	{
		$jsonData = json_encode($data);
		return (new Response($jsonData, $statusCode))->setHeader('Content-Type', 'application/json');
	}
	
	/**
	 * Render a view file.
	 *
	 * @param string $view The view file path (relative to views directory).
	 * @param array<string, mixed> $data Data to pass to the view.
	 * @return Response Returns the rendered view content.
	 */
	protected function view(string $view, array $data = []): Response
	{
		return (new View(Container::getInstance()))->render($view, $data);
	}
}