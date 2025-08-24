<?php
/*****************************************************************
 * pages/communications/templates.php
 * ---------------------------------------------------------------
 * Manage message templates (Email, SMS, etc.)
 *   • Admin (role_id = 1) and Dentist (role_id = 2) may access
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();

/* --- Role gate --- */
$allowed = [1, 2];              // Admin & Dentist
if (!in_array((int)($_SESSION['role'] ?? 0), $allowed, true)) {
    flash('Access denied.');
    redirect('/dentosys/index.php');
}

/* ---------- Save new template ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $channel = $conn->real_escape_string($_POST['channel']);
    $title   = $conn->real_escape_string($_POST['title']);
    $body    = $conn->real_escape_string($_POST['body']);

    if ($title && $body) {
        $stmt = $conn->prepare(
          "INSERT INTO MessageTemplate (channel,title,body) VALUES (?,?,?)"
        );
        $stmt->bind_param('sss', $channel, $title, $body);
        $stmt->execute();
        flash('Template saved.');
    } else {
        flash('Title and body are required.','error');
    }
    redirect('templates.php');
}

/* ---------- Filters ---------- */
$where = [];
if (!empty($_GET['channel'])) {
    $ch = $conn->real_escape_string($_GET['channel']);
    $where[] = "channel = '$ch'";
}
if (!empty($_GET['q'])) {
    $q = $conn->real_escape_string($_GET['q']);
    $where[] = "(title LIKE '%$q%' OR body LIKE '%$q%')";
}
$whereSQL = $where ? 'WHERE '.implode(' AND ', $where) : '';

$templates = $conn->query(
  "SELECT * FROM MessageTemplate $whereSQL ORDER BY template_id DESC"
);

include BASE_PATH . '/templates/header.php';
include BASE_PATH . '/templates/sidebar.php';
?>

<style>
.templates-main {
    padding: 0 2rem 3rem;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    min-height: 100vh;
}

