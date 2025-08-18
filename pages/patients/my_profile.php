<?php
/*****************************************************************
 * pages/patients/my_profile.php
 * Patient Profile Management
 *****************************************************************/
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once BASE_PATH . '/includes/functions.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 4) {
    flash('Access denied. Patients only.');
    redirect('/dentosys/auth/login.php');
}

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

<div class="main-wrapper">
  <?php include BASE_PATH . '/templates/sidebar.php'; ?>
  
  <main class="content">
    <header class="content-header">
      <h1>👤 My Profile</h1>
      <p>Manage your personal information</p>
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
  </main>
</div>

<style>
.form-grid {
  max-width: 600px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
  color: #374151;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.3s ease;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #0066CC;
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

.form-group small {
  display: block;
  margin-top: 4px;
  color: #6b7280;
  font-size: 14px;
}

.form-actions {
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.info-item {
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid #0066CC;
}

.info-item strong {
  display: block;
  margin-bottom: 4px;
  color: #374151;
}

.info-item span {
  color: #6b7280;
}

.mt-4 {
  margin-top: 2rem;
}
</style>

<?php include BASE_PATH . '/templates/footer.php'; ?>
