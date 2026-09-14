<?php
require_once 'db.php';

// डेटाबेस से सभी एक्टिव पेजेस को ऑटो-फेच करना
$dynamic_nav_query = mysqli_query($conn, "SELECT * FROM dynamic_pages WHERE status='active' ORDER BY nav_order ASC");

echo '<ul class="user-nav-menu">';
while ($page = mysqli_fetch_assoc($dynamic_nav_query)) {
    echo '<li class="nav-item">';
    echo '<a href="page.php?slug=' . htmlspecialchars($page['slug']) . '">';
    echo '<span class="icon">' . $page['icon'] . '</span> ';
    echo '<span class="title">' . htmlspecialchars($page['title']) . '</span>';
    echo '</a>';
    echo '</li>';
}
echo '</ul>';
?>
