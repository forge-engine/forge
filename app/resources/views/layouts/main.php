<?php
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
  <!-- <script>
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  </script> -->
</head>
<!--
<script>
  Stark.define('counter', function (root) {
  return {
    count: 0,
    inc() {
      this.count++
    },
    dec() {
      this.count--
    }
  }
})
</script>
<div s-scope="{ name: 'Stark' }">
  <input type="text" s-model="name">
  <p s-text="'Hello ' + name"></p>
</div>

<div s-scope="counter">
  <button s-on="click: dec()">-</button>
  <span s-text="count"></span>
  <button s-on="click: inc()">+</button>
</div> -->
<body class="h-full scroll-smooth">
  <div>
    <?= $content ?>
  </div>
</body>

</html>
