<?php
/*****************************************************************
 * pages/patients/my_profile.php
 * Patient Profile Management
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

require_login();
require_patient(); // Only patients can access their profile

$user_id = $_SESSION['user_id'];

// Get patient information
$patient_query = $conn->query("
    SELECT p.*, u.email, u.created_at as account_created
    FROM patient p
    JOIN usertbl u ON u.email = p.email
    WHERE u.user_id = $user_id
    LIMIT 1
");

$patient = $patient_query->fetch_assoc();

if (!$patient) {
    flash('Patient profile not found.');
    redirect('/dentosys/auth/logout.php');
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $conn->real_escape_string(trim($_POST['first_name']));
    $lastName = $conn->real_escape_string(trim($_POST['last_name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $address = $conn->real_escape_string(trim($_POST['address']));
    $dob = $_POST['dob'];
    
    $update_query = "UPDATE patient SET 
        first_name = '$firstName',
        last_name = '$lastName', 
        phone = '$phone',
        address = '$address',
        dob = '$dob'
        WHERE patient_id = {$patient['patient_id']}";
    
    if ($conn->query($update_query)) {
        flash('Profile updated successfully!');
        redirect($_SERVER['PHP_SELF']);
    } else {
        flash('Error updating profile: ' . $conn->error);
    }
}

$pageTitle = 'My Profile';
include BASE_PATH . '/templates/header.php';
?>

<div class="main-wrapper patient-page full-width">
  <?php include BASE_PATH . '/templates/sidebar.php'; ?>
  
  <main class="content">
    <div class="page-container">
      <header class="content-header">
        <h1>👤 My Profile</h1>
        <p class="subtitle">Manage your personal information</p>
      </header>

    <?= get_flash(); ?>

    <div class="card">
      <div class="card-header">
        <h3>Personal Information</h3>
        <p>Keep your information up to date for better service</p>
      </div>
      
      <div class="card-body">
        <form method="post" class="form-grid">
          <div class="form-row">
            <div class="form-group">
              <label for="first_name">First Name</label>
              <input type="text" id="first_name" name="first_name" 
                     value="<?= htmlspecialchars($patient['first_name']) ?>" required>
            </div>
            
            <div class="form-group">
              <label for="last_name">Last Name</label>
              <input type="text" id="last_name" name="last_name" 
                     value="<?= htmlspecialchars($patient['last_name']) ?>" required>
            </div>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" value="<?= htmlspecialchars($patient['email']) ?>" 
                     disabled style="background: #f5f5f5;">
              <small>Email cannot be changed. Contact support if needed.</small>
            </div>
            
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" 
                     value="<?= htmlspecialchars($patient['phone']) ?>">
            </div>
          </div>
          
          <div class="form-group">
            <label for="dob">Date of Birth</label>
            <input type="date" id="dob" name="dob" 
                   value="<?= $patient['dob'] ?>">
          </div>
          
          <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="3"><?= htmlspecialchars($patient['address']) ?></textarea>
          </div>
          
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Profile</button>
          </div>
        </form>
      </div>
    </div>
    
    <div class="card mt-4">
      <div class="card-header">
        <h3>Account Information</h3>
      </div>
      
      <div class="card-body">
        <div class="info-grid">
          <div class="info-item">
            <strong>Patient ID:</strong>
            <span><?= $patient['patient_id'] ?></span>
          </div>
          
          <div class="info-item">
            <strong>Account Created:</strong>
            <span><?= date('F j, Y', strtotime($patient['account_created'])) ?></span>
          </div>
          
          <div class="info-item">
            <strong>Profile Created:</strong>
            <span><?= date('F j, Y', strtotime($patient['created_at'])) ?></span>
          </div>
        </div>
      </div>
    </div>
    </div>
  </main>
</div>

<style>
/* Full-width layout for patient profile page */
.patient-page.full-width .content {
  padding: 0;
  width: 100%;
}

.patient-page .page-container {
  width: 100%;
  padding: 2rem;
  margin: 0;
}

.patient-page .content-header {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: white;
  padding: 2.5rem 2rem;
  margin-bottom: 2rem;
  border-radius: 0;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.patient-page .content-header h1 {
  font-size: 2.25rem;
  font-weight: 700;
  margin: 0 0 0.5rem;
}

.patient-page .content-header .subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
  margin: 0;
}

.patient-page .card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
  margin-bottom: 2rem;
  width: 100%;
}

.patient-page .card-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  background-color: #f8fafc;
}

.patient-page .card-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem;
  color: #1e293b;
}

.patient-page .card-header p {
  margin: 0;
  color: #64748b;
}

.patient-page .card-body {
  padding: 2rem;
}

.form-grid {
  width: 100%;
  max-width: 800px;
  margin: 0 auto;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #374151;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-group small {
  display: block;
  margin-top: 0.25rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.form-actions {
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: center;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.info-item {
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  border-left: 4px solid #4f46e5;
}

.info-item strong {
  display: block;
  margin-bottom: 0.25rem;
  color: #374151;
}

.info-item span {
  color: #6b7280;
}

.mt-4 {
  margin-top: 2rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  line-height: 1;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all .2s ease;
}

.btn-primary {
  color: #fff;
  background-color: #4f46e5;
  border-color: #4f46e5;
}

.btn-primary:hover {
  background-color: #4338ca;
  border-color: #4338ca;
  transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .patient-page .page-container {
    padding: 1rem;
  }
  
  .patient-page .content-header {
    padding: 2rem 1rem;
  }
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
