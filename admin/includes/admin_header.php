<?php
/**
 * MULTICAR — Admin Header / Layout Template
 * Variables expected: $adminTitle (string)
 */
if (!defined('ADMIN_LOADED')) { http_response_code(403); exit; }
$adminTitle = $adminTitle ?? 'Panel de Administración';
$flashMessages = getFlash();
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$unreadLeads = db()->query("SELECT COUNT(*) FROM leads WHERE read_status = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminTitle) ?> — MULTICAR Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        /* ===== RESET & BASE ===== */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --navy: #1B3A5C;
            --navy-dark: #142d48;
            --navy-light: #2a5280;
            --gold: #C8963E;
            --gold-light: #dbb06a;
            --gold-dark: #a87a2e;
            --bg: #f4f6f8;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --green: #22c55e;
            --green-bg: #dcfce7;
            --yellow: #f59e0b;
            --yellow-bg: #fef3c7;
            --red: #ef4444;
            --red-bg: #fee2e2;
            --blue: #3b82f6;
            --blue-bg: #dbeafe;
            --sidebar-w: 260px;
            --topbar-h: 60px;
            --radius: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
            --shadow-lg: 0 4px 12px rgba(0,0,0,0.1);
        }
        html { font-size: 15px; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg);
            color: var(--gray-800);
            line-height: 1.5;
            min-height: 100vh;
        }
        a { color: var(--navy); text-decoration: none; }
        a:hover { color: var(--gold); }

        /* ===== SIDEBAR ===== */
        .admin-sidebar {
            position: fixed;
            left: 0; top: 0;
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--navy);
            color: #fff;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }
        .sidebar-logo {
            padding: 20px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-logo .logo-icon {
            width: 40px; height: 40px;
            background: var(--gold);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 18px;
            color: var(--navy-dark);
            flex-shrink: 0;
        }
        .sidebar-logo .logo-text {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .sidebar-logo .logo-sub {
            font-size: 10px;
            color: var(--gold-light);
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .sidebar-nav {
            flex: 1;
            padding: 16px 0;
            overflow-y: auto;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 24px;
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar-nav a:hover {
            color: #fff;
            background: rgba(255,255,255,0.06);
        }
        .sidebar-nav a.active {
            color: var(--gold-light);
            background: rgba(200,150,62,0.1);
            border-left-color: var(--gold);
        }
        .sidebar-nav a svg {
            width: 20px; height: 20px;
            flex-shrink: 0;
            opacity: 0.85;
        }
        .sidebar-nav a.active svg { opacity: 1; }
        .sidebar-nav .nav-badge {
            margin-left: auto;
            background: var(--red);
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }
        .sidebar-nav .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.08);
            margin: 12px 24px;
        }
        .sidebar-footer {
            padding: 16px 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 12px;
            color: rgba(255,255,255,0.4);
        }

        /* ===== TOPBAR ===== */
        .admin-topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--topbar-h);
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            z-index: 999;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .topbar-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--navy);
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: var(--gray-600);
        }
        .topbar-user .user-avatar {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: var(--navy);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .topbar-user .user-name { font-weight: 600; color: var(--gray-800); }
        .topbar-user .user-role { font-size: 12px; color: var(--gray-400); text-transform: capitalize; }
        .btn-view-site {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 13px;
            color: var(--gray-600);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            transition: all 0.2s;
        }
        .btn-view-site:hover {
            color: var(--navy);
            border-color: var(--navy);
        }
        .btn-view-site svg { width: 16px; height: 16px; }
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            font-size: 13px;
            color: var(--red);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            background: transparent;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-logout:hover { background: var(--red-bg); border-color: var(--red); }

        /* Hamburger for mobile */
        .hamburger {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: var(--gray-700);
        }
        .hamburger svg { width: 24px; height: 24px; }

        /* ===== MAIN CONTENT ===== */
        .admin-main {
            margin-left: var(--sidebar-w);
            margin-top: var(--topbar-h);
            padding: 28px;
            min-height: calc(100vh - var(--topbar-h));
        }

        /* ===== FLASH MESSAGES ===== */
        .flash-container { margin-bottom: 20px; }
        .flash {
            padding: 14px 20px;
            border-radius: var(--radius);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            animation: flashIn 0.3s ease;
        }
        .flash-success { background: var(--green-bg); color: #166534; border: 1px solid #bbf7d0; }
        .flash-error { background: var(--red-bg); color: #991b1b; border: 1px solid #fecaca; }
        .flash-warning { background: var(--yellow-bg); color: #92400e; border: 1px solid #fde68a; }
        .flash-info { background: var(--blue-bg); color: #1e40af; border: 1px solid #bfdbfe; }
        .flash svg { width: 20px; height: 20px; flex-shrink: 0; }
        @keyframes flashIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }

        /* ===== CARDS ===== */
        .card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
        }
        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .card-header h2 {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
        }
        .card-body { padding: 24px; }
        .card-footer {
            padding: 14px 24px;
            border-top: 1px solid var(--gray-100);
            background: var(--gray-50);
            border-radius: 0 0 var(--radius) var(--radius);
        }

        /* ===== STAT CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--gray-200);
            padding: 22px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .stat-icon svg { width: 24px; height: 24px; }
        .stat-icon.blue { background: var(--blue-bg); color: var(--blue); }
        .stat-icon.green { background: var(--green-bg); color: var(--green); }
        .stat-icon.yellow { background: var(--yellow-bg); color: var(--yellow); }
        .stat-icon.red { background: var(--red-bg); color: var(--red); }
        .stat-icon.navy { background: rgba(27,58,92,0.1); color: var(--navy); }
        .stat-info h3 {
            font-size: 28px;
            font-weight: 800;
            color: var(--navy);
            line-height: 1.1;
        }
        .stat-info p {
            font-size: 13px;
            color: var(--gray-500);
            margin-top: 2px;
        }

        /* ===== TABLES ===== */
        .table-wrapper { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        table td {
            padding: 12px 16px;
            font-size: 14px;
            border-bottom: 1px solid var(--gray-100);
            vertical-align: middle;
        }
        table tr:hover td { background: var(--gray-50); }
        table tr:last-child td { border-bottom: none; }

        /* ===== BADGES ===== */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-green { background: var(--green-bg); color: #166534; }
        .badge-yellow { background: var(--yellow-bg); color: #92400e; }
        .badge-red { background: var(--red-bg); color: #991b1b; }
        .badge-blue { background: var(--blue-bg); color: #1e40af; }
        .badge-gray { background: var(--gray-100); color: var(--gray-600); }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            text-decoration: none;
        }
        .btn svg { width: 16px; height: 16px; }
        .btn-primary { background: var(--navy); color: #fff; }
        .btn-primary:hover { background: var(--navy-light); color: #fff; }
        .btn-gold { background: var(--gold); color: #fff; }
        .btn-gold:hover { background: var(--gold-dark); color: #fff; }
        .btn-success { background: var(--green); color: #fff; }
        .btn-success:hover { background: #16a34a; color: #fff; }
        .btn-danger { background: var(--red); color: #fff; }
        .btn-danger:hover { background: #dc2626; color: #fff; }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
        }
        .btn-outline:hover {
            border-color: var(--navy);
            color: var(--navy);
        }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        .btn-xs { padding: 4px 8px; font-size: 12px; }

        /* ===== FORMS ===== */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 6px;
        }
        .form-group .hint {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 4px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            font-size: 14px;
            font-family: inherit;
            color: var(--gray-800);
            background: var(--white);
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(27,58,92,0.1);
        }
        .form-control::placeholder { color: var(--gray-400); }
        select.form-control { cursor: pointer; }
        textarea.form-control { min-height: 100px; resize: vertical; }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .form-row .form-group { margin-bottom: 0; }
        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-check input[type="checkbox"] {
            width: 18px; height: 18px;
            accent-color: var(--navy);
            cursor: pointer;
        }
        .form-check label {
            margin-bottom: 0;
            cursor: pointer;
            font-size: 14px;
        }

        /* ===== PAGINATION ===== */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-top: 24px;
        }
        .pagination a, .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border-radius: var(--radius);
            font-size: 14px;
            font-weight: 500;
            border: 1px solid var(--gray-200);
            color: var(--gray-600);
            transition: all 0.2s;
        }
        .pagination a:hover { border-color: var(--navy); color: var(--navy); }
        .pagination .active {
            background: var(--navy);
            color: #fff;
            border-color: var(--navy);
        }
        .pagination .disabled {
            opacity: 0.4;
            pointer-events: none;
        }

        /* ===== IMAGE MANAGEMENT ===== */
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }
        .image-item {
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            border: 2px solid var(--gray-200);
            aspect-ratio: 4/3;
            background: var(--gray-100);
        }
        .image-item.is-cover { border-color: var(--gold); }
        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-item .image-actions {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .image-item:hover .image-actions { opacity: 1; }
        .image-item .cover-badge {
            position: absolute;
            top: 6px; left: 6px;
            background: var(--gold);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .image-btn {
            width: 32px; height: 32px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }
        .image-btn:hover { transform: scale(1.1); }
        .image-btn svg { width: 16px; height: 16px; }
        .image-btn.btn-cover-img { background: var(--gold); color: #fff; }
        .image-btn.btn-delete-img { background: var(--red); color: #fff; }

        /* ===== DRAG & DROP UPLOAD ===== */
        .upload-zone {
            border: 2px dashed var(--gray-300);
            border-radius: var(--radius);
            padding: 32px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--gray-50);
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--gold);
            background: rgba(200,150,62,0.05);
        }
        .upload-zone svg { width: 40px; height: 40px; color: var(--gray-400); margin-bottom: 8px; }
        .upload-zone p { color: var(--gray-500); font-size: 14px; }
        .upload-zone .upload-hint { font-size: 12px; color: var(--gray-400); margin-top: 4px; }
        .upload-previews {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }
        .upload-preview {
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            aspect-ratio: 4/3;
            border: 1px solid var(--gray-200);
        }
        .upload-preview img { width: 100%; height: 100%; object-fit: cover; }
        .upload-preview .remove-preview {
            position: absolute;
            top: 4px; right: 4px;
            width: 22px; height: 22px;
            border-radius: 50%;
            background: var(--red);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1;
        }

        /* ===== STAR TOGGLE ===== */
        .star-toggle {
            cursor: pointer;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--gray-300);
            transition: color 0.2s;
            padding: 2px;
        }
        .star-toggle.active { color: var(--gold); }
        .star-toggle:hover { color: var(--gold-light); }

        /* ===== LEAD EXPAND ===== */
        .lead-message {
            display: none;
            padding: 12px 16px;
            background: var(--gray-50);
            font-size: 14px;
            color: var(--gray-700);
            line-height: 1.6;
        }
        .lead-message.show { display: table-row; }
        .lead-row { cursor: pointer; }
        .lead-row.unread td { font-weight: 600; }

        /* ===== SEARCH / FILTER BAR ===== */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            align-items: center;
        }
        .filter-bar .search-input {
            flex: 1;
            min-width: 200px;
            position: relative;
        }
        .filter-bar .search-input input {
            padding-left: 38px;
        }
        .filter-bar .search-input svg {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px; height: 16px;
            color: var(--gray-400);
        }
        .filter-bar select {
            min-width: 160px;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--gray-400);
        }
        .empty-state svg { width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5; }
        .empty-state p { font-size: 15px; margin-bottom: 16px; }

        /* ===== VEHICLE THUMBNAIL ===== */
        .vehicle-thumb {
            width: 80px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
            background: var(--gray-100);
        }

        /* ===== TOOLTIP ===== */
        [data-tooltip] {
            position: relative;
        }
        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 6px);
            left: 50%;
            transform: translateX(-50%);
            background: var(--gray-800);
            color: #fff;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 100;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 20px rgba(0,0,0,0.3);
            }
            .admin-topbar {
                left: 0;
            }
            .admin-main {
                margin-left: 0;
            }
            .hamburger {
                display: block;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.4);
                z-index: 999;
            }
            .sidebar-overlay.open { display: block; }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 600px) {
            .admin-main { padding: 16px; }
            .stats-grid { grid-template-columns: 1fr; }
            .topbar-right .btn-view-site span { display: none; }
        }

        /* ===== MISC ===== */
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 16px; }
        .mb-3 { margin-bottom: 24px; }
        .mt-2 { margin-top: 16px; }
        .mt-3 { margin-top: 24px; }
        .text-muted { color: var(--gray-500); }
        .text-sm { font-size: 13px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .d-flex { display: flex; }
        .gap-1 { gap: 8px; }
        .gap-2 { gap: 16px; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .flex-wrap { flex-wrap: wrap; }
        .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">M</div>
            <div>
                <div class="logo-text">MULTICAR</div>
                <div class="logo-sub">Panel Admin</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= SITE_URL ?>/admin/" class="<?= $currentPage === 'index' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Dashboard
            </a>
            <a href="<?= SITE_URL ?>/admin/vehicles.php" class="<?= $currentPage === 'vehicles' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Vehiculos
            </a>
            <a href="<?= SITE_URL ?>/admin/vehicle_edit.php" class="<?= ($currentPage === 'vehicle_edit' && !isset($_GET['id'])) ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Nuevo Vehiculo
            </a>
            <a href="<?= SITE_URL ?>/admin/leads.php" class="<?= $currentPage === 'leads' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Leads / Consultas
                <?php if ($unreadLeads > 0): ?>
                    <span class="nav-badge"><?= $unreadLeads ?></span>
                <?php endif; ?>
            </a>
            <div class="nav-divider"></div>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/settings.php" class="<?= $currentPage === 'settings' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                Configuracion
            </a>
            <a href="<?= SITE_URL ?>/admin/users.php" class="<?= $currentPage === 'users' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                Usuarios
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            &copy; <?= date('Y') ?> MULTICAR
        </div>
    </aside>

    <!-- Top Bar -->
    <header class="admin-topbar">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn" aria-label="Abrir menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <h1 class="topbar-title"><?= e($adminTitle) ?></h1>
        </div>
        <div class="topbar-right">
            <a href="<?= SITE_URL ?>" target="_blank" class="btn-view-site" data-tooltip="Ver sitio web">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                <span>Ver sitio</span>
            </a>
            <div class="topbar-user">
                <div>
                    <div class="user-name"><?= e($_SESSION['user_name'] ?? 'Usuario') ?></div>
                    <div class="user-role"><?= e($_SESSION['user_role'] ?? '') ?></div>
                </div>
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></div>
            </div>
            <a href="<?= SITE_URL ?>/admin/logout.php" class="btn-logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Salir
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-main">
        <?php if (!empty($flashMessages)): ?>
        <div class="flash-container">
            <?php foreach ($flashMessages as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>">
                <?php if ($f['type'] === 'success'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?php elseif ($f['type'] === 'error'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?php endif; ?>
                <?= e($f['message']) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
