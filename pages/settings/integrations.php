<?php
/*****************************************************************
 * pages/settings/integrations.php
 * ---------------------------------------------------------------
 * Integration management with multiple providers
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_admin();

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
        redirect('integrations.php');
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
        redirect('integrations.php');
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

<style>
.integrations-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    min-height: 100vh;
}

.integrations-header {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(14, 165, 233, 0.3);
}

.integrations-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.integrations-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.integrations-content {
    display: grid;
    gap: 2rem;
}

.integrations-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.card-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.form-select, .form-input, .form-textarea {
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s ease;
    background: white;
}

.form-select:focus, .form-input:focus, .form-textarea:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #0ea5e9;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.875rem;
}

.btn-primary {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px -8px rgba(14, 165, 233, 0.4);
}

.btn-secondary {
    background: #f8fafc;
    color: #475569;
    border: 2px solid #e2e8f0;
}

.btn-secondary:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.75rem;
}

.btn-outline {
    background: transparent;
    color: #0ea5e9;
    border: 2px solid #0ea5e9;
}

.btn-outline:hover {
    background: #0ea5e9;
    color: white;
}

.config-section {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.config-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: end;
}

.config-row input {
    flex: 1;
}

.integrations-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
}

.integrations-table th {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e2e8f0;
}

.integrations-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}

.integrations-table tbody tr:hover {
    background: #f8fafc;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.badge-success {
    background: #dcfce7;
    color: #166534;
}

.badge-danger {
    background: #fecaca;
    color: #991b1b;
}

.badge-warning {
    background: #fef3c7;
    color: #92400e;
}

.badge-primary {
    background: #dbeafe;
    color: #1e40af;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.template-card {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.2s ease;
}

.template-card:hover {
    border-color: #0ea5e9;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px -8px rgba(14, 165, 233, 0.3);
}

.template-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.template-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.template-desc {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 1.5rem;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #64748b;
}

.empty-state-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .integrations-main {
        padding: 0 1rem 2rem;
    }
    
    .integrations-header {
        margin: 0 -1rem 1.5rem;
        padding: 1.5rem;
    }
    
    .integrations-title {
        font-size: 2rem;
    }
    
    .integrations-card {
        padding: 1.5rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .config-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .templates-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="integrations-main">
    <div class="integrations-header">
        <h1 class="integrations-title">
            <span>🔗</span>
            Integration & API Settings
        </h1>
        <p class="integrations-subtitle">
            Connect external services and APIs to enhance your clinic's functionality
        </p>
    </div>

    <?= get_flash(); ?>

    <div class="integrations-content">

        <!-- Add New Integration -->
        <div class="integrations-card">
            <div class="card-header">
                <div class="card-icon">➕</div>
                <h3 class="card-title">Add New Integration</h3>
            </div>
            
            <form method="post" id="integrationForm">
                <input type="hidden" name="action" value="save_integration">
                
                <div class="form-grid">
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

                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="form-label">Webhook URL</label>
                        <input type="url" name="webhook_url" class="form-input" 
                               placeholder="https://your-domain.com/webhook/endpoint">
                    </div>

                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="is_active" value="1">
                            <span>Enable Integration</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="test_mode" value="1" checked>
                            <span>Test Mode</span>
                        </label>
                    </div>
                </div>

                <!-- Additional Configuration -->
                <div class="config-section">
                    <label class="form-label">Additional Configuration</label>
                    <div id="configContainer">
                        <div class="config-row">
                            <input type="text" name="config_key[]" class="form-input" placeholder="Configuration Key">
                            <input type="text" name="config_value[]" class="form-input" placeholder="Configuration Value">
                            <button type="button" onclick="addConfigRow()" class="btn btn-sm btn-outline">
                                ➕ Add
                            </button>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <span>💾</span>
                        Save Integration
                    </button>
                    <button type="button" onclick="document.getElementById('integrationForm').reset()" class="btn btn-secondary">
                        <span>🔄</span>
                        Reset Form
                    </button>
                </div>
            </form>
        </div>
            </div>
        </form>
    </div>

        <!-- Existing Integrations -->
        <div class="integrations-card">
            <div class="card-header">
                <div class="card-icon">📋</div>
                <h3 class="card-title">Configured Integrations</h3>
            </div>
            
            <?php if ($integrations->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔌</div>
                    <h3>No integrations configured</h3>
                    <p>Start by adding your first integration to connect external services.</p>
                </div>
            <?php else: ?>
                <table class="integrations-table">
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
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span>
                                            <?php
                                            $type_icons = [
                                                'payment_gateway' => '💳',
                                                'email_service' => '📧',
                                                'sms_service' => '📱',
                                                'calendar_sync' => '📅',
                                                'backup_service' => '💾'
                                            ];
                                            echo $type_icons[$integration['integration_type']] ?? '🔗';
                                            ?>
                                        </span>
                                        <span><?= ucfirst(str_replace('_', ' ', $integration['integration_type'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($integration['provider_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $integration['is_active'] ? 'success' : 'danger'; ?>">
                                        <?= $integration['is_active'] ? '✅ Active' : '❌ Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?= $integration['test_mode'] ? 'warning' : 'primary'; ?>">
                                        <?= $integration['test_mode'] ? '🧪 Test' : '🚀 Live'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?= date('M d, Y', strtotime($integration['updated_at'])); ?>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="test_integration">
                                        <input type="hidden" name="setting_id" value="<?= $integration['setting_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline">
                                            🧪 Test
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Integration Templates -->
        <div class="integrations-card">
            <div class="card-header">
                <div class="card-icon">📚</div>
                <h3 class="card-title">Popular Integration Templates</h3>
            </div>
            
            <div class="templates-grid">
                <div class="template-card">
                    <div class="template-icon">💳</div>
                    <div class="template-title">Stripe Payment</div>
                    <div class="template-desc">Accept credit card payments securely</div>
                    <button onclick="useTemplate('stripe')" class="btn btn-primary">
                        Use Template
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-icon">📧</div>
                    <div class="template-title">SendGrid Email</div>
                    <div class="template-desc">Send appointment reminders and notifications</div>
                    <button onclick="useTemplate('sendgrid')" class="btn btn-primary">
                        Use Template
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-icon">📱</div>
                    <div class="template-title">Twilio SMS</div>
                    <div class="template-desc">SMS notifications and confirmations</div>
                    <button onclick="useTemplate('twilio')" class="btn btn-primary">
                        Use Template
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-icon">📅</div>
                    <div class="template-title">Google Calendar</div>
                    <div class="template-desc">Sync appointments with Google Calendar</div>
                    <button onclick="useTemplate('google_calendar')" class="btn btn-primary">
                        Use Template
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-icon">💾</div>
                    <div class="template-title">AWS S3 Backup</div>
                    <div class="template-desc">Automated cloud backup solutions</div>
                    <button onclick="useTemplate('aws_s3')" class="btn btn-primary">
                        Use Template
                    </button>
                </div>
                
                <div class="template-card">
                    <div class="template-icon">💰</div>
                    <div class="template-title">PayPal</div>
                    <div class="template-desc">Alternative payment processing</div>
                    <button onclick="useTemplate('paypal')" class="btn btn-primary">
                        Use Template
                    </button>
                </div>
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
    newRow.className = 'config-row';
    newRow.innerHTML = `
        <input type="text" name="config_key[]" class="form-input" placeholder="Configuration Key">
        <input type="text" name="config_value[]" class="form-input" placeholder="Configuration Value">
        <button type="button" onclick="this.parentElement.remove()" class="btn btn-sm btn-secondary">
            ❌ Remove
        </button>
    `;
    container.appendChild(newRow);
}

function useTemplate(template) {
    const form = document.getElementById('integrationForm');
    
    // Clear form first
    form.reset();
    
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
        case 'google_calendar':
            form.integration_type.value = 'calendar_sync';
            form.integration_type.dispatchEvent(new Event('change'));
            setTimeout(() => form.provider_name.value = 'Google Calendar', 100);
            break;
        case 'aws_s3':
            form.integration_type.value = 'backup_service';
            form.integration_type.dispatchEvent(new Event('change'));
            setTimeout(() => form.provider_name.value = 'AWS S3', 100);
            break;
        case 'paypal':
            form.integration_type.value = 'payment_gateway';
            form.integration_type.dispatchEvent(new Event('change'));
            setTimeout(() => form.provider_name.value = 'PayPal', 100);
            break;
    }
    
    // Scroll to form
    form.scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php include BASE_PATH . '/templates/footer.php'; ?>
