<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudySync — Offline</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'DM Sans', sans-serif;
    background: #161412;
    color: #fff;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 24px;
    text-align: center;
}

.icon {
    width: 80px;
    height: 80px;
    border-radius: 24px;
    background: rgba(205,133,63,.12);
    border: 1px solid rgba(205,133,63,.28);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 28px;
    font-size: 36px;
}

h1 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin-bottom: 12px;
    color: #fff;
}

p {
    font-size: 15px;
    color: rgba(255,255,255,.5);
    max-width: 320px;
    line-height: 1.6;
    margin-bottom: 32px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 10px;
    background: #cd853f;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s;
}

.btn:hover { background: #a8642a; }

.wordmark {
    position: absolute;
    top: 28px;
    left: 28px;
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 800;
    color: #fff;
    text-decoration: none;
}

.wordmark span { color: #cd853f; }
</style>
</head>
<body>

<a href="/" class="wordmark">Study<span>Sync</span></a>

<div class="icon">📡</div>

<h1>You're offline</h1>
<p>It looks like you've lost your internet connection. Check your connection and try again.</p>

<button class="btn" onclick="window.location.reload()">
    ↻ Try Again
</button>

</body>
</html>
