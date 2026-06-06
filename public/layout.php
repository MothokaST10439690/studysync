<?php
// layout.php — Obsidian & Copper theme
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudySync</title>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Fonts: Playfair Display (headings) + DM Sans (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
    /* ── TOKENS ─────────────────────────────────────────────── */
    :root {
        --sidebar:        #161412;
        --sidebar-border: #2a2420;
        --sidebar-hover:  rgba(255,255,255,.05);
        --copper:         #cd853f;
        --copper-dark:    #a8642a;
        --copper-dim:     rgba(205,133,63,.12);
        --copper-border:  rgba(205,133,63,.28);
        --cream:          #faf8f6;
        --card:           #ffffff;
        --border:         #ede8e2;
        --border-soft:    #f3efe9;
        --text-primary:   #1e1810;
        --text-secondary: #6b5d50;
        --text-muted:     #a09080;
        --text-sidebar:   #b0a090;
        /* status */
        --red:            #c0392b;
        --red-bg:         #fdf1f0;
        --red-border:     #f5c6c0;
        --amber:          #b7610a;
        --amber-bg:       #fdf6ec;
        --amber-border:   #f5d9b0;
        --green:          #1a7a40;
        --green-bg:       #edf8f2;
        --green-border:   #b8e8cd;
    }

    /* ── RESET ──────────────────────────────────────────────── */
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    html, body { height:100%; }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--cream);
        color: var(--text-primary);
        font-size: 14px;
        line-height: 1.5;
        display: flex;
        overflow: hidden;
    }

    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #d8cfc5; border-radius: 10px; }

    /* ── SIDEBAR ────────────────────────────────────────────── */
    .ss-sidebar {
        width: 264px;
        min-height: 100vh;
        background: var(--sidebar);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        border-right: 1px solid var(--sidebar-border);
    }

    .ss-logo {
        padding: 22px 20px 18px;
        border-bottom: 1px solid var(--sidebar-border);
    }

    .ss-logo-mark {
        font-family: 'Playfair Display', serif;
        font-size: 21px;
        font-weight: 800;
        color: #fff;
        letter-spacing: -0.3px;
        text-decoration: none;
        display: block;
    }

    .ss-logo-mark span { color: var(--copper); }

    .ss-logo-sub {
        font-size: 10.5px;
        color: rgba(255,255,255,.28);
        margin-top: 2px;
        letter-spacing: 0.3px;
    }

    .ss-nav {
        flex: 1;
        padding: 16px 12px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .ss-nav-label {
        font-size: 9.5px;
        letter-spacing: 1.2px;
        text-transform: uppercase;
        color: rgba(255,255,255,.22);
        padding: 14px 8px 6px;
        font-weight: 500;
    }

    .ss-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 10px;
        border-radius: 6px;
        color: var(--text-sidebar);
        font-size: 13.5px;
        font-weight: 500;
        text-decoration: none;
        transition: background .15s, color .15s;
        border: 1px solid transparent;
    }

    .ss-nav a .bi { font-size: 15px; flex-shrink: 0; }

    .ss-nav a:hover {
        background: var(--sidebar-hover);
        color: rgba(255,255,255,.8);
    }

    .ss-nav a.active {
        background: var(--copper-dim);
        color: var(--copper);
        border-color: var(--copper-border);
    }

    .ss-nav a.active .bi { color: var(--copper); }

    /* ── SIDEBAR BOTTOM ─────────────────────────────────────── */
    .ss-user {
        padding: 14px 16px;
        border-top: 1px solid var(--sidebar-border);
    }

    .ss-user-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .ss-avatar {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: var(--copper-dim);
        border: 1px solid var(--copper-border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        color: var(--copper);
        flex-shrink: 0;
    }

    .ss-user-name { font-size: 13px; font-weight: 600; color: #fff; }
    .ss-user-role { font-size: 11px; color: rgba(255,255,255,.35); margin-top: 1px; }

    .ss-signout {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 8px;
        border-radius: 6px;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.07);
        color: rgba(255,255,255,.38);
        font-size: 12.5px;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
        text-decoration: none;
        transition: background .15s, color .15s;
    }

    .ss-signout:hover {
        background: rgba(255,255,255,.08);
        color: rgba(255,255,255,.65);
    }

    /* ── MAIN WRAPPER ───────────────────────────────────────── */
    .ss-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        min-width: 0;
    }

    /* ── TOPBAR ─────────────────────────────────────────────── */
    .ss-topbar {
        height: 56px;
        background: var(--card);
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 0 26px;
        flex-shrink: 0;
    }

    .ss-topbar-title {
        font-family: 'Playfair Display', serif;
        font-size: 17px;
        font-weight: 700;
        color: var(--text-primary);
        letter-spacing: -0.2px;
    }

    .ss-topbar-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ss-icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: var(--card);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        cursor: pointer;
        transition: background .15s;
        text-decoration: none;
    }

    .ss-icon-btn:hover { background: var(--cream); }

    /* ── CONTENT AREA ───────────────────────────────────────── */
    .ss-content {
        flex: 1;
        overflow-y: auto;
        padding: 26px 28px;
    }

    /* ── SHARED COMPONENTS ──────────────────────────────────── */

    /* Cards */
    .card,
    .card-modern {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
    }

    /* Page hero banner */
    .page-hero {
        background: var(--sidebar);
        border-radius: 18px;
        padding: 28px 30px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
        color: #fff;
    }

    .page-hero::after {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 220px; height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(205,133,63,.18) 0%, transparent 70%);
        pointer-events: none;
    }

    .page-hero-eyebrow {
        font-size: 10px;
        letter-spacing: 1.4px;
        text-transform: uppercase;
        color: var(--copper);
        margin-bottom: 7px;
        font-weight: 500;
    }

    .page-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        font-weight: 800;
        letter-spacing: -0.5px;
        line-height: 1.1;
        margin-bottom: 6px;
    }

    .page-hero-sub { font-size: 13px; color: rgba(255,255,255,.5); }

    /* Stat cards */
    .stat-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 18px 20px;
    }

    .stat-card-label {
        font-size: 12px;
        color: var(--text-secondary);
        font-weight: 500;
        margin-bottom: 8px;
    }

    .stat-card-value {
        font-family: 'Playfair Display', serif;
        font-size: 30px;
        font-weight: 800;
        letter-spacing: -1px;
        line-height: 1;
        color: var(--text-primary);
    }

    .stat-card.copper-tint {
        background: #fdf8f2;
        border-color: #eed8b8;
    }
    .stat-card.copper-tint .stat-card-label { color: #9a6030; }
    .stat-card.copper-tint .stat-card-value { color: var(--copper-dark); }

    .stat-card.red-tint {
        background: var(--red-bg);
        border-color: var(--red-border);
    }
    .stat-card.red-tint .stat-card-label { color: #943228; }
    .stat-card.red-tint .stat-card-value { color: var(--red); }

    /* Buttons */
    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: var(--text-primary);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
        transition: background .15s;
        text-decoration: none;
    }

    .btn-primary:hover { background: #2c211a; }

    .btn-copper {
        background: var(--copper) !important;
        color: #fff !important;
    }

    .btn-copper:hover { background: var(--copper-dark) !important; }

    /* Status pills */
    .pill {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 9px;
        border-radius: 20px;
        display: inline-block;
        white-space: nowrap;
    }

    .pill-done    { background: var(--green-bg);  color: var(--green);  border: 1px solid var(--green-border); }
    .pill-overdue { background: var(--red-bg);    color: var(--red);    border: 1px solid var(--red-border); }
    .pill-pending { background: var(--amber-bg);  color: var(--amber);  border: 1px solid var(--amber-border); }
    .pill-active  { background: var(--green-bg);  color: var(--green);  border: 1px solid var(--green-border); }
    .pill-inactive{ background: var(--red-bg);    color: var(--red);    border: 1px solid var(--red-border); }
    .pill-admin   { background: #fdf3e4; color: #7a4a10; border: 1px solid #f0d0a0; }
    .pill-member  { background: #e8f0fd; color: #1a3a80; border: 1px solid #b8cef5; }
    .pill-private { background: var(--amber-bg);  color: var(--amber);  border: 1px solid var(--amber-border); }
    .pill-public  { background: var(--green-bg);  color: var(--green);  border: 1px solid var(--green-border); }

    /* Form inputs — used in modals + filters */
    .ss-input {
        width: 100%;
        padding: 10px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--card);
        color: var(--text-primary);
        font-family: 'DM Sans', sans-serif;
        font-size: 13.5px;
        outline: none;
        transition: border-color .15s;
    }

    .ss-input:focus { border-color: var(--copper); }

    .ss-label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }

    /* Section heading */
    .section-heading {
        font-family: 'Playfair Display', serif;
        font-size: 20px;
        font-weight: 800;
        letter-spacing: -0.3px;
        color: var(--text-primary);
    }

    /* Panel header (card header row) */
    .panel-header {
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-soft);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .panel-title { font-size: 13.5px; font-weight: 600; }
    .panel-sub   { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }

    .panel-link {
        font-size: 12px;
        font-weight: 600;
        color: var(--copper-dark);
        text-decoration: none;
    }
    .panel-link:hover { color: var(--copper); }

    /* Table shared styles */
    .ss-table { width: 100%; border-collapse: collapse; }
    .ss-table thead { background: var(--cream); }
    .ss-table th {
        padding: 12px 20px;
        text-align: left;
        font-size: 10.5px;
        font-weight: 600;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: var(--text-muted);
        border-bottom: 1px solid var(--border);
    }

    .ss-table td {
        padding: 13px 20px;
        font-size: 13.5px;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-soft);
    }

    .ss-table tbody tr:last-child td { border-bottom: none; }
    .ss-table tbody tr:hover td { background: var(--cream); }

    /* Modal backdrop */
    .ss-modal-bg {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.55);
        backdrop-filter: blur(3px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 500;
        padding: 20px;
    }

    .ss-modal {
        background: var(--card);
        border-radius: 18px;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 20px 60px rgba(0,0,0,.18);
        overflow: hidden;
    }

    .ss-modal-head {
        padding: 22px 24px 18px;
        border-bottom: 1px solid var(--border-soft);
    }

    .ss-modal-title {
        font-family: 'Playfair Display', serif;
        font-size: 20px;
        font-weight: 800;
    }

    .ss-modal-sub { font-size: 12.5px; color: var(--text-muted); margin-top: 3px; }
    .ss-modal-body { padding: 20px 24px; }
    .ss-modal-footer {
        padding: 16px 24px;
        border-top: 1px solid var(--border-soft);
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-ghost {
        padding: 8px 16px;
        border-radius: 8px;
        background: var(--cream);
        border: 1px solid var(--border);
        color: var(--text-secondary);
        font-size: 13px;
        font-family: 'DM Sans', sans-serif;
        cursor: pointer;
        transition: background .15s;
    }
    .btn-ghost:hover { background: #ede8e2; }

    /* Empty state */
    .empty-state {
        padding: 52px 20px;
        text-align: center;
        color: var(--text-muted);
    }
    .empty-state p { font-size: 13px; margin-top: 8px; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="ss-sidebar">

        <div class="ss-logo">
            <a href="dashboard.php" class="ss-logo-mark">Study<span>Sync</span></a>
            <div class="ss-logo-sub">Smart Student Collaboration</div>
        </div>

        <nav class="ss-nav">
            <div class="ss-nav-label">Main</div>
            <a href="dashboard.php" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <a href="groups.php" class="<?= in_array($current_page, ['groups','group']) ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i> Study Groups
            </a>
            <a href="tasks.php" class="<?= $current_page === 'tasks' ? 'active' : '' ?>">
                <i class="bi bi-check2-square"></i> Tasks
            </a>
            <a href="files.php" class="<?= $current_page === 'files' ? 'active' : '' ?>">
                <i class="bi bi-folder-fill"></i> Files
            </a>
            <a href="calendar.php" class="<?= $current_page === 'calendar' ? 'active' : '' ?>">
                <i class="bi bi-calendar-event-fill"></i> Calendar
            </a>

            <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
            <div class="ss-nav-label" style="margin-top:8px;">Administration</div>
            <a href="admin.php" class="<?= $current_page === 'admin' ? 'active' : '' ?>">
                <i class="bi bi-shield-lock-fill"></i> Admin Panel
            </a>
            <?php endif; ?>
        </nav>

        <div class="ss-user">
            <div class="ss-user-row">
                <div class="ss-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <div class="ss-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></div>
                    <div class="ss-user-role"><?= ucfirst($_SESSION['user_role'] ?? '') ?></div>
                </div>
            </div>
            <a href="logout.php" class="ss-signout">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </div>

    </aside>

    <!-- MAIN -->
    <div class="ss-main">

        <!-- Topbar -->
        <header class="ss-topbar">
            <div class="ss-topbar-title"><?= ucwords(str_replace('-', ' ', $current_page)) ?></div>
            <div class="ss-topbar-right">
                <a href="logout.php" class="ss-icon-btn" title="Sign out">
                    <i class="bi bi-box-arrow-right" style="font-size:14px;"></i>
                </a>
            </div>
        </header>

        <!-- Page content -->
        <div class="ss-content">
            <?= $page_content ?? '' ?>
        </div>

    </div>

</body>
</html>