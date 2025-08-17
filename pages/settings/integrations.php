<?php
/*****************************************************************
 * pages/settings/integrations_enhanced.php
 * ---------------------------------------------------------------
 * Enhanced integration management with multiple providers
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* ───────── Admin-only access ───────── */
if (!is_admin()) {
    flash('Integration settings are restricted to administrators.');
    redirect('/index.php');
}

/* ───────── Create Integration settings table ───────── */
$conn->query("
CREATE TABLE IF NOT EXISTS IntegrationSettings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    integration_type ENUM('payment_gateway', 'email_service', 'sms_service', 'calendar_sync', 'backup_service') NOT NULL,
    provider_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    webhook_url VARCHAR(255),
    is_active BOOLEAN DEFAULT FALSE,
    test_mode BOOLEAN DEFAULT TRUE,
    configuration JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_integration (integration_type, provider_name)
)
");

/* ───────── Handle form submissions ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_integration';
    
    if ($action === 'save_integration') {
        $integration_type = $conn->real_escape_string($_POST['integration_type']);
        $provider_name = $conn->real_escape_string($_POST['provider_name']);
        $api_key = $conn->real_escape_string($_POST['api_key']);
        $api_secret = $conn->real_escape_string($_POST['api_secret']);
        $webhook_url = $conn->real_escape_string($_POST['webhook_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $test_mode = isset($_POST['test_mode']) ? 1 : 0;
        
        // Build configuration JSON
        $config = [];
        if (!empty($_POST['config_key']) && !empty($_POST['config_value'])) {
            foreach ($_POST['config_key'] as $i => $key) {
                if (!empty($key) && !empty($_POST['config_value'][$i])) {
                    $config[$key] = $_POST['config_value'][$i];
                }
            }
        }
        $configuration = json_encode($config);
        
        $sql = "INSERT INTO IntegrationSettings 
                (integration_type, provider_name, api_key, api_secret, webhook_url, 
                 is_active, test_mode, configuration)
                VALUES 
                ('$integration_type', '$provider_name', '$api_key', '$api_secret', '$webhook_url', 
                 $is_active, $test_mode, '$configuration')
                ON DUPLICATE KEY UPDATE
                api_key = '$api_key',
                api_secret = '$api_secret',
                webhook_url = '$webhook_url',
                is_active = $is_active,
                test_mode = $test_mode,
                configuration = '$configuration'";
        
        if ($conn->query($sql)) {
            flash('Integration settings saved successfully.');
        } else {
            flash('Error: ' . $conn->error, 'error');
        }
        redirect('integrations_enhanced.php');
    }
    
    if ($action === 'test_integration') {
        $setting_id = intval($_POST['setting_id']);
        $setting = $conn->query("SELECT * FROM IntegrationSettings WHERE setting_id = $setting_id")->fetch_assoc();
        
        if ($setting) {
            // Basic test functionality
            $test_result = test_integration($setting);
            flash($test_result['success'] ? 'Integration test successful!' : 'Integration test failed: ' . $test_result['message'], 
                  $test_result['success'] ? 'success' : 'error');
        }
        redirect('integrations_enhanced.php');
    }
}

/* ───────── Test integration function ───────── */
function test_integration($setting) {
    // Basic test implementation - expand based on integration type
    switch ($setting['integration_type']) {
        case 'payment_gateway':
            if ($setting['provider_name'] === 'Stripe') {
                return ['success' => !empty($setting['api_key']), 'message' => 'Stripe API key validation'];
            }
            break;
        case 'email_service':
            return ['success' => !empty($setting['api_key']), 'message' => 'Email service credentials validation'];
        case 'sms_service':
            return ['success' => !empty($setting['api_key']), 'message' => 'SMS service credentials validation'];
        default:
            return ['success' => true, 'message' => 'Basic configuration check passed'];
    }
    return ['success' => false, 'message' => 'Unknown integration type'];
}

