<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';





function normalizedPath(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $path = parse_url($uri, PHP_URL_PATH);
    return is_string($path) ? $path : '';
}

 
function isSidebarRole(string $role): bool
{
    return in_array($role, ['admin', 'staff', 'student'], true);
}





function navLinksForRole(string $role, int $cartCount): array
{
    if ($role === 'admin') {
        return [
            ['icon' => '🏠', 'label' => 'Dashboard',        'href' => '/CAPSTONE/admin/dashboard.php'],
            ['icon' => '🛒', 'label' => 'POS',               'href' => '/CAPSTONE/admin/pos_transactions.php'],
            ['icon' => '📋', 'label' => 'Orders',            'href' => '/CAPSTONE/admin/orders.php'],
            ['icon' => '📦', 'label' => 'Products',          'href' => '/CAPSTONE/admin/products.php'],
            ['icon' => '🗄️', 'label' => 'Inventory',         'href' => '/CAPSTONE/admin/inventory.php'],
            ['icon' => '📊', 'label' => 'Reports',           'href' => '/CAPSTONE/admin/reports.php'],
            ['icon' => '👥', 'label' => 'Users',             'href' => '/CAPSTONE/admin/users.php'],
            ['icon' => '📜', 'label' => 'System Logs',       'href' => '/CAPSTONE/admin/logs.php'],
            ['icon' => '⚙️', 'label' => 'Settings',          'href' => '/CAPSTONE/admin/settings.php'],
            ['icon' => '🚪', 'label' => 'Logout',            'href' => '/CAPSTONE/admin/logout.php'],
        ];
    }

    if ($role === 'staff') {
        return [
            ['icon' => '🏠', 'label' => 'Dashboard',    'href' => '/CAPSTONE/staff/dashboard.php'],
            ['icon' => '🛒', 'label' => 'POS',           'href' => '/CAPSTONE/staff/pos.php'],
            ['icon' => '📋', 'label' => 'Orders',        'href' => '/CAPSTONE/staff/orders.php'],
            ['icon' => '🗄️', 'label' => 'Inventory',     'href' => '/CAPSTONE/admin/inventory.php'],
            ['icon' => '📦', 'label' => 'Order Pickup',  'href' => '/CAPSTONE/staff/pickup.php'],
            ['icon' => '📊', 'label' => 'Reports',       'href' => '/CAPSTONE/staff/reports.php'],
            ['icon' => '👤', 'label' => 'Profile',       'href' => '/CAPSTONE/staff/profile.php'],
            ['icon' => '🚪', 'label' => 'Logout',        'href' => '/CAPSTONE/admin/logout.php'],
        ];
    }

    if ($role === 'student') {
        return [
            ['icon' => '🏠', 'label' => 'Dashboard',      'href' => '/CAPSTONE/student/dashboard.php'],
            ['icon' => '🛍️', 'label' => 'Browse Items',   'href' => '/CAPSTONE/student/browse.php'],
            ['icon' => '🛒', 'label' => 'Cart (' . $cartCount . ')', 'href' => '/CAPSTONE/cart.php'],
            ['icon' => '📋', 'label' => 'My Orders',      'href' => '/CAPSTONE/student/my_orders.php'],
            ['icon' => '📍', 'label' => 'Track Order',    'href' => '/CAPSTONE/track.php'],
            ['icon' => '👤', 'label' => 'Profile',        'href' => '/CAPSTONE/student/profile.php'],
            ['icon' => '🚪', 'label' => 'Logout',         'href' => '/CAPSTONE/admin/logout.php'],
        ];
    }

     
    return [
        ['label' => 'Home',        'href' => '/CAPSTONE/index.php'],
        ['label' => 'Browse Items','href' => '/CAPSTONE/index.php#catalog'],
        ['label' => 'Cart (' . $cartCount . ')', 'href' => '/CAPSTONE/cart.php'],
        ['label' => 'Track Order', 'href' => '/CAPSTONE/track.php'],
        ['label' => 'About RGO',   'href' => '/CAPSTONE/index.php#about-rgo'],
        ['label' => 'Contact',     'href' => '/CAPSTONE/index.php#contact-rgo'],
        ['label' => 'Login',       'href' => '/CAPSTONE/admin/login.php'],
    ];
}





