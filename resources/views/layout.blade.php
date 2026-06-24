<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; margin: 0; background: #f6f7f9; color: #17202a; }
        main { max-width: 1180px; margin: 0 auto; padding: 32px 20px; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
        h1 { font-size: 26px; margin: 0; }
        form.filters { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 16px; }
        input[type="search"] { min-width: 280px; padding: 9px 10px; border: 1px solid #c9d1d9; border-radius: 6px; }
        button, .button { border: 0; border-radius: 6px; padding: 9px 12px; background: #155e75; color: #fff; cursor: pointer; text-decoration: none; }
        .secondary { background: #475569; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #dce2e8; }
        th, td { padding: 11px 12px; text-align: left; border-bottom: 1px solid #e5eaf0; vertical-align: top; }
        th { background: #edf2f7; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; }
        details { margin-top: 8px; }
        summary { cursor: pointer; color: #155e75; font-weight: 600; }
        .status { margin-bottom: 14px; padding: 10px 12px; border-radius: 6px; background: #dcfce7; color: #14532d; }
        .errors { margin-bottom: 14px; padding: 10px 12px; border-radius: 6px; background: #fee2e2; color: #7f1d1d; }
        .muted { color: #64748b; }
        .candidate { padding: 10px 0; border-top: 1px solid #e5eaf0; }
    </style>
</head>
<body>
<main>
    @yield('content')
</main>
</body>
</html>
