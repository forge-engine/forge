<?php
/** @var array $files */
/** @var array $buckets */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storage Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 800px;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }

        form {
            margin-bottom: 20px;
        }

        .actions a {
            margin-right: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Storage Dashboard</h1>

    <h2>Buckets</h2>
    <form action="/dashboard/create-bucket" method="POST">
        <label for="bucket">
            <input type="text" name="bucket_name" placeholder="Bucket Name" required id="bucket">
        </label>
        <button type="submit">Create Bucket</button>
    </form>

    <h2>Files</h2>
    <form action="/dashboard/upload" method="POST" enctype="multipart/form-data">
        <label for="bucket_name">Select Bucket:</label>
        <select name="bucket_name" id="bucket_name" required>
            <?php foreach ($buckets as $bucket): ?>
                <option value="<?= htmlspecialchars($bucket['name']) ?>">
                    <?= htmlspecialchars($bucket['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="file" name="image" required>
        <button type="submit">Upload File</button>
    </form>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Bucket</th>
            <th>Path</th>
            <th>Size</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($files as $file): ?>
            <tr>
                <td><?= htmlspecialchars($file['id']) ?></td>
                <td><?= htmlspecialchars($file['bucket']) ?></td>
                <td><?= htmlspecialchars($file['path']) ?></td>
                <td><?= htmlspecialchars($file['size']) ?></td>
                <td class="actions">
                    <a href="/dashboard/url?bucket=<?= urlencode($file['bucket']) ?>&path=<?= urlencode($file['path']) ?>">Get
                        URL</a>
                    <a href="/dashboard/temporary-url?bucket=<?= urlencode($file['bucket']) ?>&path=<?= urlencode($file['path']) ?>">Get
                        Temporary URL</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
