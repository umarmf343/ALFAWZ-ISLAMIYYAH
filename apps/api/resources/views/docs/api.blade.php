<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AlFawz API Reference</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #f9fafb;
        }

        header {
            background-color: #065f46;
            color: #fff;
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            margin: 0;
            font-size: 1.75rem;
        }

        header p {
            margin: 0.25rem 0 0;
            opacity: 0.85;
        }

        redoc {
            height: calc(100% - 96px);
        }
    </style>
</head>
<body>
<header>
    <h1>AlFawz Qur'an Institute API</h1>
    <p>Interactive reference powered by Redoc.</p>
</header>
<redoc spec-url="{{ route('api.docs.schema') }}" hide-download-button></redoc>
<script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
</body>
</html>
