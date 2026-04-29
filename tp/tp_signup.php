<?php
session_start();
// require __DIR__ . '/../includes/config.php'; // Uncomment for DB connection

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic Account Details
    $center_id = $_POST['center_id'];
    $email = $_POST['sig_email']; // Using Signatory Email as login email
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Yahan par aap baaki saare POST variables aur Files ko receive karenge
    // Jaise: $sig_name = $_POST['sig_name'];
    // Jaise: $fac_name = $_POST['fac_name']; (Yeh array hoga)

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "danger"; 
    } else {
        /*
        // --- Database Logic (Commented for testing design) ---
        // 1. Check if email or center_id exists
        // 2. Hash password: $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // 3. Move all uploaded documents using move_uploaded_file()
        // 4. Insert data into Users table, Faculty table, and Documents table
        
        $message = "Registration successful! Please wait for Admin approval.";
        $messageType = "success";
        */
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete TP Registration - NIELIT TPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f6f9; }
        .step-indicator {
            width: 35px; height: 35px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; background-color: white; transition: all 0.3s;
            margin: 0 auto; z-index: 2; position: relative;
        }
        .step-active { background-color: #0d6efd; color: white; border: 2px solid #0d6efd; box-shadow: 0 0 10px rgba(13, 110, 253, 0.3); }
        .step-completed { background-color: #198754; color: white; border: 2px solid #198754; }
        .step-inactive { background-color: #e9ecef; color: #6c757d; border: 2px solid #ced4da; }
        .step-label { font-size: 0.75rem; margin-top: 8px; text-align: center; color: #6c757d; font-weight: 500; }
        .active-label { color: #0d6efd; font-weight: bold; }
        .form-step { display: none; animation: fadeIn 0.4s; }
        .form-step.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0">NIELIT TPS - New Center Registration Form</h4>
                </div>
                <div class="card-body p-4 p-md-5">
                    
                    <?php if($message): ?>
                        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
                    <?php endif; ?>

                    <div class="position-relative mb-5 px-2">
                        <div class="progress" style="height: 4px; top: 16px; position: absolute; width: 90%; left: 5%; z-index: 1;">
                            <div class="progress-bar bg-primary" id="progressBar" style="width: 0%; transition: width 0.4s;"></div>
                        </div>
                        <div class="d-flex justify-content-between position-relative" style="z-index: 2;">
                            <div style="width: 70px;"><div class="step-indicator step-active" id="ind-1">1</div><div class="step-label active-label" id="lbl-1">Account</div></div>
                            <div style="width: 70px;"><div class="step-indicator step-inactive" id="ind-2">2</div><div class="step-label" id="lbl-2">Faculty</div></div>
                            <div style="width: 70px;"><div class="step-indicator step-inactive" id="ind-3">3</div><div class="step-label" id="lbl-3">Financials</div></div>
                            <div style="width: 70px;"><div class="step-indicator step-inactive" id="ind-4">4</div><div class="step-label" id="lbl-4">Documents</div></div>
                            <div style="width: 70px;"><div class="step-indicator step-inactive" id="ind-5">5</div><div class="step-label" id="lbl-5">Submit</div></div>
                        </div>
                    </div>

                    <form id="signupForm" method="POST" action="" enctype="multipart/form-data">
                        
                        <div class="form-step active" id="step-0">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold"><i class="bi bi-person-badge"></i> Step 1: Account & Center Details</h5>
                            
                            <div class="row bg-light p-3 rounded mb-4 border shadow-sm">
                                <div class="col-md-4 mb-3"><label class="fw-bold text-secondary">Unique Center ID</label><input type="text" name="center_id" class="form-control" required placeholder="e.g. OD001"></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold text-secondary">Create Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold text-secondary">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required minlength="6"></div>
                            </div>

                            <h6 class="text-secondary mb-3 fw-bold">Legal Status of Institute (Sec 9)</h6>
                            <div class="row mb-4">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label fw-bold">Select Category</label>
                                    <select name="legal_status" id="legalStatus" class="form-select border-primary" required>
                                        <option value="">-- Select Category --</option>
                                        <option value="proprietorship">(1) Proprietorship Concern</option>
                                        <option value="partnership">(2) Partnership</option>
                                        <option value="society">(3) Society / NGO</option>
                                        <option value="trust">(4) Trust</option>
                                        <option value="company">(5) Company</option>
                                    </select>
                                </div>
                            </div>

                            <h6 class="text-secondary mb-3 fw-bold">Signatory Information (Sec 3)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Name</label><input type="text" name="sig_name" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Father's Name</label><input type="text" name="sig_fname" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Designation</label><input type="text" name="sig_designation" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Address</label><input type="text" name="sig_address" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Locality/District/State/Pin</label><input type="text" name="sig_locality" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Mobile</label><input type="text" name="sig_mobile" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Email (Used for Login)</label><input type="email" name="sig_email" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">ID Proof Num (Pan/Aadhar)</label><input type="text" name="sig_id_number" class="form-control" required></div>
                            </div>

                            <h6 class="text-secondary mt-3 mb-3 fw-bold">Premises Information (Sec 4)</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Premise Type</label>
                                    <select name="premise_type" class="form-select" required><option value="">Select...</option><option value="Owned">Owned</option><option value="Rented">Rented</option></select>
                                </div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Premise Valid Upto</label><input type="date" name="premise_valid_upto" class="form-control" required></div>
                            </div>
                        </div>

                            <h6 class="text-secondary mt-3 mb-3 fw-bold">Premises Information (Sec 4)</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Premise Type</label>
                                    <select name="premise_type" class="form-select" required><option value="">Select...</option><option value="Owned">Owned</option><option value="Rented">Rented</option></select>
                                </div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Premise Valid Upto</label><input type="date" name="premise_valid_upto" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-1">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold"><i class="bi bi-building"></i> Step 2: Infrastructure & Faculty</h5>
                            
                            <h6 class="text-secondary mb-3 fw-bold">Basic Infrastructure (Required for Approval)</h6>
                            <div class="row bg-light p-3 rounded mb-4 border">
                                <div class="col-md-3 mb-3"><label class="form-label fw-bold">Carpet Area (Sq ft)</label><input type="number" name="infra_area" class="form-control" required></div>
                                <div class="col-md-3 mb-3"><label class="form-label fw-bold">No. of Classrooms</label><input type="number" name="infra_classes" class="form-control" required></div>
                                <div class="col-md-3 mb-3"><label class="form-label fw-bold">No. of Comp. Labs</label><input type="number" name="infra_labs" class="form-control" required></div>
                                <div class="col-md-3 mb-3"><label class="form-label fw-bold">Total Seating Capacity</label><input type="number" name="infra_seats" class="form-control" required></div>
                            </div>

                            <h6 class="text-secondary mb-3 fw-bold">Primary Faculty Details</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Name of Primary Faculty</label><input type="text" name="fac_name[]" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Qualification</label><input type="text" name="fac_qualification[]" class="form-control" required placeholder="e.g. OSCIT"></div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold">Examination Passed</label>
                                    <select name="fac_exam[]" class="form-select" required>
                                        <option value="">Select...</option><option value="MCA">MCA</option><option value="BCA">BCA</option><option value="OSCIT">OSCIT</option><option value="Others">Others</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Year of Passing</label><input type="number" name="fac_yop[]" class="form-control" required min="1990" max="2030"></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Board / University</label><input type="text" name="fac_board[]" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Experience (Dates)</label><input type="text" name="fac_exp[]" class="form-control" placeholder="DD-MM-YYYY to DD-MM-YYYY" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Name of Org. (Experience)</label><input type="text" name="fac_exp_org[]" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-2">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold"><i class="bi bi-graph-up-arrow"></i> Step 3: Financial Details</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Financial Ending Year</label><input type="number" name="fin_year" class="form-control" placeholder="e.g. 2024" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Turnover in Computer Training (₹)</label><input type="text" name="fin_turnover_comp" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Turnover in Other Activities (₹)</label><input type="text" name="fin_turnover_other" class="form-control" required></div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Income Tax Exempted</label>
                                    <select name="fin_tax_exempt" class="form-select" required><option value="">Select...</option><option value="Y">Yes</option><option value="N">No</option></select>
                                </div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Total Students Placed</label><input type="number" name="fin_placed" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-3">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold"><i class="bi bi-folder-check"></i> Step 4: Upload Documents</h5>
                            <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle"></i> Upload PDF/JPG
                    <div class="text-center mt-4">
                        <a href="../login.php" class="text-decoration-none fw-bold text-secondary">Already registered? Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentStep = 0;
    const steps = document.getElementsByClassName("form-step");
    showStep(currentStep);

    function showStep(n) {
        for (let i = 0; i < steps.length; i++) steps[i].classList.remove("active");
        steps[n].classList.add("active");

        document.getElementById("prevBtn").style.display = (n == 0) ? "none" : "inline-block";
        document.getElementById("nextBtn").style.display = (n == (steps.length - 1)) ? "none" : "inline-block";
        
        updateProgress(n);
    }

    function nextPrev(n) {
        if (n == 1 && !validateForm()) return false;
        currentStep += n;
        showStep(currentStep);
    }

    function validateForm() {
        let valid = true;
        const inputs = steps[currentStep].querySelectorAll('input, select');
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.reportValidity();
                valid = false;
            }
        });
        return valid;
    }

    function updateProgress(n) {
        const percent = (n / (steps.length - 1)) * 100;
        document.getElementById("progressBar").style.width = percent + "%";

        for (let i = 0; i < steps.length; i++) {
            const ind = document.getElementById("ind-" + (i+1));
            const lbl = document.getElementById("lbl-" + (i+1));
            ind.className = "step-indicator step-inactive";
            lbl.className = "step-label";
            ind.innerHTML = i + 1;

            if (i < n) {
                ind.className = "step-indicator step-completed";
                ind.innerHTML = "✓";
            } else if (i === n) {
                ind.className = "step-indicator step-active";
                lbl.className = "step-label active-label";
            }
        }
    }
</script>
</body>
</html>