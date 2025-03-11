<?php

/**
 * Access Denied
 *
 * This file is placed in the root of the Forge framework to prevent
 * direct access to the framework's core files.
 *
 * All requests should be routed through the `public/index.php` file.
 *
 */

http_response_code(403);
echo "Access denied.";
exit();