function renderSidebar(string $role, array $links, string $currentPath, string $userName): void
{
    $roleLabel = strtoupper($role);
    $initials  = strtoupper(substr($userName, 0, 2));

    echo '<aside class="sidebar" id="sidebar">';

     
    echo '<div class="sb-brand">';
    echo '<img class="sb-logo" src="/CAPSTONE/img/BSUlogo.jpg" alt="BSU" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
    echo '<span class="sb-logo-fallback">' . e($initials) . '</span>';
    echo '<div class="sb-brand-text"><strong>BatState-U</strong><small>RGO System</small></div>';
    echo '</div>';

     
    echo '<div class="sb-role-badge">' . e($roleLabel) . ' PORTAL</div>';

     
    echo '<nav class="sb-nav">';
    foreach ($links as $link) {
        $href      = (string) ($link['href'] ?? '#');
        $label     = (string) ($link['label'] ?? '');
        $icon      = (string) ($link['icon'] ?? '');
        $linkPath  = parse_url($href, PHP_URL_PATH);
        $active    = is_string($linkPath) && $linkPath !== '' && $currentPath !== '' && $currentPath === $linkPath;
        $isLogout  = str_contains($href, 'logout');
        $cls       = 'sb-link';
        if ($active)   $cls .= ' active';
        if ($isLogout) $cls .= ' logout';
        echo '<a class="' . $cls . '" href="' . e($href) . '">';
        if ($icon !== '') echo '<span class="sb-icon" aria-hidden="true">' . $icon . '</span>';
        echo '<span>' . e($label) . '</span>';
        echo '</a>';
    }
    echo '</nav>';

     
    echo '<div class="sb-user">';
    echo '<div class="sb-avatar">' . e($initials) . '</div>';
    echo '<div class="sb-user-info"><strong>' . e($userName) . '</strong><small>' . e($roleLabel) . '</small></div>';
    echo '</div>';

    echo '</aside>';
}





function renderHeader(string $title, ?string $menuRole = null): void
{
    $flashOk    = flash('success');
    $flashErr   = flash('error');
    $cartCount  = cartCount();
    $user       = $_SESSION['auth_user'] ?? null;
    $role       = $menuRole ?? (is_array($user) ? (string) ($user['role'] ?? '') : '');
    $currentPath = normalizedPath();
    $links      = navLinksForRole($role, $cartCount);
    $useSidebar = isSidebarRole($role);
    $userName   = is_array($user) ? (string) ($user['name'] ?? $role) : $role;

    echo '<!doctype html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' — RGO</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">';
    echo '<link rel="stylesheet" href="/CAPSTONE/assets/styles.css">';
    echo '</head>';

    if ($useSidebar) {
         
        echo '<body class="has-sidebar">';
        echo '<div class="app-shell">';

        renderSidebar($role, $links, $currentPath, $userName);

        echo '<div class="app-body">';

         
        echo '<header class="mob-topbar">';
        echo '<button class="mob-menu-btn" onclick="document.getElementById(\'sidebar\').classList.toggle(\'open\')" aria-label="Toggle menu">☰</button>';
        echo '<span class="mob-title">' . e($title) . '</span>';
        echo '</header>';

        echo '<main class="dash-content">';

        if ($flashOk !== null)  echo '<div class="alert success">' . e($flashOk)  . '</div>';
        if ($flashErr !== null) echo '<div class="alert error">'   . e($flashErr) . '</div>';

    } else {
         
        echo '<body>';
        echo '<div class="page-bg"></div>';
        echo '<header class="topbar">';
        echo '<div class="container nav">';
        echo '<a class="brand" href="/CAPSTONE/index.php">';
        echo '<span class="brand-mark" aria-hidden="true">';
        echo '<img class="brand-logo" src="/CAPSTONE/img/BSUlogo.jpg" alt="Batangas State University Seal" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'grid\';">';
        echo '<span class="brand-fallback">BSU</span>';
        echo '</span>';
        echo '<span class="brand-text">';
        echo '<strong>Batangas State University</strong>';
        echo '<small>RGO Ordering System</small>';
        echo '</span>';
        echo '</a>';
        echo '<nav class="menu">';
        foreach ($links as $link) {
            $href     = (string) ($link['href'] ?? '/CAPSTONE/index.php');
            $label    = (string) ($link['label'] ?? 'Link');
            $linkPath = parse_url($href, PHP_URL_PATH);
            $hasFragment = str_contains($href, '#');
            $fragment = (string) (parse_url($href, PHP_URL_FRAGMENT) ?? '');
            $active   = !$hasFragment && is_string($linkPath) && $linkPath !== '' && $currentPath !== '' && $currentPath === $linkPath;
            $class    = $active ? ' class="active"' : '';
            $fragmentAttr = $fragment !== '' ? ' data-nav-fragment="' . e($fragment) . '"' : '';
            echo '<a' . $class . $fragmentAttr . ' href="' . e($href) . '">' . e($label) . '</a>';
        }
        echo '</nav>';
        echo '<div class="header-right" aria-hidden="true">';
        echo '<img class="right-emblem" src="/CAPSTONE/img/right-emblem.png" alt="" onerror="this.style.display=\'none\';">';
        echo '</div>';
        echo '</div>';
        echo '</header>';
        echo '<div class="top-accent"></div>';
        echo '<main class="main-content">';

        if ($flashOk !== null)  echo '<div class="alert success">' . e($flashOk)  . '</div>';
        if ($flashErr !== null) echo '<div class="alert error">'   . e($flashErr) . '</div>';
    }
}

