<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Homework App</title>
    @vite('resources/js/app.js') <!-- Ensure Vite loads the compiled app.js -->
</head>
<body>
    <div id="app"></div> <!-- Vue app will be mounted here -->
</body>
</html>
