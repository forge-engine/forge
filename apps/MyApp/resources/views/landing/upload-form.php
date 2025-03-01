<!-- resources/views/upload.form.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Image</title>
</head>
<body>
<h1>Upload Image</h1>
<form action="/upload" method="POST" enctype="multipart/form-data">
    <label for="image">Choose an image to upload:</label>
    <input type="file" name="image" id="image" accept="image/*" required>
    <button type="submit">Upload</button>
</form>
</body>
</html>
