<?php
// color_prediction.php - Color Prediction Control Panel
// इसे आप admin.php में include कर सकते हैं या स्वतंत्र रूप से रन कर सकते हैं।

$success_msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['set_manual_result'])) {
    $period_no = $_POST['period_no'];
    $winning_color = $_POST['winning_color'];
    $winning_number = $_POST['winning_number'];
    // यहाँ आप डेटाबेस में मैनुअल रिजल्ट स्टोर करने की क्वेरी लिख सकते हैं
    $success_msg = "Period #{$period_no} के लिए मैनुअल रिजल्ट সফলपूर्वक सेट कर दिया गया है!";
}
?>

<div class="header-bar">
    <h1 class="page-title">🎯 कलर प्रेडिक्शन कंट्रोल & आरटीपी</h1>
</div>

<?php if (!empty($success_msg)): ?>
    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px;">
        ✅ <?= htmlspecialchars($success_msg) ?>
    </div>
<?php endif; ?>

<!-- Live Game Status Card -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">📊 वर्तमान गेम स्टेटस (Live Period Info)</div>
    <div class="form-grid" style="margin-top: 15px;">
        <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">करेंट पीरियड नंबर (Period No)</div>
            <div style="font-size: 20px; font-weight: 700; color: #fff; margin-top: 5px;">202609141029</div>
        </div>
        <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">टाइमर (Time Remaining)</div>
            <div style="font-size: 20px; font-weight: 700; color: #f59e0b; margin-top: 5px;" id="countdownTimer">01:45</div>
        </div>
        <div style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 8px;">
            <div style="font-size: 12px; color: var(--text-muted);">कुल बेट्स (Total Pool Amount)</div>
            <div style="font-size: 20px; font-weight: 700; color: #10b981; margin-top: 5px;">₹14,550</div>
        </div>
    </div>
</div>

<!-- Manual Result Control Form -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">🛠️ मैनुअल रिजल्ट मैनिपुलेशन (Admin Control)</div>
    <p style="color: var(--text-muted); font-size: 13px; margin-top: 5px;">
        अगले पीरियड के लिए फिक्स रिजल्ट सेट करें (यदि ऑटो मोड बंद है)।
    </p>

    <form method="POST" style="margin-top: 15px;">
        <div class="form-grid">
            <div class="form-group">
                <label>पीरियड नंबर (Period No)</label>
                <input type="text" class="form-control" name="period_no" value="202609141030" required>
            </div>
            <div class="form-group">
                <label>विनिंग कलर (Winning Color)</label>
                <select class="form-control" name="winning_color" required>
                    <option value="Green">🟢 Green</option>
                    <option value="Red">🔴 Red</option>
                    <option value="Violet">🟣 Violet</option>
                </select>
            </div>
            <div class="form-group">
                <label>विनिंग नंबर (0 से 9)</label>
                <input type="number" class="form-control" name="winning_number" min="0" max="9" value="5" required>
            </div>
        </div>
        <div style="margin-top: 20px;">
            <button type="submit" name="set_manual_result" class="btn btn-primary">🚀 रिजल्ट लॉक करें (Set Result)</button>
        </div>
    </form>
</div>

<!-- Recent Game History Table -->
<div class="card">
    <div class="card-header">📜 पिछले परिणाम इतिहास (Recent Results History)</div>
    <div style="overflow-x: auto; margin-top: 15px;">
        <table style="width: 100%; border-collapse: collapse; color: #fff; font-size: 13px;">
            <thead>
                <tr style="background: rgba(255,255,255,0.05); text-align: left;">
                    <th style="padding: 12px;">Period No</th>
                    <th style="padding: 12px;">Price</th>
                    <th style="padding: 12px;">Number</th>
                    <th style="padding: 12px;">Result Color</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 12px;">202609141028</td>
                    <td style="padding: 12px;">54201</td>
                    <td style="padding: 12px; font-weight: bold;">4</td>
                    <td style="padding: 12px;"><span style="color: #ef4444; background: rgba(239,68,68,0.1); padding: 3px 8px; border-radius: 4px; font-size: 11px;">Red</span></td>
                </tr>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 12px;">202609141027</td>
                    <td style="padding: 12px;">54198</td>
                    <td style="padding: 12px; font-weight: bold;">9</td>
                    <td style="padding: 12px;"><span style="color: #10b981; background: rgba(16,185,129,0.1); padding: 3px 8px; border-radius: 4px; font-size: 11px;">Green & Violet</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
