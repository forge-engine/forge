<?php
/** @var array $files */
/** @var array $buckets */
/**
 * @var MyApp\DataTransferObjects\CategoryDTO|null $category
 * @var array<MyApp\DataTransferObjects\CategoryDTO> $allCategories
 * @var array<MyApp\DataTransferObjects\SectionDTO> $sections
 */
?>
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

    <pre>
    <?php print_r($category); ?>
    </pre>
    <pre>
    <?php print_r($sections[0]->content); ?>
    </pre>
</div>