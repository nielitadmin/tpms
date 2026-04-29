<?php
session_start();
require __DIR__ . '/../includes/config.php'; 

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $center_id = $_POST['center_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "danger"; 
    } else {
        $message = "Registration request sent successfully! Waiting for Admin approval.";
        $messageType = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complete TP Registration - NIELIT TPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        
        .step-indicator {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; background-color: white; 
            border: 2px solid #ced4da; color: #6c757d;
            margin: 0 auto; z-index: 2; position: relative;
        }
        .step-active { background-color: #0d6efd; color: white; border-color: #0d6efd; }
        .step-completed { background-color: #198754; color: white; border-color: #198754; }
        .step-label { font-size: 0.8rem; margin-top: 8px; text-align: center; color: #6c757d; font-weight: 500; }
        .active-label { color: #0d6efd; font-weight: bold; }
        
        .form-step { display: none; }
        .form-step.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0">NIELIT TPS - Center Registration</h4>
                </div>
                <div class="card-body p-4 p-md-5">
                    
                    <?php if($message): ?>
                        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
                    <?php endif; ?>

                    <div class="position-relative mb-5 px-2">
                        <div class="progress" style="height: 4px; top: 18px; position: absolute; width: 90%; left: 5%; z-index: 1;">
                            <div class="progress-bar bg-primary" id="progressBar" style="width: 0%; transition: width 0.4s;"></div>
                        </div>
                        <div class="d-flex justify-content-between position-relative" style="z-index: 2;">
                            <div style="width: 70px;"><div class="step-indicator step-active" id="ind-1">1</div><div class="step-label active-label" id="lbl-1">Account</div></div>
                            <div style="width: 70px;"><div class="step-indicator" id="ind-2">2</div><div class="step-label" id="lbl-2">Faculty</div></div>
                            <div style="width: 70px;"><div class="step-indicator" id="ind-3">3</div><div class="step-label" id="lbl-3">Financials</div></div>
                            <div style="width: 70px;"><div class="step-indicator" id="ind-4">4</div><div class="step-label" id="lbl-4">Documents</div></div>
                            <div style="width: 70px;"><div class="step-indicator" id="ind-5">5</div><div class="step-label" id="lbl-5">Submit</div></div>
                        </div>
                    </div>

                    <form id="signupForm" method="POST" action="" enctype="multipart/form-data">
                        
                        <div class="form-step active" id="step-0">
                            <h5 class="border-bottom pb-2 mb-4 text-primary">Step 1: Account & Legal Status</h5>
                            <div class="row bg-light p-3 rounded mb-4 border">
                                <div class="col-md-4 mb-3"><label class="fw-bold">Center ID</label><input type="text" name="center_id" class="form-control" required placeholder="e.g. OD001"></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="fw-bold">Legal Status Category (Sec 9)</label>
                                <select name="legal_status" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="proprietorship">Proprietorship Concern</option>
                                    <option value="partnership">Partnership</option>
                                    <option value="society">Society / NGO</option>
                                    <option value="trust">Trust</option>
                                    <option value="company">Company</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3"><label class="fw-bold">Signatory Name</label><input type="text" name="sig_name" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Mobile</label><input type="text" name="sig_mobile" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Email (Login ID)</label><input type="email" name="sig_email" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-1">
                            <h5 class="border-bottom pb-2 mb-4 text-primary">Step 2: Faculty & Infrastructure</h5>
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3"><label class="fw-bold">Carpet Area (Sq ft)</label><input type="number" name="infra_area" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Computer Labs</label><input type="number" name="infra_labs" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Premise Type</label><select name="premise_type" class="form-select" required><option value="Owned">Owned</option><option value="Rented">Rented</option></select></div>
                            </div>
                            <h6 class="text-secondary fw-bold">Primary Faculty (Sec 12)</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3"><input type="text" name="fac_name[]" class="form-control" placeholder="Faculty Name" required></div>
                                <div class="col-md-6 mb-3"><input type="text" name="fac_qual[]" class="form-control" placeholder="Qualification (e.g., OSCIT)" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-2">
                            <h5 class="border-bottom pb-2 mb-4 text-primary">Step 3: Financial Details (Sec 14)</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="fw-bold">Financial Ending Year</label><input type="number" name="fin_year" class="form-control" required placeholder="e.g. 2024"></div>
                                <div class="col-md-6 mb-3"><label class="fw-bold">Annual Turnover (₹)</label><input type="text" name="fin_turnover" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="fw-bold">Students Placed</label><input type="number" name="fin_placed" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-3">
                            <h5 class="border-bottom pb-2 mb-4 text-primary">Step 4: Upload Documents (Sec 17)</h5>
                            <div class="alert alert-warning small">Upload PDF/JPG formats. Max size: 10MB per file.</div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="small fw-bold">1. Authorized ID Proof</label><input type="file" name="doc_id" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="small fw-bold">2. Signatory Signature</label><input type="file" name="doc_sig" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="small fw-bold">3. Layout Map</label><input type="file" name="doc_map" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="small fw-bold">4. Building Photos</label><input type="file" name="doc_photos" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-4">
                            <h5 class="border-bottom pb-2 mb-4 text-primary">Step 5: Final Review</h5>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" required>
                                <label class="form-check-label fw-bold">I confirm all uploaded information is authentic according to NIELIT standards.</label>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">Submit Application</button>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <button type="button" class="btn btn-secondary px-4 fw-bold" id="prevBtn" onclick="nextPrev(-1)" style="display: none;">Back</button>
                            <button type="button" class="btn btn-primary px-4 fw-bold ms-auto" id="nextBtn" onclick="nextPrev(1)">Next Step</button>
                        </div>
                    </form>

                    <div class="text-center mt-4 border-top pt-3">
                        <a href="../login.php" class="text-decoration-none text-secondary fw-bold">Already registered? Login here</a>
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
        document.getElementById("nextBtn").style.display = (n == steps.length - 1) ? "none" : "inline-block";
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
        inputs.forEach(input => { if (!input.checkValidity()) { input.reportValidity(); valid = false; } });
        return valid;
    }

    function updateProgress(n) {
        const percent = (n / (steps.length - 1)) * 100;
        document.getElementById("progressBar").style.width = percent + "%";
        for (let i = 0; i < steps.length; i++) {
            const ind = document.getElementById("ind-" + (i+1));
            const lbl = document.getElementById("lbl-" + (i+1));
            ind.className = "step-indicator";
            lbl.className = "step-label";
            if (i < n) {
                ind.classList.add("step-completed");
                ind.innerHTML = "✓";
            } else if (i === n) {
                ind.classList.add("step-active");
                lbl.classList.add("active-label");
                ind.innerHTML = i + 1;
            } else {
                ind.innerHTML = i + 1;
            }
        }
    }
</script>
</body>
</html>