/* ───────── Fetch existing integrations ───────── */
$integrations = $conn->query("SELECT * FROM IntegrationSettings ORDER BY integration_type, provider_name");

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>
<main>
    <h2>Enhanced Integration & API Settings</h2>
    <?= get_flash(); ?>

    <!-- Navigation -->
    <div style="margin-bottom: 30px;">
        <a class="btn btn-outline" href="integrations.php">← Legacy Integrations</a>
        <a class="btn btn-outline" href="clinic_info.php">🏥 Clinic Info</a>
        <a class="btn btn-outline" href="users.php">👥 Users</a>
    </div>

    <!-- Add New Integration -->
    <div class="card" style="margin-bottom: 30px;">
        <h3>Add New Integration</h3>
        <form method="post" id="integrationForm">
            <input type="hidden" name="action" value="save_integration">
            
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="form-label">Integration Type *</label>
                    <select name="integration_type" required class="form-select" id="integrationType">
                        <option value="">Select Type</option>
                        <option value="payment_gateway">💳 Payment Gateway</option>
                        <option value="email_service">📧 Email Service</option>
                        <option value="sms_service">📱 SMS Service</option>
                        <option value="calendar_sync">📅 Calendar Sync</option>
                        <option value="backup_service">💾 Backup Service</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Provider Name *</label>
                    <select name="provider_name" required class="form-select" id="providerName">
                        <option value="">Select Provider</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">API Key *</label>
                    <input type="password" name="api_key" required class="form-input" 
                           placeholder="Enter API key">
                </div>

                <div class="form-group">
                    <label class="form-label">API Secret</label>
                    <input type="password" name="api_secret" class="form-input" 
                           placeholder="Enter API secret (if required)">
                </div>

                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Webhook URL</label>
                    <input type="url" name="webhook_url" class="form-input" 
                           placeholder="https://your-domain.com/webhook/endpoint">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_active" value="1">
                        <span>Enable Integration</span>
                    </label>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="test_mode" value="1" checked>
                        <span>Test Mode</span>
                    </label>
                </div>
            </div>

            <!-- Additional Configuration -->
            <div class="form-group" style="margin-top: 20px;">
                <label class="form-label">Additional Configuration</label>
                <div id="configContainer">
                    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                        <input type="text" name="config_key[]" placeholder="Key" style="flex: 1;">
                        <input type="text" name="config_value[]" placeholder="Value" style="flex: 1;">
                        <button type="button" onclick="addConfigRow()" class="btn btn-sm btn-outline">+</button>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Save Integration</button>
                <button type="button" onclick="document.getElementById('integrationForm').reset()" class="btn btn-outline">Reset</button>
            </div>
        </form>
    </div>

    <!-- Existing Integrations -->
    <div class="card">
        <h3>Configured Integrations</h3>
        <?php if ($integrations->num_rows === 0): ?>
            <p style="text-align: center; color: #666; padding: 20px;">No integrations configured yet.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Mode</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($integration = $integrations->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?= ucfirst(str_replace('_', ' ', $integration['integration_type'])); ?>
                            </td>
                            <td><?= htmlspecialchars($integration['provider_name']); ?></td>
                            <td>
                                <span class="badge badge-<?= $integration['is_active'] ? 'success' : 'danger'; ?>">
                                    <?= $integration['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $integration['test_mode'] ? 'warning' : 'primary'; ?>">
                                    <?= $integration['test_mode'] ? 'Test' : 'Live'; ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($integration['updated_at'])); ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="test_integration">
                                    <input type="hidden" name="setting_id" value="<?= $integration['setting_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline">Test</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Integration Templates -->
    <div class="card" style="margin-top: 30px;">
        <h3>Popular Integration Templates</h3>
        <div class="grid grid-cols-3 gap-4">
            <div class="card" style="padding: 15px;">
                <h4>Stripe Payment</h4>
                <p style="font-size: 12px; color: #666;">Accept credit card payments</p>
                <button onclick="useTemplate('stripe')" class="btn btn-sm btn-primary">Use Template</button>
            </div>
            <div class="card" style="padding: 15px;">
                <h4>SendGrid Email</h4>
                <p style="font-size: 12px; color: #666;">Send appointment reminders</p>
                <button onclick="useTemplate('sendgrid')" class="btn btn-sm btn-primary">Use Template</button>
            </div>
            <div class="card" style="padding: 15px;">
                <h4>Twilio SMS</h4>
                <p style="font-size: 12px; color: #666;">SMS notifications</p>
                <button onclick="useTemplate('twilio')" class="btn btn-sm btn-primary">Use Template</button>
            </div>
        </div>
    </div>
</main>

<script>
// Provider options based on integration type
const providerOptions = {
    'payment_gateway': ['Stripe', 'PayPal', 'Square', 'Authorize.Net'],
    'email_service': ['SendGrid', 'Mailgun', 'Amazon SES', 'SMTP'],
    'sms_service': ['Twilio', 'Nexmo', 'Amazon SNS', 'TextMagic'],
    'calendar_sync': ['Google Calendar', 'Outlook', 'CalDAV'],
    'backup_service': ['AWS S3', 'Google Drive', 'Dropbox', 'Local']
};

document.getElementById('integrationType').addEventListener('change', function() {
    const type = this.value;
    const providerSelect = document.getElementById('providerName');
    
    providerSelect.innerHTML = '<option value="">Select Provider</option>';
    
    if (type && providerOptions[type]) {
        providerOptions[type].forEach(provider => {
            providerSelect.innerHTML += `<option value="${provider}">${provider}</option>`;
        });
    }
});

function addConfigRow() {
    const container = document.getElementById('configContainer');
    const newRow = document.createElement('div');
    newRow.style.cssText = 'display: flex; gap: 10px; margin-bottom: 10px;';
    newRow.innerHTML = `
        <input type="text" name="config_key[]" placeholder="Key" style="flex: 1;">
        <input type="text" name="config_value[]" placeholder="Value" style="flex: 1;">
        <button type="button" onclick="this.parentElement.remove()" class="btn btn-sm btn-danger">×</button>
    `;
    container.appendChild(newRow);
}

function useTemplate(template) {
    const form = document.getElementById('integrationForm');
    
    switch(template) {
        case 'stripe':
            form.integration_type.value = 'payment_gateway';
            form.integration_type.dispatchEvent(new Event('change'));
            setTimeout(() => form.provider_name.value = 'Stripe', 100);
            break;
        case 'sendgrid':
            form.integration_type.value = 'email_service';
            form.integration_type.dispatchEvent(new Event('change'));
            setTimeout(() => form.provider_name.value = 'SendGrid', 100);
            break;
        case 'twilio':
            form.integration_type.value = 'sms_service';
            form.integration_type.dispatchEvent(new Event('change'));
            setTimeout(() => form.provider_name.value = 'Twilio', 100);
            break;
    }
}
</script>

<style>
.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.badge-success { background: #d1f2eb; color: #0f5132; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-warning { background: #fff3cd; color: #664d03; }
.badge-primary { background: #cff4fc; color: #055160; }
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
