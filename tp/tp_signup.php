<?php
session_start();
// require __DIR__ . '/../includes/config.php'; // Uncomment when database is ready

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $center_id = $_POST['center_id'];
    $email = $_POST['sig_email']; 
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "danger"; 
    } else {
        // --- Database Logic will go here later ---
        $message = "Form submitted successfully! Wait for Admin approval.";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        /* Animated Tech Gradient Background */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(-45deg, #0d324d, #19547b, #0a58ca, #00d2ff);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            color: #333;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* 3D Texture Grid Overlay */
        .bg-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(255,255,255,0.07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size: 30px 30px;
            z-index: -1;
            animation: panGrid 20s linear infinite;
        }
        @keyframes panGrid {
            0% { background-position: 0px 0px; }
            100% { background-position: 30px 30px; }
        }

        /* 3D Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.2), inset 0 0 0 1px rgba(255,255,255,0.2);
            border-radius: 20px;
            transform: translateZ(0);
            transition: all 0.4s ease;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 35px 60px rgba(0, 0, 0, 0.3), inset 0 0 0 1px rgba(255,255,255,0.3);
        }

        /* Progress Indicator Styling */
        .step-indicator {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; background-color: white; transition: all 0.4s ease;
            margin: 0 auto; z-index: 2; position: relative;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .step-active { background-color: #0d6efd; color: white; border: none; box-shadow: 0 0 15px rgba(13, 110, 253, 0.6); transform: scale(1.1); }
        .step-completed { background-color: #198754; color: white; border: none; }
        .step-inactive { background-color: #e9ecef; color: #6c757d; border: none; }
        .step-label { font-size: 0.8rem; margin-top: 10px; text-align: center; color: #555; font-weight: 600; }
        .active-label { color: #0d6efd; font-weight: 800; }
        
        .form-step { display: none; animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
        .form-step.active { display: block; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .form-control, .form-select { border-radius: 8px; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25); border-color: #86b7fe; }
    </style>
</head>
<body class="py-5">

<div class="bg-overlay"></div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="glass-card">
                <div class="bg-primary text-white text-center py-4" style="border-radius: 20px 20px 0 0;">
                    <h3 class="mb-0 fw-bold">NIELIT TPS <i class="bi bi-shield-check"></i></h3>
                    <p class="mb-0 text-white-50">New Training Center Registration</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    
                    <?php if($message): ?>
                        <div class="alert alert-<?= $messageType ?> rounded-3 shadow-sm"><?= $message ?></div>
                    <?php endif; ?>

                    <div class="position-relative mb-5 px-2 mt-2">
                        <div class="progress" style="height: 4px; top: 18px; position: absolute; width: 90%; left: 5%; z-index: 1; background-color: #e9ecef;">
                            <div class="progress-bar bg-primary" id="progressBar" style="width: 0%; transition: width 0.5s ease;"></div>
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
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold"><i class="bi bi-person-badge"></i> Step 1: Account Setup & Signatory Details</h5>
                            <div class="row bg-light p-3 rounded mb-4 border shadow-sm">
                                <div class="col-md-4 mb-3"><label class="fw-bold text-secondary">Unique Center ID</label><input type="text" name="center_id" class="form-control" required placeholder="e.g. OD001"></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold text-secondary">Create Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold text-secondary">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required minlength="6"></div>
                            </div>

                            <h6 class="text-secondary mb-3 fw-bold">Signatory Information (Sec 3)</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Name</label><input type="text" name="sig_name" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Father's Name</label><input type="text" name="sig_fname" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Designation</label><input type="text" name="sig_designation" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Address 1</label><input type="text" name="sig_address1" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Locality/District/State</label><input type="text" name="sig_locality" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Mobile</label><input type="text" name="sig_mobile" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Email (Used for Login)</label><input type="email" name="sig_email" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">ID Proof Num (Pan/Aadhar)</label><input type="text" name="sig_id_number" class="form-control" required></div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Premise Type (Sec 4)</label>
                                    <select name="premise_type" class="form-select" required><option value="">Select...</option><option value="Owned">Owned</option><option value="Rented">Rented</option></select>
                                </div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Premise Valid Upto</label><input type="date" name="premise_valid_upto" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-1">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold"><i class="bi bi-person-workspace"></i> Step 2: Faculty Details</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Name of Primary Faculty</label><input type="text" name="fac_name[]" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Qualification</label><input type="text" name="fac_qualification[]" class="form-control" required placeholder="e.g. OSCIT"></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Year of Passing</label><input type="text" name="fac_yop[]" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Board / University</label><input type="text" name="fac_board[]" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="form-label fw-bold">Experience (Dates)</label><input type="text" name="fac_exp[]" class="form-control" placeholder="DD-MM-YY to DD-MM-YY" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-2">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold"><i class="bi bi-graph-up-arrow"></i> Step 3: Financial Details</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Financial Ending Year</label><input type="number" name="fin_year" class="form-control" placeholder="e.g. 2024" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Turnover in Computer Training</label><input type="text" name="fin_turnover" class="form-control" required></div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Income Tax Exempted</label>
                                    <select name="fin_tax_exempt" class="form-select" required><option value="">Select...</option><option value="Y">Yes</option><option value="N">No</option></select>
                                </div>
                                <div class="col-md-6 mb-3"><label class="form-label fw-bold">Students Placed</label><input type="number" name="fin_placed" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-3">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold"><i class="bi bi-folder-check"></i> Step 4: Upload Documents</h5>
                            <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle"></i> Upload PDF/JPG formats only (Max 2MB each)</div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="form-label small fw-bold">1. Authorized ID Proof</label><input type="file" name="doc_id_proof" class="form-control form-control-sm" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label small fw-bold">2. Signatory Signature</label><input type="file" name="doc_signature" class="form-control form-control-sm" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label small fw-bold">3. Layout Map</label><input type="file" name="doc_layout" class="form-control form-control-sm" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label small fw-bold">4. Govt Registration Cert.</label><input type="file" name="doc_govt_reg" class="form-control form-control-sm" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label small fw-bold">5. Lease/Rent Agreement (NOC)</label><input type="file" name="doc_lease" class="form-control form-control-sm" required></div>
                                <div class="col-md-6 mb-3"><label class="form-label small fw-bold">6. Building/Lab Photos</label><input type="file" name="doc_photos" class="form-control form-control-sm" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-4">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold"><i class="bi bi-check2-circle"></i> Step 5: Final Declaration</h5>
                            <div class="alert alert-info">I declare that all information and uploaded documents are authentic.</div>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                <label class="form-check-label fw-bold" for="agreeTerms">I agree to NIELIT Terms & Conditions</label>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm">Submit Application for Admin Approval <i class="bi bi-send ms-2"></i></button>
                        </div>

                        <div class="d-flex justify-content-between mt-5" id="formNavigation">
                            <button type="button" class="btn btn-outline-secondary px-4 fw-bold" id="prevBtn" onclick="nextPrev(-1)" style="display: none;">Previous</button>
                            <button type="button" class="btn btn-primary px-4 fw-bold ms-auto" id="nextBtn" onclick="nextPrev(1)">Next Step <i class="bi bi-arrow-right"></i></button>
                        </div>

                    </form>
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
                ind.innerHTML = "<i class='bi bi-check'></i>";
            } else if (i === n) {
                ind.className = "step-indicator step-active";
                lbl.className = "step-label active-label";
            }
        }
    }
</script>
</body>
</html>