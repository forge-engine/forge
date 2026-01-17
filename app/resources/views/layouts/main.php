<?php

use Forge\Core\Helpers\ModuleResources;

/**
 * @var string $title
 * @var string $content
 * @var int $counter
 */

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="description" content="" />
  <meta name="author" content="" />
  <meta name="viewport"
    content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width" />
  <link rel="stylesheet" href="/assets/css/app.css" />
  <title><?= $title ?? "Default Title" ?></title>

  <?= raw(csrf_meta()) ?>
  <script>
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  </script>
</head>

<body class="h-full scroll-smooth">
  <div>
    <?= $content ?>
  </div>
</body>

</html>
