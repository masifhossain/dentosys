<?php
/*****************************************************************
 * pages/settings/clinic_info.php
 * ---------------------------------------------------------------
 * Manage basic clinic profile (name, address, phone, logo).
 * Admin-only access.
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';   // up 2 levels
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_admin();

/* ───────── Ensure settings row exists (singleton pattern) ───────── */
$conn->query("INSERT IGNORE INTO clinicinfo (id) VALUES (1)");

/* ───────── Handle POST update ───────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = $conn->real_escape_string(trim($_POST['clinic_name']));
    $addr    = $conn->real_escape_string(trim($_POST['clinic_address']));
    $phone   = $conn->real_escape_string(trim($_POST['phone']));
    $logoCol = '';       // SQL fragment if logo uploaded

    /* Handle optional logo upload */
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext  = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $safe = 'logo_' . uniqid() . ($ext ? ".$ext" : '');
        $dest = BASE_PATH . '/uploads/' . $safe;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
            $logoCol = ", logo_path = '$safe'";
        } else {
            flash('Logo upload failed (check permissions).');
        }
    }

    $sql = "UPDATE clinicinfo
            SET clinic_name    = '$name',
                clinic_address = '$addr',
                phone          = '$phone'
                $logoCol
            WHERE id = 1";
    if ($conn->query($sql)) {
        flash('Clinic info saved.');
    } else {
        flash('DB error: ' . $conn->error);
    }
    redirect('clinic_info.php');
}

/* ───────── Fetch current info ───────── */
$info = $conn->query("SELECT * FROM clinicinfo WHERE id = 1")->fetch_assoc();

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
.clinic-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    min-height: 100vh;
}

.clinic-header {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(14, 165, 233, 0.3);
}

.clinic-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.clinic-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.clinic-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
    border: 1px solid #e2e8f0;
    max-width: 800px;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
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

.form-input, .form-textarea, .form-file {
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.2s ease;
    background: white;
}

.form-input:focus, .form-textarea:focus, .form-file:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.form-file {
    padding: 0.5rem;
}

.logo-preview {
    background: #f8fafc;
    border: 2px dashed #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    margin-top: 1rem;
}

.logo-preview img {
    max-height: 120px;
    border-radius: 8px;
    box-shadow: 0 4px 12px -4px rgba(0,0,0,0.1);
}

.logo-preview-empty {
    color: #64748b;
    font-style: italic;
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

.form-hint {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.info-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    margin-bottom: 0.5rem;
}

.info-value {
    font-size: 1rem;
    color: #1e293b;
    font-weight: 500;
}

@media (max-width: 768px) {
    .clinic-main {
        padding: 0 1rem 2rem;
    }
    
    .clinic-header {
        margin: 0 -1rem 1.5rem;
        padding: 1.5rem;
    }
    
    .clinic-title {
        font-size: 2rem;
    }
    
    .clinic-card {
        padding: 1.5rem;
    }
}
</style>

<main class="clinic-main">
    <div class="clinic-header">
        <h1 class="clinic-title">
            <span>🏥</span>
            Clinic Information
        </h1>
        <p class="clinic-subtitle">
            Manage your clinic's basic profile information, contact details, and branding
        </p>
    </div>

    <?= get_flash(); ?>

    <!-- Current Information Summary -->
    <?php if (!empty($info['clinic_name'])): ?>
    <div class="clinic-card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <div class="card-icon">📋</div>
            <h3 class="card-title">Current Information</h3>
        </div>
        
        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">Clinic Name</div>
                <div class="info-value"><?= htmlspecialchars($info['clinic_name'] ?? 'Not set'); ?></div>
            </div>
            <div class="info-card">
                <div class="info-label">Phone Number</div>
                <div class="info-value"><?= htmlspecialchars($info['phone'] ?? 'Not set'); ?></div>
            </div>
            <div class="info-card" style="grid-column: 1 / -1;">
                <div class="info-label">Address</div>
                <div class="info-value"><?= nl2br(htmlspecialchars($info['clinic_address'] ?? 'Not set')); ?></div>
            </div>
        </div>
        
        <?php if (!empty($info['logo_path'])): ?>
        <div class="logo-preview">
            <div class="info-label" style="margin-bottom: 1rem;">Current Logo</div>
            <img src="/dentosys/uploads/<?= urlencode($info['logo_path']); ?>" alt="Clinic Logo">
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="clinic-card">
        <div class="card-header">
            <div class="card-icon">✏️</div>
            <h3 class="card-title">Update Clinic Information</h3>
        </div>
        
        <form method="post" enctype="multipart/form-data" class="form-grid">
            <div class="form-group">
                <label class="form-label">Clinic Name *</label>
                <input type="text" name="clinic_name" class="form-input"
                       value="<?= htmlspecialchars($info['clinic_name'] ?? ''); ?>" 
                       placeholder="Enter your clinic name" required>
                <div class="form-hint">This will appear on documents and communications</div>
            </div>

            <div class="form-group">
                <label class="form-label">Clinic Address *</label>
                <textarea name="clinic_address" class="form-textarea"
                          placeholder="Enter complete clinic address including city, state, and postal code" 
                          required><?= htmlspecialchars($info['clinic_address'] ?? ''); ?></textarea>
                <div class="form-hint">Include street address, city, state/province, and postal code</div>
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-input"
                       value="<?= htmlspecialchars($info['phone'] ?? ''); ?>"
                       placeholder="(555) 123-4567">
                <div class="form-hint">Main contact number for the clinic</div>
            </div>

            <div class="form-group">
                <label class="form-label">Clinic Logo</label>
                <input type="file" name="logo" class="form-file" 
                       accept=".jpg,.jpeg,.png">
                <div class="form-hint">
                    Upload JPG or PNG format, maximum 500 KB. Recommended size: 200x200 pixels
                </div>
            </div>

            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary">
                    <span>💾</span>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</main>
<?php include BASE_PATH . '/templates/footer.php'; ?>