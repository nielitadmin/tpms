<?php
session_start();
require __DIR__ . '/../includes/config.php'; 

$message = '';
$messageType = '';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $center_id = trim($_POST['center_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($center_id) || !preg_match('/^[A-Z]{2}\d{3}$/', $center_id)) {
        $errors[] = "Invalid Center ID format (e.g., OD001)";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }

    if (empty($errors)) {
        // Hash password and save to database (implement your DB logic here)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // TODO: Insert into pending_registrations table
        // $stmt = $pdo->prepare("INSERT INTO pending_registrations (center_id, password_hash, data, created_at) VALUES (?, ?, ?, NOW())");
        // $stmt->execute([$center_id, $hashed_password, json_encode($_POST), $center_id]);
        
        $_SESSION['registration_pending'] = $center_id;
        $message = "Registration request sent successfully! Waiting for Admin approval.";
        $messageType = "success";
    } else {
        $message = implode('<br>', $errors);
        $messageType = "danger";
    }
}

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete TP Registration - NIELIT TPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --danger-color: #dc3545;
        }
        
        body { 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .registration-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .step-indicator {
            width: 45px; height: 45px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 1.1rem;
            background: linear-gradient(145deg, #f0f0f0, #e6e6e6);
            border: 3px solid #e9ecef; color: #6c757d;
            margin: 0 auto; position: relative; transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 50%; left: -25px; width: 25px; height: 2px;
            background: #e9ecef; z-index: -1;
        }
        .step-indicator:first-child::before { display: none; }
        
        .step-active { 
            background: linear-gradient(145deg, var(--primary-color), #0b5ed7);
            color: white; border-color: var(--primary-color);
            transform: scale(1.05); box-shadow: 0 6px 20px rgba(13,110,253,0.4);
        }
        
        .step-completed { 
            background: linear-gradient(145deg, var(--success-color), #157347);
            color: white; border-color: var(--success-color);
            box-shadow: 0 6px 20px rgba(25,135,84,0.4);
        }
        
        .step-label { 
            font-size: 0.85rem; margin-top: 10px; text-align: center; 
            color: #6c757d; font-weight: 500; transition: color 0.3s ease;
        }
        .active-label { color: var(--primary-color); font-weight: 600; }
        
        .form-step { display: none; }
        .form-step.active { 
            display: block; 
            animation: slideInUp 0.5s ease-out; 
        }
        
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .input-group-text {
            background: linear-gradient(145deg, #f8f9fa, #e9ecef);
            border: 1px solid #dee2e6;
        }
        
        .file-input-wrapper {
            position: relative; overflow: hidden; display: inline-block;
            cursor: pointer; transition: all 0.3s ease;
        }
        
        .file-input-wrapper:hover { transform: translateY(-2px); }
        .file-input-wrapper input[type=file] { 
            position: absolute; left: -9999px; 
        }
        
        .progress-step {
            height: 5px; top: 22px; position: absolute; 
            width: 90%; left: 5%; z-index: 1; border-radius: 3px;
        }
        
        @media (max-width: 768px) {
            .step-indicator::before { display: none; }
            .step-indicator { width: 40px; height: 40px; font-size: 1rem; }
        }
    </style>
</head>
<body class="py-4 py-md-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-xl-10">
                <div class="card registration-card shadow-lg border-0 rounded-4 p-0">
                    <div class="card-header bg-gradient text-white text-center py-4 position-relative overflow-hidden">
                        <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10 bg-dark"></div>
                        <i class="fas fa-building-shield fa-3x mb-3 d-block"></i>
                        <h3 class="mb-1 fw-bold">NIELIT TPS - Center Registration</h3>
                        <p class="mb-0 opacity-90">Complete 5-step registration process</p>
                    </div>
                    
                    <div class="card-body p-4 p-md-5">
                        <?php if($message): ?>
                            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                                <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
                                <?= $message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Progress Steps -->
                        <div class="position-relative mb-5 px-3">
                            <div class="progress progress-step bg-light">
                                <div class="progress-bar bg-gradient" id="progressBar" style="width: 0%; transition: width 0.5s ease;"></div>
                            </div>
                            <div class="d-flex justify-content-between position-relative" style="z-index: 2;">
                                <div><div class="step-indicator step-active" id="ind-1"><i class="fas fa-user"></i></div><div class="step-label active-label" id="lbl-1">Account</div></div>
                                <div><div class="step-indicator" id="ind-2">2</div><div class="step-label" id="lbl-2">Faculty</div></div>
                                <div><div class="step-indicator" id="ind-3">3</div><div class="step-label" id="lbl-3">Financials</div></div>
                                <div><div class="step-indicator" id="ind-4">4</div><div class="step-label" id="lbl-4">Documents</div></div>
                                <div><div class="step-indicator" id="ind-5">5</div><div class="step-label" id="lbl-5">Submit</div></div>
                            </div>
                        </div>

                        <form id="signupForm" method="POST" action="" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            
                            <!-- Step 1: Account -->
                            <div class="form-step active" id="step-0">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-primary">
                                    <div class="step-indicator step-active me-3 flex-shrink-0">1</div>
                                    <h5 class="mb-0 fw-bold text-primary">Account Setup & Legal Status</h5>
                                </div>
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Center ID <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                            <input type="text" name="center_id" class="form-control" 
                                                   pattern="[A-Z]{2}\d{3}" maxlength="5" required 
                                                   placeholder="e.g. OD001" title="Format: XX### (e.g., OD001)">
                                        </div>
                                        <div class="form-text">2 letters + 3 digits</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" name="password" class="form-control" 
                                                   minlength="8" required>
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Minimum 8 characters</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Legal Status <span class="text-danger">*</span></label>
                                        <select name="legal_status" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <option value="proprietorship">Proprietorship Concern</option>
                                            <option value="partnership">Partnership Firm</option>
                                            <option value="society">Society/NGO</option>
                                            <option value="trust">Trust</option>
                                            <option value="company">Private/Govt Company</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Signatory Name <span class="text-danger">*</span></label>
                                        <input type="text" name="sig_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Mobile <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">+91</span>
                                            <input type="tel" name="sig_mobile" class="form-control" 
                                                   pattern="[6-9]\d{9}" maxlength="10" required>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Email (Login ID) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" name="sig_email" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 2: Faculty -->
                            <div class="form-step" id="step-1">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-primary">
                                    <div class="step-indicator step-active me-3 flex-shrink-0">2</div>
                                    <h5 class="mb-0 fw-bold text-primary">Faculty & Infrastructure</h5>
                                </div>
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Carpet Area (Sq ft) <span class="text-danger">*</span></label>
                                        <input type="number" name="infra_area" class="form-control" min="500" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Computer Labs <span class="text-danger">*</span></label>
                                        <input type="number" name="infra_labs" class="form-control" min="1" max="10" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Premise Type <span class="text-danger">*</span></label>
                                        <select name="premise_type" class="form-select" required>
                                            <option value="owned">Owned</option>
                                            <option value="rented">Rented</option>
                                            <option value="leased">Leased</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <h6 class="text-primary fw-bold mb-3"><i class="fas fa-users me-2"></i>Primary Faculty Details</h6>
                                <div id="facultyContainer">
                                    <div class="faculty-row row g-3 mb-3 p-3 border rounded bg-light">
                                        <div class="col-md-5">
                                            <input type="text" name="fac_name[]" class="form-control" placeholder="Faculty Name" required>
                                        </div>
                                        <div class="col-md-5">
                                            <input type="text" name="fac_qual[]" class="form-control" placeholder="Qualification (e.g., OSCIT)" required>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger w-100 remove-faculty" style="display:none;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary mb-4" id="addFaculty">
                                    <i class="fas fa-plus me-1"></i>Add More Faculty
                                </button>
                            </div>

                            <!-- Step 3: Financials -->
                            <div class="form-step" id="step-2">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-primary">
                                    <div class="step-indicator step-active me-3 flex-shrink-0">3</div>
                                    <h5 class="mb-0 fw-bold text-primary">Financial Details (Sec 14)</h5>
                                </div>
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Financial Year Ending <span class="text-danger">*</span></label>
                                        <input type="number" name="fin_year" class="form-control" min="2020" max="2030" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Annual Turnover (₹ Lakh) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" name="fin_turnover" class="form-control" step="0.01" min="0" required>
                                            <span class="input-group-text">Lakh</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Students Placed <span class="text-danger">*</span></label>
                                        <input type="number" name="fin_placed" class="form-control" min="0" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Documents -->
                            <div class="form-step" id="step-3">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-primary">
                                    <div class="step-indicator step-active me-3 flex-shrink-0">4</div>
                                    <h5 class="mb-0 fw-bold text-primary">Document Upload (Sec 17)</h5>
                                </div>
                                
                                <div class="alert alert-warning border-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Upload PDF/JPG/PNG files only. Max size: 5MB per file.
                                </div>
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">1. Authorized ID Proof <span class="text-danger">*</span></label>
                                        <div class="file-input-wrapper">
                                            <input type="file" name="doc_id" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <label class="form-control text-center p-3">
                                                <i class="fas fa-id-card fa-2x mb-2 d-block text-muted"></i>
                                                <div class="fw-bold">Choose ID Proof</div>
                                                <small class="text-muted">PDF/JPG/PNG (Max 5MB)</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">2. Signatory Signature <span class="text-danger">*</span></label>
                                        <div class="file-input-wrapper">
                                            <input type="file" name="doc_sig" class="form-control" accept=".jpg,.jpeg,.png" required>
                                            <label class="form-control text-center p-3">
                                                <i class="fas fa-signature fa-2x mb-2 d-block text-muted"></i>
                                                <div class="fw-bold">Upload Signature</div>
                                                <small class="text-muted">JPG/PNG (Max 2MB)</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">3. Layout Map <span class="text-danger">*</span></label>
                                        <div class="file-input-wrapper">
                                            <input type="file" name="doc_map" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <label class="form-control text-center p-3">
                                                <i class="fas fa-map fa-2x mb-2 d-block text-muted"></i>
                                                <div class="fw-bold">Upload Layout</div>
                                                <small class="text-muted">PDF/JPG/PNG (Max 5MB)</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">4. Building Photos <span class="text-danger">*</span></label>
                                        <div class="file-input-wrapper">
                                            <input type="file" name="doc_photos[]" class="form-control" accept="image/*" multiple required>
                                            <label class="form-control text-center p-3">
                                                <i class="fas fa-camera fa-2x mb-2 d-block text-muted"></i>
                                                <div class="fw-bold">Upload Photos</div>
                                                <small class="text-muted">Multiple JPG/PNG (Max 10MB total)</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 5: Review -->
                            <div class="form-step" id="step-4">
                                <div class="d-flex align-items-center mb-4 pb-3 border-bottom border-primary">
                                    <div class="step-indicator step-active me-3 flex-shrink-0">5</div>
                                    <h5 class="mb-0 fw-bold text-primary">Review & Submit</h5>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6 class="mb-2"><i class="fas fa-check-double me-2"></i>Review your information</h6>
                                    <ul class="mb-0">
                                        <li>All fields marked with <span class="text-danger">*</span> are mandatory</li>
                                        <li>Uploaded documents cannot be changed after submission</li>
                                        <li>Admin approval required (typically 3-5 working days)</li>
                                    </ul>
                                </div>
                                
                                <div class="form-check mb-4 p-4 border rounded bg-light">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label fw-semibold" for="terms">
                                        <i class="fas fa-shield-alt text-primary me-2"></i>
                                        I confirm that all information and documents provided are authentic and 
                                        comply with NIELIT TPS guidelines and standards.
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold py-3 shadow-lg" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Submit Registration Application
                                </button>
                            </div>

                            <!-- Navigation -->
                            <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
                                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-bold" id="prevBtn" onclick="nextPrev(-1)" style="display: none;">
                                    <i class="fas fa-arrow-left me-2"></i>Back
                                </button>
                                <div class="text-muted small">
                                    Step <span id="currentStep">1</span> of 5
                                </div>
                                <button type="button" class="btn btn-primary px-4 py-2 fw-bold ms-auto" id="nextBtn" onclick="nextPrev(1)">
                                    Next Step <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4 pt-3 border-top">
                            <a href="../login.php" class="btn btn-link btn-lg p-0 text-decoration-none fw-bold">
                                <i class="fas fa-sign-in-alt me-2"></i>Already registered? Login here
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let currentStep = 0;
        const totalSteps = 5;
        const steps = document.querySelectorAll(".form-step");
        
        // Initialize
        showStep(currentStep);
        
        // Navigation
        function showStep(n) {
            steps.forEach((step, i) => step.classList.toggle("active", i === n));
            
            document.getElementById("prevBtn").style.display = n === 0 ? "none" : "inline-flex";
            document.getElementById("nextBtn").style.display = n === totalSteps - 1 ? "none" : "inline-flex";
            document.getElementById("currentStep").textContent = n + 1;
            
            updateProgress(n);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function nextPrev(direction) {
            if (direction === 1 && !validateCurrentStep()) return;
            
            currentStep += direction;
            currentStep = Math.max(0, Math.min(totalSteps - 1, currentStep));
            showStep(currentStep);
        }
        
        function validateCurrentStep() {
            const currentStepEl = steps[currentStep];
            const inputs = currentStepEl.querySelectorAll('input[required], select[required]');
            let isValid = true;
            
            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            
            // Custom validation for password match
            if (currentStep === 0) {
                const pass1 = currentStepEl.querySelector('input[name="password"]');
                const pass2 = currentStepEl.querySelector('input[name="confirm_password"]');
                if (pass1.value && pass1.value !== pass2.value) {
                    pass2.setCustomValidity('Passwords do not match');
                    pass2.classList.add('is-invalid');
                    isValid = false;
                } else {
                    pass2.setCustomValidity('');
                }
            }
            
            return isValid;
        }
        
        // Dynamic faculty addition
        document.getElementById('addFaculty')?.addEventListener('click', function() {
            const container = document.getElementById('facultyContainer');
            const newRow = container.firstElementChild.cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelector('.remove-faculty').style.display = 'block';
            container.appendChild(newRow);
        });
        
        // Remove faculty
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-faculty')) {
                e.target.closest('.faculty-row').remove();
            }
        });
        
        // Password toggle
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const password = document.querySelector('input[name="password"]');
            const icon = this.querySelector('i');
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Progress update
        function updateProgress(n) {
            const progress = (n / (totalSteps - 1)) * 100;
            document.getElementById("progressBar").style.width = progress + "%";
            
            for (let i = 1; i <= totalSteps; i++) {
                const indicator = document.getElementById(`ind-${i}`);
                const label = document.getElementById(`lbl-${i}`);
                
                indicator.className = 'step-indicator';
                label.className = 'step-label';
                
                if (i - 1 < n) {
                    indicator.classList.add('step-completed');
                    indicator.innerHTML = '<i class="fas fa-check"></i>';
                } else if (i - 1 === n) {
                    indicator.classList.add('step-active');
                    label.classList.add('active-label');
                    indicator.innerHTML = i;
                } else {
                    indicator.innerHTML = i;
                }
            }
        }
        
        // Form submission
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            if (!validateCurrentStep()) {
                e.preventDefault();
                showStep(currentStep);
                return false;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
        });
        
        // Real-time validation
        document.addEventListener('input', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') {
                e.target.classList.toggle('is-invalid', !e.target.checkValidity());
            }
        });
    </script>
</body>
</html>