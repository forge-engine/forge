<?php

/**
 * Access Denied
 *
 * This file is placed in the root of the Forge framework to prevent
 * direct access to the framework's core files.
 *
 * All requests should be routed through the `public/index.php` file.
 *
 * @framework Forge
 * @license MIT
 * @github acidlake
 * @author Jeremias
 * @security Root Protection
 * @version 1.0.0
 * @copyright 2025
 */

http_response_code(403);
echo "Access denied.";
exit();