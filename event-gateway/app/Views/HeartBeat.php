<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Gateway Health</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f6f8fb; margin: 0; padding: 32px; }
    h1 { margin-bottom: 12px; }
    table { border-collapse: collapse; width: 420px; background: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    th, td { padding: 12px 14px; border-bottom: 1px solid #e9eef5; text-align: left; }
    th { background: #f0f4fa; font-weight: 600; }
    tr:last-child td { border-bottom: none; }
    .ok { color: #0a8f4d; font-weight: 600; }
  </style>
</head>
<body>
  <h1>Gateway Health</h1>
  <table>
    <tr><th>Status</th><td class="ok">200 OK</td></tr>
    <tr><th>Message</th><td><?= esc($msg) ?></td></tr>
    <tr><th>Server Time</th><td><?= esc($time) ?></td></tr>
    <tr><th>Env</th><td><?= esc($env) ?></td></tr>
  </table>
</body>
</html>
