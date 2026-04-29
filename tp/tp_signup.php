<?php
session_start();
require __DIR__ . '/../includes/config.php';

$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $center_id = $conn->real_escape_string($_POST['center_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // New fields added from the document
    $legal_status = $conn->real_escape_string($_POST['legal_status']);
    $premises = $conn->real_escape_string($_POST['premises']);

    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $messageType = "danger";
    } else {
        // Check if email or center_id already exists
        $check_sql = "SELECT id FROM users WHERE email = '$email' OR center_id = '$center_id'";
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $message = "Email or Center ID already registered.";
            $messageType = "danger";
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Updated SQL query to include legal_status and premises
            $sql = "INSERT INTO users (center_id, name, email, phone, password, role, status, legal_status, premises) 
                    VALUES ('$center_id', '$name', '$email', '$phone', '$hashed_password', 'tp', 'pending', '$legal_status', '$premises')";
            
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
        .document-section {
            display: none;
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #0d6efd;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 0.9em;
        }
        .document-section.active {
            display: block;
        }
        .form-check-label {
            font-size: 0.9em;
        }
    </style>
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">Register New TPS Center</h4>
                        
                        <?php if($message): ?>
                            <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Unique Center ID (e.g., OD001)</label>
                                    <input type="text" name="center_id" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Center / Institute Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Email address</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Phone Number</label>
                                    <input type="text" name="phone" class="form-control" required pattern="[0-9]{10}" title="Enter a valid 10-digit phone number">
                                </div>
                            </div>

                            <div class="mb-3 p-3 border rounded bg-white">
                                <label class="fw-bold d-block mb-2">Premises Details</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="premises" id="premiseOwn" value="Own" required>
                                    <label class="form-check-label" for="premiseOwn">Own</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="premises" id="premiseRented" value="Rented" required>
                                    <label class="form-check-label" for="premiseRented">Rented</label>
                                </div>
                            </div>

                            <div class="mb-3 p-3 border rounded bg-white">
                                <label class="fw-bold">Legal Status Category</label>
                                <select name="legal_status" id="legalStatus" class="form-select mt-2" onchange="showDocuments()" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="Proprietorship">(1) PROPRIETORSHIP CONCERN</option>
                                    <option value="Partnership">(2) PARTNERSHIP</option>
                                    <option value="Society">(3) SOCIETY / NGO</option>
                                    <option value="Trust">(4) TRUST</option>
                                    <option value="Company">(5) COMPANY</option>
                                </select>

                                <div id="Proprietorship" class="document-section">
                                    <p class="fw-bold text-primary mb-2">Required Documents to Submit/Verify:</p>
                                    <ul class="list-unstyled">
                                        <li>&#10004; Registration/Certificate issued by Govt authority (Industrial/Business unit or Shop & Establishment Act)</li>
                                        <li>&#10004; Registration with Registrar / Sub-Registrar</li>
                                        <li>&#10004; Registration with Sales Tax / Service Tax</li>
                                        <li><small class="text-danger">* Bank certificate can establish ownership if needed.</small></li>
                                        <li>&#10004; Authority letter from proprietor indicating powers given.</li>
                                    </ul>
                                </div>

                                <div id="Partnership" class="document-section">
                                    <p class="fw-bold text-primary mb-2">Required Documents to Submit/Verify:</p>
                                    <ul class="list-unstyled">
                                        <li>&#10004; Registered Partnership Deed</li>
                                        <li>&#10004; Registration Certificate from Registrar of firms</li>
                                        <li>&#10004; Authority letter duly signed by all partners</li>
                                    </ul>
                                </div>

                                <div id="Society" class="document-section">
                                    <p class="fw-bold text-primary mb-2">Required Documents to Submit/Verify:</p>
                                    <ul class="list-unstyled">
                                        <li>&#10004; Certificate from the Registrar of Society</li>
                                        <li>&#10004; Rules and Regulations / Memorandum of Association</li>
                                        <li>&#10004; Resolution nominating the authorised person signed by members</li>
                                    </ul>
                                </div>

                                <div id="Trust" class="document-section">
                                    <p class="fw-bold text-primary mb-2">Required Documents to Submit/Verify:</p>
                                    <ul class="list-unstyled">
                                        <li>&#10004; Trust Deed</li>
                                        <li>&#10004; Certificate of Registration of Trust</li>
                                        <li>&#10004; Resolution to nominate the authorised person signed by Trustees</li>
                                    </ul>
                                </div>

                                <div id="Company" class="document-section">
                                    <p class="fw-bold text-primary mb-2">Required Documents to Submit/Verify:</p>
                                    <ul class="list-unstyled">
                                        <li>&#10004; Certificate of Incorporation</li>
                                        <li>&#10004; Memorandum of Association</li>
                                        <li>&#10004; Board Resolution authorizing the person to deal with NIELIT</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label>Password</label>
                                    <input type="password" name="password" class="form-control" required minlength="6">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label>Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mt-2">Register Center</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="../login.php" class="text-decoration-none">Already registered? Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to show the required document list based on dropdown selection
        function showDocuments() {
            var sections = document.querySelectorAll('.document-section');
            sections.forEach(function(section) {
                section.classList.remove('active');
            });

            var selectedStatus = document.getElementById('legalStatus').value;
            if (selectedStatus) {
                document.getElementById(selectedStatus).classList.add('active');
            }
        }
    </script>
</body>
</html>