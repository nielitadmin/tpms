<?php
session_start();
require __DIR__ . '/../includes/config.php'; 

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic Data Extraction
    $center_id = $_POST['center_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "danger"; 
    } else {
        // Backend logic for saving 10MB files will go here
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(-45deg, #0d324d, #19547b, #0a58ca, #00d2ff);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
        }
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .bg-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 30px 30px;
            z-index: -1;
            animation: panGrid 20s linear infinite;
        }
        @keyframes panGrid { from { background-position: 0 0; } to { background-position: 30px 30px; } }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        .step-indicator {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; background: white; margin: 0 auto;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .step-active { background: #0d6efd; color: white; transform: scale(1.1); box-shadow: 0 0 15px rgba(13, 110, 253, 0.5); }
        .step-completed { background: #198754; color: white; }
        .step-label { font-size: 0.75rem; margin-top: 8px; text-align: center; color: #666; font-weight: 600; }
        .form-step { display: none; animation: fadeIn 0.5s ease; }
        .form-step.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="py-5">
<div class="bg-overlay"></div>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="glass-card">
                <div class="bg-primary text-white text-center py-4 rounded-top" style="border-radius: 20px 20px 0 0;">
                    <h3 class="mb-0 fw-bold">NIELIT TPS <i class="bi bi-shield-lock"></i></h3>
                    <p class="mb-0 text-white-50">Complete Institutional Registration</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php if($message): ?>
                        <div class="alert alert-<?= $messageType ?> rounded-3 shadow-sm"><?= $message ?></div>
                    <?php endif; ?>

                    <div class="position-relative mb-5 px-2">
                        <div class="progress" style="height: 4px; top: 18px; position: absolute; width: 90%; left: 5%; background: #eee;">
                            <div class="progress-bar bg-primary" id="progressBar" style="width: 0%; transition: 0.5s;"></div>
                        </div>
                        <div class="d-flex justify-content-between position-relative" style="z-index: 2;">
                            <div><div class="step-indicator step-active" id="ind-1">1</div><div class="step-label" id="lbl-1">Account</div></div>
                            <div><div class="step-indicator" id="ind-2">2</div><div class="step-label" id="lbl-2">Faculty</div></div>
                            <div><div class="step-indicator" id="ind-3">3</div><div class="step-label" id="lbl-3">Financial</div></div>
                            <div><div class="step-indicator" id="ind-4">4</div><div class="step-label" id="lbl-4">Documents</div></div>
                            <div><div class="step-indicator" id="ind-5">5</div><div class="step-label" id="lbl-5">Submit</div></div>
                        </div>
                    </div>

                    <form id="signupForm" method="POST" action="" enctype="multipart/form-data">
                        <div class="form-step active" id="step-0">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold">Step 1: Center & Legal Status (Sec 9)</h5>
                            <div class="row bg-light p-3 rounded mb-4 border">
                                <div class="col-md-4 mb-3"><label class="fw-bold">Center ID</label><input type="text" name="center_id" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Password</label><input type="password" name="password" class="form-control" required minlength="6"></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
                            </div>
                            <div class="mb-4">
                                <label class="fw-bold">Legal Status Category</label>
                                <select name="legal_status" class="form-select border-primary" required>
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
                                <div class="col-md-4 mb-3"><label class="fw-bold">Email</label><input type="email" name="sig_email" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-1">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold">Step 2: Faculty & Infrastructure</h5>
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3"><label class="fw-bold">Carpet Area (Sq ft)</label><input type="number" name="infra_area" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Labs</label><input type="number" name="infra_labs" class="form-control" required></div>
                                <div class="col-md-4 mb-3"><label class="fw-bold">Premise</label><select name="premise_type" class="form-select" required><option value="Owned">Owned</option><option value="Rented">Rented</option></select></div>
                            </div>
                            <h6 class="text-secondary fw-bold">Primary Faculty (Sec 12)</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3"><input type="text" name="fac_name[]" class="form-control" placeholder="Faculty Name" required></div>
                                <div class="col-md-6 mb-3"><input type="text" name="fac_qual[]" class="form-control" placeholder="Qualification" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-2">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold">Step 3: Financial Details (Sec 14)</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="fw-bold">Ending Year</label><input type="number" name="fin_year" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="fw-bold">Turnover (₹)</label><input type="text" name="fin_turnover" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="fw-bold">Students Placed</label><input type="number" name="fin_placed" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-3">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold">Step 4: Documents (Sec 17)</h5>
                            <div class="alert alert-warning small">Max size: 10MB per file.</div>
                            <div class="row">
                                <div class="col-md-6 mb-3"><label class="small fw-bold">1. ID Proof</label><input type="file" name="doc_id" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="small fw-bold">2. Signature</label><input type="file" name="doc_sig" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="small fw-bold">3. Layout Map</label><input type="file" name="doc_map" class="form-control" required></div>
                                <div class="col-md-6 mb-3"><label class="small fw-bold">4. Building Photos</label><input type="file" name="doc_photos" class="form-control" required></div>
                            </div>
                        </div>

                        <div class="form-step" id="step-4">
                            <h5 class="border-bottom pb-2 mb-4 text-primary fw-bold">Step 5: Final Review</h5>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" required>
                                <label class="form-check-label fw-bold">I confirm all information is authentic according to NIELIT standards.</label>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">Finish Registration</button>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <button type="button" class="btn btn-secondary px-4 fw-bold" id="prevBtn" onclick="nextPrev(-1)" style="display: none;">Back</button>
                            <button type="button" class="btn btn-primary px-4 fw-bold ms-auto" id="nextBtn" onclick="nextPrev(1)">Next <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </form>
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
            ind.className = "step-indicator" + (i < n ? " step-completed" : (i == n ? " step-active" : ""));
            ind.innerHTML = i < n ? "✓" : i + 1;
        }
    }
</script>
</body>
</html>