.templates-header {
    background: linear-gradient(135deg, #0ea5e9, #0284c7);
    margin: 0 -2rem 2rem;
    padding: 2rem 2rem 2.5rem;
    color: white;
    border-radius: 0 0 24px 24px;
    box-shadow: 0 8px 32px -8px rgba(14, 165, 233, 0.3);
}

.templates-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.templates-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.templates-content {
    display: grid;
    gap: 2rem;
}

.templates-card {
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

.form-textarea {
    resize: vertical;
    min-height: 120px;
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

.btn-outline {
    background: transparent;
    color: #0ea5e9;
    border: 2px solid #0ea5e9;
}

.btn-outline:hover {
    background: #0ea5e9;
    color: white;
}

.filters-section {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.filters-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filters-form {
    display: grid;
    grid-template-columns: 200px 1fr auto auto;
    gap: 1rem;
    align-items: end;
}

.templates-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.1);
}

.templates-table th {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e2e8f0;
}

.templates-table td {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
}

.templates-table tbody tr:hover {
    background: #f8fafc;
}

.template-id {
    font-weight: 600;
    color: #0ea5e9;
}

.channel-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.channel-email { background: #dbeafe; color: #1e40af; }
.channel-sms { background: #dcfce7; color: #166534; }
.channel-letter { background: #fef3c7; color: #92400e; }
.channel-postcard { background: #fce7f3; color: #be185d; }
.channel-flow { background: #e0e7ff; color: #3730a3; }

.template-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.25rem;
}

.template-snippet {
    color: #64748b;
    font-size: 0.875rem;
    line-height: 1.4;
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

.collapsible-card {
    margin-bottom: 2rem;
}

.collapsible-header {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.2s ease;
    font-weight: 600;
    color: #374151;
}

.collapsible-header:hover {
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    border-color: #cbd5e1;
}

.collapsible-content {
    border: 2px solid #e2e8f0;
    border-top: none;
    border-radius: 0 0 12px 12px;
    padding: 2rem;
    background: white;
    display: none;
}

.collapsible-content.open {
    display: block;
}

.toggle-icon {
    transition: transform 0.2s ease;
}

.toggle-icon.open {
    transform: rotate(180deg);
}

@media (max-width: 768px) {
    .templates-main {
        padding: 0 1rem 2rem;
    }
    
    .templates-header {
        margin: 0 -1rem 1.5rem;
        padding: 1.5rem;
    }
    
    .templates-title {
        font-size: 2rem;
    }
    
    .filters-form {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .templates-table {
        font-size: 0.875rem;
    }
    
    .templates-table th,
    .templates-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle collapsible sections
    const collapsibleHeaders = document.querySelectorAll('.collapsible-header');
    
    collapsibleHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const icon = this.querySelector('.toggle-icon');
            
            if (content.classList.contains('open')) {
                content.classList.remove('open');
                icon.classList.remove('open');
            } else {
                content.classList.add('open');
                icon.classList.add('open');
            }
        });
    });
    
    // Auto-resize textarea
    const textarea = document.querySelector('.form-textarea');
    if (textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
});
</script>

<main class="templates-main">
    <div class="templates-header">
        <h1 class="templates-title">
            <span>📧</span>
            Communication Templates
        </h1>
        <p class="templates-subtitle">
            Create and manage reusable templates for emails, SMS, letters, and other communications
        </p>
        <div style="margin-top: 1rem; display: flex; gap: 0.75rem;">
            <a href="/dentosys/pages/communications/feedback.php" class="btn btn-secondary" title="Review patient feedback">
                <span>📝</span>
                Review Feedback
            </a>
        </div>
    </div>

    <?= get_flash(); ?>

    <div class="templates-content">
        <!-- New Template Form -->
        <div class="collapsible-card">
            <div class="collapsible-header">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 1.25rem;">➕</span>
                    <span>Create New Template</span>
                </div>
                <span class="toggle-icon">🔽</span>
            </div>
            <div class="collapsible-content">
                <form method="post" class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Communication Channel</label>
                        <select name="channel" class="form-select" required>
                            <?php foreach (['Email','SMS','Letter','Postcard','Flow'] as $c): ?>
                                <option value="<?= $c; ?>"><?= $c; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Template Title</label>
                        <input type="text" name="title" class="form-input" 
                               placeholder="Enter a descriptive title for this template" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Template Content</label>
                        <textarea name="body" class="form-textarea" 
                                  placeholder="Enter the template content here..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span>💾</span>
                        Save Template
                    </button>
                </form>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="templates-card">
            <div class="card-header">
                <div class="card-icon">🔍</div>
                <h3 class="card-title">Filter Templates</h3>
            </div>
            
            <form method="get" class="filters-form">
                <div class="form-group">
                    <label class="form-label">Channel</label>
                    <select name="channel" class="form-select">
                        <option value="">All Channels</option>
                        <?php foreach (['Email','SMS','Letter','Postcard','Flow'] as $c): ?>
                            <option value="<?= $c; ?>"
                                <?= (!empty($_GET['channel']) && $_GET['channel']===$c)?'selected':''; ?>>
                                <?= $c; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-input" 
                           placeholder="Search titles and content..."
                           value="<?= htmlspecialchars($_GET['q'] ?? ''); ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <span>🔍</span>
                    Filter
                </button>
                
                <a href="templates.php" class="btn btn-secondary">
                    <span>🔄</span>
                    Reset
                </a>
            </form>
        </div>

        <!-- Templates List -->
        <div class="templates-card">
            <div class="card-header">
                <div class="card-icon">📋</div>
                <h3 class="card-title">Templates Library</h3>
            </div>
            
            <?php if ($templates->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>No templates found</h3>
                    <p>Create your first template or adjust your filters to see existing templates.</p>
                </div>
            <?php else: ?>
                <table class="templates-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 120px;">Channel</th>
                            <th style="width: 200px;">Title</th>
                            <th>Content Preview</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($t = $templates->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="template-id">#<?= $t['template_id']; ?></span>
                                </td>
                                <td>
                                    <span class="channel-badge channel-<?= strtolower($t['channel']); ?>">
                                        <?= $t['channel']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="template-title">
                                        <?= htmlspecialchars($t['title']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="template-snippet">
                                        <?= htmlspecialchars(mb_strimwidth($t['body'], 0, 100, '...')); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button class="btn btn-outline" style="padding: 0.5rem; font-size: 0.75rem;"
                                                onclick="viewTemplate(<?= $t['template_id']; ?>, '<?= htmlspecialchars($t['title'], ENT_QUOTES); ?>', '<?= htmlspecialchars($t['body'], ENT_QUOTES); ?>')">
                                            👁️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Template Viewer Modal -->
<div id="templateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
     background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 2rem; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0; color: #1e293b;">Template Details</h3>
            <button onclick="closeModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">❌</button>
        </div>
        <div id="modalContent">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>
</div>

<script>
function viewTemplate(id, title, body) {
    const modal = document.getElementById('templateModal');
    const content = document.getElementById('modalContent');
    
    content.innerHTML = `
        <div style="margin-bottom: 1rem;">
            <strong style="color: #374151;">Template ID:</strong> #${id}
        </div>
        <div style="margin-bottom: 1rem;">
            <strong style="color: #374151;">Title:</strong> ${title}
        </div>
        <div style="margin-bottom: 1rem;">
            <strong style="color: #374151;">Content:</strong>
        </div>
        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; white-space: pre-wrap; font-family: monospace; font-size: 0.875rem;">
            ${body}
        </div>
    `;
    
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('templateModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('templateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
<?php include BASE_PATH . '/templates/footer.php'; ?>