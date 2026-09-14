<?php
// sidebar.php - Independent & Synced Sidebar Component
$active_tab = $active_tab ?? 'overview';
$pending_w = $pending_w ?? 0;
?>

<style>
/* CSS Styles for Sidebar Sync */
.sidebar {
    width: 260px;
    min-width: 260px;
    background: #0d111a;
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100vh;
    position: sticky;
    top: 0;
    z-index: 100;
    box-sizing: border-box;
}

.sidebar-brand {
    padding: 24px 20px;
    font-size: 18px;
    font-weight: 800;
    color: #fff;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    gap: 8px;
    letter-spacing: 0.5px;
}

.nav-menu {
    list-style: none;
    padding: 15px 10px;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nav-item a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: #94a3b8;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    border-radius: 12px;
    transition: all 0.2s ease-in-out;
}

.nav-item a:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.05);
}

.nav-item.active a {
    background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
    color: #ffffff;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(168, 85, 247, 0.4);
}

.user-profile-badge {
    padding: 16px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    background: rgba(15, 23, 42, 0.4);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logout-btn {
    color: #ef4444;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 8px;
    border-radius: 6px;
    background: rgba(239, 68, 68, 0.1);
    transition: 0.2s;
}

.logout-btn:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
}
</style>

<div class="sidebar">
    <div style="overflow-y: auto;">
        <div class="sidebar-brand">⚡ TC GAME MASTER</div>
        <ul class="nav-menu">
            <li class="nav-item <?= $active_tab == 'overview' ? 'active' : '' ?>"><a href="admin.php?tab=overview">📊 डैशबोर्ड ओवरव्यू</a></li>
            
            <!-- Game Management Group -->
            <li class="nav-item <?= ($active_tab == 'games' || $active_tab == 'game') ? 'active' : '' ?>"><a href="games.php?tab=games">🎮 गेम मैनेजमेंट</a></li>
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
    <div class="user-profile-badge">
        <div>
            <div style="font-size:12px; font-weight:700; color:#fff;"><?= htmlspecialchars($_SESSION['admin_user'] ?? 'admin') ?></div>
            <div style="font-size:10px; color:#94a3b8;">Super Admin</div>
        </div>
        <a href="admin.php?action=logout" class="logout-btn">Exit 🔒</a>
    </div>
</div>
