<style>
.sidebar__nav-link.icon-size-10 .sidebar__icon { width: 10px; }
.sidebar__nav-link.icon-size-10 > .sidebar__icon,
.sidebar__nav-link.icon-size-10 > .sidebar__icon[src],
.sidebar__nav-link.icon-size-10 img.sidebar__icon {
    width: 10px !important;
    max-width: 10px !important;
    height: auto !important;
    display: block !important;
}
</style>

<aside class="sidebar">
    <?php
    // $base_path  → prefix for assets (images, CSS)
    // $nav_path   → prefix for navigation links
    $base_path = isset($GLOBALS['base_path']) ? $GLOBALS['base_path'] : '';
    $nav_path  = isset($GLOBALS['nav_path'])  ? $GLOBALS['nav_path']  : $base_path;
    ?>
    <div class="sidebar__brand">
        <a href="<?php echo $nav_path; ?>index.php" class="sidebar__logo">
            <img src="<?php echo $base_path; ?>assets/image/logo.png" alt="Navi Shipping" class="sidebar__logo-img">
        </a>
    </div>

    <nav class="sidebar__nav">
        <ul class="sidebar__nav-list">
            <?php
            $current = basename($_SERVER['PHP_SELF']);
            $navItems = [
                ['href' => 'index.php',       'title' => 'Dashboard',       'img' => 'assets/image/dash.png'],
                ['href' => 'crew.php',         'title' => 'Crew Management', 'img' => 'assets/image/vessel.png'],
                ['href' => 'staff.php',        'title' => 'Staff Management','img' => 'assets/image/staff.png'],
                ['href' => 'tests.php',        'title' => 'NSC Result',      'img' => 'assets/image/test.png'],
                ['href' => 'rep.php',          'title' => 'Crew Change',     'img' => 'assets/image/rep.png'],
                ['href' => 'application.php',  'title' => 'Application Form','img' => 'assets/image/ap.png'],
                ['href' => 'settings.php',     'title' => 'Settings',        'img' => 'assets/image/settings.png'],
            ];
            $perItemSizes = [
                'index.php' => ['width' => '24px', 'height' => '24px'],
                'crew.php' => ['width' => '30px', 'height' => '30px'],
                'staff.php' => ['width' => '24px', 'height' => '26px'],
                'tests.php' => ['width' => '24px', 'height' => '26px'],
                'rep.php' => ['width' => '32px', 'height' => '32px'],
                'application.php' => ['width' => '30px', 'height' => '30px'],
                'settings.php' => ['width' => '30px', 'height' => '30px'],
            ];

            foreach ($navItems as $item) {
                // determine active state
                // When viewing crew_documents.php or crew.php, highlight Crew Management
                $active = '';
                if ($current === 'crew.php' || $current === 'crew_documents.php') {
                    // mark the Crew menu as active when on crew.php or crew_documents.php
                    if ($item['href'] === 'crew.php') {
                        $active = 'is-active';
                    }
                } else {
                    // normal exact-match behavior for other pages
                    if ($current === $item['href']) {
                        $active = 'is-active';
                    }
                }

                $iconStyle = '';
                if (isset($perItemSizes[$item['href']])) {
                    $width = $perItemSizes[$item['href']]['width'];
                    $height = $perItemSizes[$item['href']]['height'];
                    $iconStyle = ' style="width: ' . $width . '; height: ' . $height . ';"';
                }
                ?>
                <li class="sidebar__nav-item">
                    <a href="<?php echo $nav_path . $item['href']; ?>" class="sidebar__nav-link <?php echo $active; ?>" title="<?php echo htmlspecialchars($item['title']); ?>">
                        <img class="sidebar__icon" src="<?php echo $base_path . $item['img']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>"<?php echo $iconStyle; ?>>
                        <span class="sidebar__label">
                            <span class="label__title"><?php echo htmlspecialchars($item['title']); ?></span>
                        </span>
                    </a>
                </li>
                <?php
            }
            ?>
        </ul>
    </nav>

    <div class="sidebar__logout">
        <a href="<?php echo $nav_path; ?>logout.php">Logout</a>
    </div>
</aside>
