<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/config.php';
checkRole('admin');

$message = '';
$messageType = '';

// Handle Placement Record Deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_placement'])) {
    $placement_id = (int)$_POST['placement_id'];
    
    if ($conn->query("DELETE FROM placements WHERE id = $placement_id")) {
        $message = "Placement record successfully deleted.";
        $messageType = "success";
    } else {
        $message = "Error deleting record: " . $conn->error;
        $messageType = "danger";
    }
}

// Fetch Quick Stats
$total_placements = $conn->query("SELECT COUNT(*) as count FROM placements")->fetch_assoc()['count'];
$total_companies = $conn->query("SELECT COUNT(DISTINCT company_name) as count FROM placements")->fetch_assoc()['count'];

// Fetch all placements with the corresponding TP details
$sql = "SELECT p.*, u.center_id, u.name as tp_name 
        FROM placements p 
        JOIN users u ON p.tp_id = u.id 
        ORDER BY p.placement_date DESC";
        
$placements = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Placement Records - Admin Command</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Moving Light Theme */
        @keyframes moveGradient { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        body { background: linear-gradient(-45deg, #f1f5f9, #e2e8f0, #f8fafc, #cbd5e1); background-size: 400% 400%; animation: moveGradient 15s ease infinite; font-family: 'Segoe UI', system-ui, sans-serif; color: #334155; min-height: 100vh; }

        /* Glass Navbar */
        .navbar-glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.5); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05); z-index: 1000; }
        .navbar-brand { font-weight: 800; background: linear-gradient(90deg, #1e293b, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        /* 3D Glass Cards */
        .card-3d { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 1); border-radius: 20px; box-shadow: 10px 10px 20px rgba(166, 180, 200, 0.4), -10px -10px 20px rgba(255, 255, 255, 0.9); transition: all 0.4s; overflow: hidden; }
        .card-3d-hover:hover { transform: translateY(-5px); box-shadow: 15px 15px 25px rgba(166, 180, 200, 0.5), -15px -15px 25px rgba(255, 255, 255, 1); }

        /* Action Buttons 3D */
        .btn-3d { border-radius: 12px; font-weight: 600; letter-spacing: 0.5px; transition: all 0.2s; box-shadow: 4px 4px 10px rgba(0,0,0,0.1), -4px -4px 10px rgba(255,255,255,0.8); border: none; }
        .btn-3d:active { transform: scale(0.95); box-shadow: inset 4px 4px 10px rgba(0,0,0,0.1), inset -4px -4px 10px rgba(255,255,255,0.8); }

        /* Table Styling */
        .table-glass { --bs-table-bg: transparent; --bs-table-hover-bg: rgba(241, 245, 249, 0.6); margin-bottom: 0; }
        .table-glass thead th { background: #1e293b; color: white; border: none; padding: 15px; font-weight: 600; letter-spacing: 0.5px; }
        .table-glass tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .table-glass tbody tr:last-child td { border-bottom: none; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
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
                <h2 class="fw-bold text-dark mb-1"><i class="fas fa-briefcase text-success me-2"></i> Master Placement Records</h2>
                <p class="text-muted mb-0">Track successful job placements logged by centers across the state.</p>
            </div>
            <div>
                <a href="admin_dashboard.php" class="btn btn-3d btn-light text-primary px-4 py-2 border">
                    <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?= $messageType ?> card-3d border-<?= $messageType ?> border-start border-5 mb-4 p-3 d-flex align-items-center animate__animated animate__fadeIn">
                <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> fs-4 text-<?= $messageType ?> me-3"></i>
                <div class="fw-bold"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card-3d card-3d-hover p-3 border-start border-success border-4 d-flex flex-row align-items-center bg-white bg-opacity-75">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
                        <i class="fas fa-user-tie fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.8rem;">Total Students Placed</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= $total_placements ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-3d card-3d-hover p-3 border-start border-primary border-4 d-flex flex-row align-items-center bg-white bg-opacity-75">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase fw-bold mb-1" style="font-size: 0.8rem;">Unique Hiring Companies</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= $total_companies ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-3d p-2 p-md-4 border-top border-success border-4">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 px-2">
                <div class="input-group w-100" style="max-width: 400px;">
                    <span class="input-group-text bg-white border-end-0 rounded-start-3"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="placementSearch" class="form-control border-start-0 rounded-end-3 shadow-none py-2" placeholder="Search names, companies, centers...">
                </div>
                
                <button onclick="exportTableToCSV('NIELIT_Placements.csv')" class="btn btn-success btn-3d px-4 py-2 text-nowrap">
                    <i class="fas fa-file-csv me-2"></i> Download Data
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-glass align-middle" id="placementTable">
                    <thead>
                        <tr>
                            <th style="border-top-left-radius: 12px;"><i class="fas fa-user-graduate me-2"></i>Candidate</th>
                            <th><i class="fas fa-building me-2"></i>Company & Role</th>
                            <th><i class="fas fa-money-check-alt me-2"></i>Package Info</th>
                            <th><i class="fas fa-university me-2"></i>Training Center</th>
                            <th><i class="fas fa-calendar-check me-2"></i>Date</th>
                            <th style="border-top-right-radius: 12px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $placements->fetch_assoc()): ?>
                        <tr class="placement-row">
                            <td>
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($row['student_name']) ?></div>
                            </td>
                            <td>
                                <div class="text-primary fw-bold"><i class="fas fa-briefcase me-1 opacity-75"></i> <?= htmlspecialchars($row['company_name']) ?></div>
                                <div class="text-muted small mt-1"><?= htmlspecialchars($row['designation']) ?></div>
                            </td>
                            <td>
                                <?php if(!empty($row['package'])): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success p-2 px-3 rounded-pill">
                                        <?= htmlspecialchars($row['package']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary p-2 px-3 rounded-pill">
                                        Not Disclosed
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($row['tp_name']) ?>">
                                    <?= htmlspecialchars($row['tp_name']) ?>
                                </div>
                                <div class="text-muted small mt-1">ID: <?= htmlspecialchars($row['center_id']) ?></div>
                            </td>
                            <td class="text-muted fw-medium"><?= date('d M Y', strtotime($row['placement_date'])) ?></td>
                            <td>
                                <form method="POST" action="" class="m-0" onsubmit="return confirm('Are you sure you want to delete this placement record?');">
                                    <input type="hidden" name="placement_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="delete_placement" class="btn btn-sm btn-outline-danger btn-3d px-2 py-1" title="Delete Record">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if($placements->num_rows == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-folder-open fa-3x text-muted opacity-25 mb-3"></i>
                                    <h5 class="text-muted fw-bold">No placement records found.</h5>
                                    <p class="text-muted small">Training partners have not uploaded any placement data yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Real-time Search Filter
        document.getElementById('placementSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.placement-row');
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                if(text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Export Table to CSV Function
        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#placementTable tr");
            
            // Loop through rows (excluding the last empty ones if hidden)
            for (var i = 0; i < rows.length; i++) {
                if(rows[i].style.display !== 'none') {
                    var row = [], cols = rows[i].querySelectorAll("td, th");
                    
                    // Exclude the last column (Action button) from CSV
                    let colCount = i === 0 ? cols.length - 1 : cols.length - 1; 
                    
                    for (var j = 0; j < colCount; j++) {
                        // Clean up text for CSV
                        let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""');
                        row.push('"' + data + '"');
                    }
                    if(cols.length > 0) csv.push(row.join(","));
                }
            }

            // Download CSV file
            var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>
