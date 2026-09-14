<?php
// sidebar.php - Independent & Synced Sidebar Component
$active_tab = $active_tab ?? 'overview';
$pending_w = $pending_w ?? 0;
?>
<div class="sidebar" style="height: 100vh; overflow-y: auto;">
    <div>
        <div class="sidebar-brand">⚡ TC GAME MASTER</div>
        <ul class="nav-menu" style="list-style: none; padding: 0;">
            <li class="nav-item <?= $active_tab == 'overview' ? 'active' : '' ?>"><a href="admin.php?tab=overview">📊 डैशबोर्ड ओवरव्यू</a></li>
            
            <!-- Game Management Group -->
            <li class="nav-item <?= $active_tab == 'games' ? 'active' : '' ?>"><a href="games.php?tab=games">🎮 गेम मैनेजमेंट</a></li>
            <li class="nav-item <?= $active_tab == 'color_prediction' ? 'active' : '' ?>"><a href="color_prediction.php?tab=color_prediction">🎯 कलर प्रेडिक्शन कंट्रोल</a></li>
            <li class="nav-item <?= $active_tab == 'aviator_control' ? 'active' : '' ?>"><a href="admin.php?tab=aviator_control">🚀 एविएटर / क्रैश गेम</a></li>
            <li class="nav-item <?= $active_tab == 'live_bets' ? 'active' : '' ?>"><a href="admin.php?tab=live_bets">🎲 लाइव बेट्स हिस्ट्री</a></li>
            <li class="nav-item <?= $active_tab == 'jackpots' ? 'active' : '' ?>"><a href="admin.php?tab=jackpots">🏆 जैकपॉट & रिवार्ड्स</a></li>

            <!-- Financial & User Group -->
            <li class="nav-item <?= $active_tab == 'users' ? 'active' : '' ?>"><a href="admin.php?tab=users">👥 यूजर कंट्रोल & वॉलेट</a></li>
            <li class="nav-item <?= $active_tab == 'withdrawals' ? 'active' : '' ?>"><a href="admin.php?tab=withdrawals">💳 विथड्रॉल (<?= $pending_w ?>)</a></li>
            <li class="nav-item <?= $active_tab == 'deposits' ? 'active' : '' ?>"><a href="admin.php?tab=deposits">💰 डिपॉजिट अप्रूवल</a></li>
            <li class="nav-item <?= $active_tab == 'transactions' ? 'active' : '' ?>"><a href="admin.php?tab=transactions">📜 सभी ट्रांजैक्शन लॉग्स</a></li>
            <li class="nav-item <?= $active_tab == 'gateway' ? 'active' : '' ?>"><a href="admin.php?tab=gateway">🌐 पेमेंट गेटवे सेटिंग्स</a></li>

            <!-- Marketing & Community -->
            <li class="nav-item <?= $active_tab == 'bonus' ? 'active' : '' ?>"><a href="admin.php?tab=bonus">🎁 बोनस & कूपन कोड</a></li>
            <li class="nav-item <?= $active_tab == 'vip_tiers' ? 'active' : '' ?>"><a href="admin.php?tab=vip_tiers">⭐ वीआईपी लेवल्स</a></li>
            <li class="nav-item <?= $active_tab == 'affiliate' ? 'active' : '' ?>"><a href="admin.php?tab=affiliate">🤝 एफिलिएट / रेफरल</a></li>
            <li class="nav-item <?= $active_tab == 'notifications' ? 'active' : '' ?>"><a href="admin.php?tab=notifications">🔔 पुश नोटिफिकेशन्स</a></li>

            <!-- System & CMS -->
            <li class="nav-item <?= $active_tab == 'pages' ? 'active' : '' ?>"><a href="admin.php?tab=pages">📄 डायनामिक पेजेस</a></li>
            <li class="nav-item <?= $active_tab == 'analytics' ? 'active' : '' ?>"><a href="admin.php?tab=analytics">📈 एनालिटिक्स & रिपोर्ट्स</a></li>
            <li class="nav-item <?= $active_tab == 'security' ? 'active' : '' ?>"><a href="admin.php?tab=security">🛡️ सिक्योरिटी & आईपी लॉग</a></li>
            <li class="nav-item <?= $active_tab == 'telegram_logs' ? 'active' : '' ?>"><a href="admin.php?tab=telegram_logs">🤖 टेलीग्राम बोट लॉग्स</a></li>
            <li class="nav-item <?= $active_tab == 'api_docs' ? 'active' : '' ?>"><a href="admin.php?tab=api_docs">🔌 एपीआई इंटीग्रेशन</a></li>
            <li class="nav-item <?= $active_tab == 'settings' ? 'active' : '' ?>"><a href="admin.php?tab=settings">⚙️ ग्लोबल सिस्टम सेटिंग्स</a></li>
        </ul>
    </div>
    <div class="user-profile-badge" style="margin-top: 20px;">
        <div>
            <div style="font-size:12px; font-weight:700; color:#fff;"><?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></div>
            <div style="font-size:10px; color:var(--text-muted);">Super Admin</div>
        </div>
        <a href="admin.php?action=logout" class="logout-btn">Exit 🔒</a>
    </div>
</div>
