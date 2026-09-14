<?php
// games.php - Game Management & RTP Control Panel
// इसे आप admin.php में include कर सकते हैं या स्वतंत्र रूप से रन कर सकते हैं।

// डेटाबेस कनेक्शन (यदि अलग से उपयोग कर रहे हैं)
// include 'db.php';

$success_msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_game'])) {
    $game_name = $_POST['game_name'];
    $status = $_POST['status'];
    $house_edge = $_POST['house_edge'];
    // यहाँ डेटाबेस अपडेट क्वेरी लिख सकते हैं
    $success_msg = "Game '{$game_name}' successfully updated!";
}
?>

<div class="header-bar">
    <h1 class="page-title">🎮 गेम मैनेजमेंट & आरटीपी कंट्रोल</h1>
</div>

<?php if (!empty($success_msg)): ?>
    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px;">
        ✅ <?= htmlspecialchars($success_msg) ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">🕹️ प्लेटफ़ॉर्म एक्टिव गेम्स और विनिंग मार्जिन (House Edge)</div>
    <p style="color: var(--text-muted); font-size: 13px; margin-top: 5px;">
        यहाँ से आप तय कर सकते हैं कि किस गेम में यूजर जीतेगा या प्लेटफॉर्म का कितना फायदा रहेगा।
    </p>

    <div style="overflow-x: auto; margin-top: 20px;">
        <table style="width: 100%; border-collapse: collapse; color: #fff; font-size: 13px;">
            <thead>
                <tr style="background: rgba(255,255,255,0.05); text-align: left;">
                    <th style="padding: 12px;">गेम का नाम</th>
                    <th style="padding: 12px;">कैटेगरी</th>
                    <th style="padding: 12px;">स्टेटस</th>
                    <th style="padding: 12px;">हाउस एज / मार्जिन</th>
                    <th style="padding: 12px;">एक्शन</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 12px; font-weight: 600;">Color Prediction (3 Min)</td>
                    <td style="padding: 12px; color: var(--text-muted);">Lottery</td>
                    <td style="padding: 12px;"><span style="color: #10b981; background: rgba(16,185,129,0.1); padding: 4px 8px; border-radius: 4px; font-size: 11px;">🟢 Active</span></td>
                    <td style="padding: 12px;">2.5%</td>
                    <td style="padding: 12px;">
                        <button type="button" class="btn btn-primary" style="padding: 6px 12px; font-size: 11px;" onclick="openEditModal('Color Prediction 3M', 'Active', '2.5')">Configure ⚙️</button>
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 12px; font-weight: 600;">Aviator Crash Game</td>
                    <td style="padding: 12px; color: var(--text-muted);">Crash / Instant</td>
                    <td style="padding: 12px;"><span style="color: #10b981; background: rgba(16,185,129,0.1); padding: 4px 8px; border-radius: 4px; font-size: 11px;">🟢 Active</span></td>
                    <td style="padding: 12px;">4.0%</td>
                    <td style="padding: 12px;">
                        <button type="button" class="btn btn-primary" style="padding: 6px 12px; font-size: 11px;" onclick="openEditModal('Aviator Crash Game', 'Active', '4.0')">Configure ⚙️</button>
                    </td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 12px; font-weight: 600;">Andar Bahar</td>
                    <td style="padding: 12px; color: var(--text-muted);">Casino</td>
                    <td style="padding: 12px;"><span style="color: #ef4444; background: rgba(239,68,68,0.1); padding: 4px 8px; border-radius: 4px; font-size: 11px;">🔴 Maintenance</span></td>
                    <td style="padding: 12px;">3.0%</td>
                    <td style="padding: 12px;">
                        <button type="button" class="btn btn-primary" style="padding: 6px 12px; font-size: 11px;" onclick="openEditModal('Andar Bahar', 'Maintenance', '3.0')">Configure ⚙️</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Game Settings Modal / Form Box -->
<div class="card" id="editGameBox" style="display: none; border: 1px solid var(--primary-color);">
    <div class="card-header" id="modalTitle">⚙️ गेम सेटिंग्स एडिट करें</div>
    <form method="POST" style="margin-top: 15px;">
        <input type="hidden" name="update_game" value="1">
        <div class="form-grid">
            <div class="form-group">
                <label>गेम का नाम</label>
                <input type="text" class="form-control" name="game_name" id="inputGameName" readonly>
            </div>
            <div class="form-group">
                <label>गेम स्टेटस</label>
                <select class="form-control" name="status" id="inputStatus">
                    <option value="Active">Active (Live)</option>
                    <option value="Maintenance">Maintenance (Off)</option>
                </select>
            </div>
            <div class="form-group">
                <label>हाउस एज / प्रॉफिट मार्जिन (%)</label>
                <input type="text" class="form-control" name="house_edge" id="inputHouseEdge">
            </div>
        </div>
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn btn-success">💾 बदलाव सेव करें</button>
            <button type="button" class="btn btn-danger" style="background:#4b5563;" onclick="document.getElementById('editGameBox').style.display='none'">रद्द करें</button>
        </div>
    </form>
</div>

<script>
function openEditModal(name, status, edge) {
    document.getElementById('editGameBox').style.display = 'block';
    document.getElementById('inputGameName').value = name;
    document.getElementById('inputStatus').value = status;
    document.getElementById('inputHouseEdge').value = edge;
    document.getElementById('modalTitle').innerText = '⚙️ कस्टमाइज़ गेम: ' + name;
    window.scrollTo({top: document.body.scrollHeight, behavior: 'smooth'});
}
</script>