function renderFooter(?string $menuRole = null): void
{
    $user       = $_SESSION['auth_user'] ?? null;
    $sessionRole = is_array($user) ? (string) ($user['role'] ?? '') : '';
    $role       = $menuRole ?? $sessionRole;
    $useSidebar = isSidebarRole($role);

    if ($useSidebar) {
        echo '</main>';   
        echo '</div>';    
        echo '</div>';    
    } else {
        echo '</main>';
        echo '<footer class="footer"><div class="container">© ' . date('Y') . ' BatState-U RGO Ordering System</div></footer>';
        echo '<script>';
        echo '(function () {';
        echo '  var links = document.querySelectorAll(".menu a");';
        echo '  if (!links.length) return;';
        echo '  function pathOf(href) {';
        echo '    try { return new URL(href, window.location.origin).pathname; } catch (e) { return ""; }';
        echo '  }';
        echo '  function syncActive() {';
        echo '    var currentPath = window.location.pathname;';
        echo '    var hash = (window.location.hash || "").replace(/^#/, "");';
        echo '    var matched = false;';
        echo '    links.forEach(function (link) {';
        echo '      link.classList.remove("active");';
        echo '      var fragment = link.getAttribute("data-nav-fragment") || "";';
        echo '      if (hash !== "" && fragment === hash) {';
        echo '        link.classList.add("active");';
        echo '        matched = true;';
        echo '      }';
        echo '    });';
        echo '    if (!matched) {';
        echo '      links.forEach(function (link) {';
        echo '        var fragment = link.getAttribute("data-nav-fragment") || "";';
        echo '        if (fragment !== "") return;';
        echo '        if (pathOf(link.href) === currentPath) {';
        echo '          link.classList.add("active");';
        echo '          matched = true;';
        echo '        }';
        echo '      });';
        echo '    }';
        echo '    if (!matched && currentPath === "/CAPSTONE/index.php") {';
        echo '      var home = document.querySelector(".menu a[href=\"/CAPSTONE/index.php\"]");';
        echo '      if (home) home.classList.add("active");';
        echo '    }';
        echo '  }';
        echo '  window.addEventListener("hashchange", syncActive);';
        echo '  syncActive();';
        echo '})();';
        echo '</script>';
    }

    echo '</body>';
    echo '</html>';
}
