<?php
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="description" content=""/>
    <meta name="author" content=""/>
    <meta name="viewport"
          content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width"/>
    <title><?= section('title') ?? 'Default Title' ?></title>

    <style>
        body {
            font-family: system-ui, sans-serif;
            line-height: 1.5;
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
    </style>
</head>
<body>
<div class="container">
    @content()

    <?php echo \Forge\Core\View\Component::render('footer', ["year" => $year] ?? []); ?>
</div>
</body>
</html>
