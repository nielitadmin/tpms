<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('admin');

$message = '';
$messageType = '';

// 1. Handle Adding a New Course (Updated with new fields)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $course_name = $conn->real_escape_string($_POST['course_name']);
    $duration = $conn->real_escape_string($_POST['duration']);
    $eligibility = $conn->real_escape_string($_POST['eligibility']);
    $carpet_area = $conn->real_escape_string($_POST['carpet_area']);
    $system_req = $conn->real_escape_string($_POST['system_requirements']);
    $faculty_req = $conn->real_escape_string($_POST['faculty_requirements']);

    $sql = "INSERT INTO courses (course_name, duration, eligibility, carpet_area, system_requirements, faculty_requirements, status) 
            VALUES ('$course_name', '$duration', '$eligibility', '$carpet_area', '$system_req', '$faculty_req', 'active')";
            
    if ($conn->query($sql)) {
        $message = "New NSQF course successfully added to the syllabus!";
        $messageType = "success";
    } else {
        $message = "Error adding course: " . $conn->error;
        $messageType = "danger";
    }
}

// 2. Handle Editing an Existing Course (NEW)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_course'])) {
    $course_id = (int)$_POST['course_id'];
    $course_name = $conn->real_escape_string($_POST['course_name']);
    $duration = $conn->real_escape_string($_POST['duration']);
    $eligibility = $conn->real_escape_string($_POST['eligibility']);
    $carpet_area = $conn->real_escape_string($_POST['carpet_area']);
    $system_req = $conn->real_escape_string($_POST['system_requirements']);
    $faculty_req = $conn->real_escape_string($_POST['faculty_requirements']);

    $sql = "UPDATE courses SET 
            course_name = '$course_name', 
            duration = '$duration', 
            eligibility = '$eligibility', 
            carpet_area = '$carpet_area', 
            system_requirements = '$system_req', 
            faculty_requirements = '$faculty_req' 
            WHERE id = $course_id";

    if ($conn->query($sql)) {
        $message = "Course details updated successfully!";
        $messageType = "success";
    } else {
        $message = "Error updating course: " . $conn->error;
        $messageType = "danger";
    }
}

// 3. Handle Status Toggle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $course_id = (int)$_POST['course_id'];
    $new_status = ($_POST['current_status'] == 'active') ? 'inactive' : 'active';
    $conn->query("UPDATE courses SET status = '$new_status' WHERE id = $course_id");
    header("Location: admin_courses.php"); 
    exit();
}

// 4. Handle Course Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_course'])) {
    $course_id = (int)$_POST['course_id'];
    if ($conn->query("DELETE FROM courses WHERE id = $course_id")) {
        $message = "Course permanently deleted from the system.";
        $messageType = "success";
    }
}

// Fetch all courses
$sql = "SELECT c.*, COUNT(s.id) as student_count 
        FROM courses c 
        LEFT JOIN students s ON c.id = s.course_id 
        GROUP BY c.id 
        ORDER BY c.id DESC";
