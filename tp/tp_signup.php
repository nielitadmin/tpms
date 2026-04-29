<?php
session_start();
// require __DIR__ . '/../includes/config.php'; // Uncomment this in your actual project

$message = '';
$messageType = '';

// Dummy connection for testing (remove this in your actual code)
// $conn = new mysqli("localhost", "root", "", "nielit_db"); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $center_id = $conn->real_escape_string($_POST['center_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $legal_status = $conn->real_escape_string($_POST['legal_status']);

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "danger"; 
    } else {
        // Check if email or center_id already exists
        $check_sql = "SELECT id FROM users WHERE email = '$email' OR center_id = '$center_id'";
        $check_result = $conn->query($check_sql);

        if ($check_result && $check_result->num_rows > 0) {
            $message = "Email or Center ID already registered.";
            $messageType = "danger";
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // NOTE: You will need to write the file upload logic here (move_uploaded_file)
            // and save the file paths to your database. For now, we are just saving the basic info.

            $sql = "INSERT INTO users (center_id, name, email, phone, password, role, status) 
                    VALUES ('$center_id', '$name', '$email', '$phone', '$hashed_password', 'tp', 'pending')";
            
            if ($conn->query($sql) === TRUE) {
                $message = "Registration successful! Please wait for Admin approval before logging in.";
                $messageType = "success";
            } else {
                $message = "Error: " . $conn->error;
                $messageType = "danger";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TP Sign Up - NIELIT TPS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .doc-section { display: none; } /* Hides document upload sections by default */
    </style>
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0">Register New TPS Center</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        
                        <?php if($message): ?>
                            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            
                            <h5 class="border-bottom pb-2 mb-3 text-primary">1. Basic Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Unique Center ID (e.g., OD001)</label>
                                    <input type="text" name="center_id" class="form-control bg-light" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Center / Institute Name</label>
                                    <input type="text" name="name" class="form-control bg-light" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Email address</label>
                                    <input type="email" name="email" class="form-control bg-light" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Phone Number</label>
                                    <input type="text" name="phone" class="form-control bg-light" required pattern="[0-9]{10}" title="Enter a valid 10-digit phone number">
                                </div>
                            </div>

                            <h5 class="border-bottom pb-2 mt-4 mb-3 text-primary">2. Legal Status & Documentation</h5>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Category</label>
                                <select name="legal_status" id="legalStatus" class="form-select border-primary" required onchange="showDocuments()">
                                    <option value="">-- Select Category --</option>
                                    <option value="proprietorship">1. Proprietorship Concern</option>
                                    <option value="partnership">2. Partnership</option>
                                    <option value="society">3. Society / NGO</option>
                                    <option value="trust">4. Trust</option>
                                    <option value="company">5. Company</option>
                                </select>
                            </div>

                            <div id="docs_proprietorship" class="doc-section p-3 bg-white border rounded mb-4 shadow-sm">
                                <h6 class="text-secondary mb-3">Upload Proprietorship Documents (PDF/JPG):</h6>
                                <div class="mb-2"><label class="small">(i) Registration/Certificate (Shop & Establishment Act) OR Bank Authority Letter</label><input type="file" name="prop_doc1" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(ii) Registration with Registrar / Sub-Registrar</label><input type="file" name="prop_doc2" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(iii) Registration with Sales Tax / Service Tax</label><input type="file" name="prop_doc3" class="form-control form-control-sm"></div>
                            </div>

                            <div id="docs_partnership" class="doc-section p-3 bg-white border rounded mb-4 shadow-sm">
                                <h6 class="text-secondary mb-3">Upload Partnership Documents (PDF/JPG):</h6>
                                <div class="mb-2"><label class="small">(i) Registered Partnership Deed</label><input type="file" name="part_doc1" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(ii) Registration Certificate showing partner names</label><input type="file" name="part_doc2" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(iii) Authority letter signed by all partners</label><input type="file" name="part_doc3" class="form-control form-control-sm"></div>
                            </div>

                            <div id="docs_society" class="doc-section p-3 bg-white border rounded mb-4 shadow-sm">
                                <h6 class="text-secondary mb-3">Upload Society/NGO Documents (PDF/JPG):</h6>
                                <div class="mb-2"><label class="small">(i) Certificate from the Registrar of Society</label><input type="file" name="soc_doc1" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(ii) Rules and Regulations / Memorandum of Association</label><input type="file" name="soc_doc2" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(iii) Resolution nominating authorised person</label><input type="file" name="soc_doc3" class="form-control form-control-sm"></div>
                            </div>

                            <div id="docs_trust" class="doc-section p-3 bg-white border rounded mb-4 shadow-sm">
                                <h6 class="text-secondary mb-3">Upload Trust Documents (PDF/JPG):</h6>
                                <div class="mb-2"><label class="small">(i) Trust Deed</label><input type="file" name="trust_doc1" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(ii) Certificate of Registration of Trust</label><input type="file" name="trust_doc2" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(iii) Resolution to nominate authorised person</label><input type="file" name="trust_doc3" class="form-control form-control-sm"></div>
                            </div>

                            <div id="docs_company" class="doc-section p-3 bg-white border rounded mb-4 shadow-sm">
                                <h6 class="text-secondary mb-3">Upload Company Documents (PDF/JPG):</h6>
                                <div class="mb-2"><label class="small">(i) Certificate of Incorporation</label><input type="file" name="comp_doc1" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(ii) Memorandum of Association</label><input type="file" name="comp_doc2" class="form-control form-control-sm"></div>
                                <div class="mb-2"><label class="small">(iii) Board Resolution authorizing person</label><input type="file" name="comp_doc3" class="form-control form-control-sm"></div>
                            </div>

                            <h5 class="border-bottom pb-2 mt-4 mb-3 text-primary">3. Security</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Password</label>
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">Register Center</button>
                        </form>

                        <div class="text-center mt-4">
                            <a href="../login.php" class="text-decoration-none fw-bold">Already registered? Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showDocuments() {
            // Hide all document sections first
            document.querySelectorAll('.doc-section').forEach(function(el) {
                el.style.display = 'none';
            });
            
            // Get the selected value
            var selectedStatus = document.getElementById('legalStatus').value;
            
            // Show the corresponding section if a valid option is selected
            if(selectedStatus) {
                document.getElementById('docs_' + selectedStatus).style.display = 'block';
            }
        }
    </script>
</body>
</html>