$courses = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage NSQF Courses - Admin Command</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Moving Light Theme */
        @keyframes moveGradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        body { background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1); background-size: 400% 400%; animation: moveGradient 15s ease infinite; font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; min-height: 100vh; }

        /* Glass Elements */
        .navbar-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); z-index: 1000; }
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 20px; box-shadow: 10px 10px 20px rgba(166, 180, 200, 0.4), -10px -10px 20px rgba(255, 255, 255, 0.9); transition: all 0.4s; overflow: hidden; }
        .btn-3d { border-radius: 12px; font-weight: 600; letter-spacing: 0.5px; transition: all 0.2s; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; }
        .btn-3d:active { transform: scale(0.95); box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8); }

        /* Form Inputs */
        .form-control-3d { border-radius: 10px; border: 1px solid rgba(0,0,0,0.1); box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05); background: rgba(255,255,255,0.9); padding: 10px 15px; }
        .form-control-3d:focus { border-color: #3b82f6; box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05), 0 0 0 0.25rem rgba(59, 130, 246, 0.25); }

        /* Table Styling */
        .table-glass { --bs-table-bg: transparent; --bs-table-hover-bg: rgba(241, 245, 249, 0.6); margin-bottom: 0; }
        .table-glass thead th { background: #1e293b; color: white; border: none; padding: 15px; font-weight: 600; letter-spacing: 0.5px; }
        .table-glass tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid rgba(0,0,0,0.05); }

        /* Status Badges */
        .status-badge { font-weight: 700; padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; border: 1px solid transparent; display: inline-block; }
        .status-active { background: rgba(52, 211, 153, 0.2); color: #059669; border-color: rgba(52, 211, 153, 0.5); }
        .status-inactive { background: rgba(248, 113, 113, 0.2); color: #dc2626; border-color: rgba(248, 113, 113, 0.5); }
        
        /* Modal Glass */
        .modal-content-glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(15px); border-radius: 20px; border: 1px solid rgba(255,255,255,1); box-shadow: 0 25px 50px rgba(0,0,0,0.15); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg sticky-top navbar-glass py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fs-4" href="admin_dashboard.php">
                <i class="fas fa-shield-alt text-dark me-2"></i> NIELIT Admin
            </a>
            <div class="d-flex align-items-center">
                <div class="me-4 text-secondary fw-semibold d-none d-md-block">
                    <i class="fas fa-user-astronaut text-dark me-1"></i> <?= htmlspecialchars($_SESSION['name'] ?? 'Administrator'); ?>
                </div>
                <a href="../logout.php" class="btn btn-3d btn-dark text-white"><i class="fas fa-power-off me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 mb-5">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-book text-primary me-2"></i> Manage NSQF Master Syllabus</h2>
                <p class="text-muted mb-0">Configure courses, define infrastructure requirements, and monitor batches.</p>
            </div>
            <div>
                <a href="admin_dashboard.php" class="btn btn-3d btn-light text-primary px-4 py-2 border">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?= $messageType ?> card-3d border-<?= $messageType ?> border-start border-5 mb-4 p-3 d-flex align-items-center">
                <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> fs-4 text-<?= $messageType ?> me-3"></i>
                <div class="fw-bold"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-xl-4 col-lg-5">
                <div class="card-3d p-4 border-top border-primary border-4 h-100" style="background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(241,245,249,0.9));">
                    <h4 class="fw-bold text-dark mb-4"><i class="fas fa-plus-circle text-primary me-2"></i> Add NSQF Course</h4>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">Course Name</label>
                            <input type="text" name="course_name" class="form-control form-control-3d" required placeholder="e.g., O level (IT)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">National Hours</label>
                            <input type="text" name="duration" class="form-control form-control-3d" required placeholder="e.g., 540 Hours">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary small text-uppercase">NSQF Level & Credits</label>
                            <input type="text" name="eligibility" class="form-control form-control-3d" required placeholder="e.g., Level 4 (18 Credits)">
                        </div>

                        <div class="accordion mb-4 mt-4" id="reqAccordion">
                            <div class="accordion-item border-0 bg-transparent">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold bg-primary bg-opacity-10 text-primary rounded-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReq">
                                        <i class="fas fa-tools me-2"></i> Infrastructure & Faculty Requirements
                                    </button>
                                </h2>
                                <div id="collapseReq" class="accordion-collapse collapse mt-3">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary small">Required Carpet Area</label>
                                        <input type="text" name="carpet_area" class="form-control form-control-3d" placeholder="e.g., Min 500 Sq Ft Classroom">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary small">System/PC Requirements</label>
                                        <textarea name="system_requirements" class="form-control form-control-3d" rows="2" placeholder="e.g., 20 PCs, i5 Processor, 8GB RAM..."></textarea>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-secondary small">Faculty Qualifications</label>
                                        <textarea name="faculty_requirements" class="form-control form-control-3d" rows="2" placeholder="e.g., B.Tech/MCA with 2 Years Exp..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="add_course" class="btn btn-primary btn-3d w-100 py-3 fs-5">Add to Syllabus <i class="fas fa-paper-plane ms-2"></i></button>
                    </form>
                </div>
            </div>

            <div class="col-xl-8 col-lg-7">
                <div class="card-3d p-2 p-md-4 h-100">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                        <h5 class="fw-bold text-dark m-0">Active Course Directory</h5>
                        <div class="input-group w-50">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="courseSearch" class="form-control border-start-0 ps-0" placeholder="Quick search courses...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-glass align-middle" id="courseTable">
                            <thead>
                                <tr>
                                    <th style="border-top-left-radius: 12px;">Course Name</th>
                                    <th>Level Details</th>
                                    <th>Status</th>
                                    <th style="border-top-right-radius: 12px;" class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $courses->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($row['course_name']) ?></div>
                                        <div class="text-muted small mt-1"><i class="fas fa-users text-primary me-1"></i> <?= $row['student_count'] ?> Enrolled</div>
                                    </td>
                                    <td>
                                        <div class="text-secondary fw-medium"><i class="fas fa-clock me-1 opacity-75"></i> <?= htmlspecialchars($row['duration']) ?></div>
                                        <div class="text-muted small mt-1"><i class="fas fa-award me-1 opacity-75"></i> <?= htmlspecialchars($row['eligibility']) ?></div>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'active'): ?>
                                            <span class="status-badge status-active"><i class="fas fa-check-circle me-1"></i> Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive"><i class="fas fa-ban me-1"></i> Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            
                                            <button type="button" class="btn btn-sm btn-info btn-3d text-white" title="View Requirements"
                                                onclick="viewRequirements('<?= htmlspecialchars(addslashes($row['course_name'])) ?>', '<?= htmlspecialchars(addslashes($row['carpet_area'])) ?>', '<?= htmlspecialchars(addslashes($row['system_requirements'])) ?>', '<?= htmlspecialchars(addslashes($row['faculty_requirements'])) ?>')">
                                                <i class="fas fa-info-circle"></i>
                                            </button>

                                            <button type="button" class="btn btn-sm btn-primary btn-3d" title="Edit Course"
                                                onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['course_name'])) ?>', '<?= htmlspecialchars(addslashes($row['duration'])) ?>', '<?= htmlspecialchars(addslashes($row['eligibility'])) ?>', '<?= htmlspecialchars(addslashes($row['carpet_area'])) ?>', '<?= htmlspecialchars(addslashes($row['system_requirements'])) ?>', '<?= htmlspecialchars(addslashes($row['faculty_requirements'])) ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <form method="POST" action="" class="m-0">
                                                <input type="hidden" name="course_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $row['status'] ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $row['status'] == 'active' ? 'warning' : 'success' ?> btn-3d" title="<?= $row['status'] == 'active' ? 'Deactivate' : 'Activate' ?>">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" action="" class="m-0" onsubmit="return confirm('WARNING: Are you sure you want to delete this course? This will also delete ALL student records associated with it.');">
                                                <input type="hidden" name="course_id" value="<?= $row['id'] ?>">
                                                <button type="submit" name="delete_course" class="btn btn-sm btn-danger btn-3d" title="Delete Course">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewReqModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-glass">
                <div class="modal-header border-bottom border-light">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-clipboard-list text-info me-2"></i> Course Requirements</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-primary mb-4 border-bottom pb-2" id="viewCourseName">Course Name</h6>
                    
                    <div class="mb-3">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1"><i class="fas fa-ruler-combined me-1"></i> Carpet Area</span>
                        <div class="fw-medium text-dark" id="viewArea">Not specified</div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1"><i class="fas fa-desktop me-1"></i> PC/System Requirements</span>
                        <div class="fw-medium text-dark bg-light p-2 rounded border" id="viewSys">Not specified</div>
                    </div>
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1"><i class="fas fa-chalkboard-teacher me-1"></i> Faculty Requirements</span>
                        <div class="fw-medium text-dark bg-light p-2 rounded border" id="viewFac">Not specified</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editCourseModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content modal-content-glass">
                <div class="modal-header border-bottom border-light">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-edit text-primary me-2"></i> Edit Course Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <input type="hidden" name="course_id" id="edit_course_id">
                        
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold text-secondary small text-uppercase">Course Name</label>
                                <input type="text" name="course_name" id="edit_name" class="form-control form-control-3d" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary small text-uppercase">National Hours</label>
                                <input type="text" name="duration" id="edit_dur" class="form-control form-control-3d" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary small text-uppercase">Level & Credits</label>
                                <input type="text" name="eligibility" id="edit_elig" class="form-control form-control-3d" required>
                            </div>
                            
                            <div class="col-12 mt-3 mb-2 border-bottom pb-1">
                                <h6 class="fw-bold text-primary"><i class="fas fa-cogs me-2"></i> Infrastructure Requirements</h6>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold text-secondary small">Required Carpet Area</label>
                                <input type="text" name="carpet_area" id="edit_area" class="form-control form-control-3d">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary small">System/PC Requirements</label>
                                <textarea name="system_requirements" id="edit_sys" class="form-control form-control-3d" rows="3"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold text-secondary small">Faculty Qualifications</label>
                                <textarea name="faculty_requirements" id="edit_fac" class="form-control form-control-3d" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top border-light">
                        <button type="button" class="btn btn-light btn-3d" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_course" class="btn btn-primary btn-3d px-4">Save Changes <i class="fas fa-save ms-1"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Real-time Search Filter
        document.getElementById('courseSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#courseTable tbody tr');
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        // Populate and Open the View Requirements Modal
        function viewRequirements(name, area, sys, fac) {
            document.getElementById('viewCourseName').innerText = name;
            document.getElementById('viewArea').innerText = area || 'Not specified';
            document.getElementById('viewSys').innerText = sys || 'Not specified';
            document.getElementById('viewFac').innerText = fac || 'Not specified';
            new bootstrap.Modal(document.getElementById('viewReqModal')).show();
        }

        // Populate and Open the Edit Course Modal
        function openEditModal(id, name, dur, elig, area, sys, fac) {
            document.getElementById('edit_course_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_dur').value = dur;
            document.getElementById('edit_elig').value = elig;
            document.getElementById('edit_area').value = area;
            document.getElementById('edit_sys').value = sys;
            document.getElementById('edit_fac').value = fac;
            new bootstrap.Modal(document.getElementById('editCourseModal')).show();
        }
    </script>
</body>
</html>
