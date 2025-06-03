<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

// Get filter parameters
$selectedSession = $_GET['session'] ?? '';
$selectedDate = $_GET['date'] ?? '';
$exportRequested = isset($_GET['export']);

// Get admin's sessions
try {
    $stmt = executeQuery(
        "SELECT s.*, c.titre as course_title FROM attendance_sessions s 
         LEFT JOIN cours c ON s.id_course = c.id_course 
         WHERE s.id_admin = ? ORDER BY s.start_time DESC",
        [getCurrentUserId()]
    );
    $sessions = $stmt->fetchAll();
} catch (Exception $e) {
    $sessions = [];
}

// Handle CSV export
if ($exportRequested) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Time', 'Date', 'Course', 'Status']);
    
    // Export CSV data
    $csvAttendance = loadAttendanceFromCSV();
    foreach ($csvAttendance as $record) {
        if (!empty($record['name'])) {
            fputcsv($output, [
                $record['name'],
                $record['time'],
                $record['date'],
                'Face Recognition',
                'Recognized'
            ]);
        }
    }
    
    // Export database records
    $dbAttendance = loadAttendanceFromDB($selectedSession, $selectedDate);
    foreach ($dbAttendance as $record) {
        fputcsv($output, [
            $record['nom'] . ' ' . $record['prenom'],
            date('H:i', strtotime($record['heure'])),
            date('d/m/Y', strtotime($record['date'])),
            $record['course_title'] ?? 'N/A',
            'Confirmed'
        ]);
    }
    
    fclose($output);
    exit;
}

// Load attendance data functions
function loadAttendanceFromCSV() {
    $attendanceData = [];
    $csvFile = '../Attendance.csv';
    
    if (file_exists($csvFile)) {
        $file = fopen($csvFile, 'r');
        while (($data = fgetcsv($file))) {
            if (count($data) >= 3) {
                $attendanceData[] = [
                    'name' => trim($data[0]),
                    'time' => trim($data[1]),
                    'date' => trim($data[2])
                ];
            }
        }
        fclose($file);
    }
    return $attendanceData;
}

function loadAttendanceFromDB($sessionId = '', $date = '') {
    try {
        $query = "
            SELECT att.*, e.nom, e.prenom, c.titre as course_title, s.start_time as session_start
            FROM attendance att 
            LEFT JOIN etudiants e ON att.id_etu = e.id_etu 
            LEFT JOIN cours c ON att.id_course = c.id_course 
            LEFT JOIN attendance_sessions s ON att.id_session = s.id_session 
            WHERE att.id_admin = ?
        ";
        
        $params = [getCurrentUserId()];
        
        if ($sessionId) {
            $query .= " AND att.id_session = ?";
            $params[] = $sessionId;
        }
        
        if ($date) {
            $query .= " AND att.date = ?";
            $params[] = $date;
        }
        
        $query .= " ORDER BY att.date DESC, att.heure DESC";
        
        $stmt = executeQuery($query, $params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Database attendance error: " . $e->getMessage());
        return [];
    }
}

// Load attendance data
$csvAttendance = loadAttendanceFromCSV();
$dbAttendance = loadAttendanceFromDB($selectedSession, $selectedDate);

// Get statistics
$totalCsvRecords = count($csvAttendance);
$totalDbRecords = count($dbAttendance);
$todayDate = date('Y-m-d');
$todayAttendance = array_filter($dbAttendance, function($record) use ($todayDate) {
    return isset($record['date']) && $record['date'] === $todayDate;
});
$todayCount = count($todayAttendance);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation des Présences - EduTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/admin-professional.css" rel="stylesheet">
    <style>
        .attendance-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .camera-icon {
            font-size: 1.2rem;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid py-4">
        <div class="page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Consultation des Présences</h1>
                    <p class="page-subtitle">Données de présence par reconnaissance faciale et manuelles</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="attendance_session.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Retour
                    </a>
                    <button class="btn btn-success" onclick="refreshData()">
                        <i class="fas fa-sync-alt me-2"></i>Actualiser
                    </button>
                    <a href="?export=csv&<?php echo http_build_query($_GET); ?>" class="btn btn-info">
                        <i class="fas fa-download me-2"></i>Exporter
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title"><?php echo $totalCsvRecords; ?></h3>
                                <p class="card-text mb-0">Reconnaissance Faciale</p>
                            </div>
                            <i class="fas fa-camera fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title"><?php echo $totalDbRecords; ?></h3>
                                <p class="card-text mb-0">Base de Données</p>
                            </div>
                            <i class="fas fa-database fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title"><?php echo $todayCount; ?></h3>
                                <p class="card-text mb-0">Aujourd'hui</p>
                            </div>
                            <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="card-title"><?php echo count($sessions); ?></h3>
                                <p class="card-text mb-0">Sessions</p>
                            </div>
                            <i class="fas fa-list fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="session" class="form-label">Session</label>
                        <select class="form-select" id="session" name="session">
                            <option value="">Toutes les sessions</option>
                            <?php foreach ($sessions as $session): ?>
                                <option value="<?php echo $session['id_session']; ?>" 
                                    <?php echo $selectedSession == $session['id_session'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(
                                        $session['course_title'] . ' - ' . 
                                        date('d/m/Y H:i', strtotime($session['start_time']))
                                    ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="date" name="date" 
                               value="<?php echo htmlspecialchars($selectedDate); ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-1"></i> Filtrer
                        </button>
                        <a href="face_attendance_view.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Face Recognition Attendance -->
        <div class="card shadow mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-camera me-2"></i>Reconnaissance Faciale
                </h5>
                <span class="badge bg-primary attendance-badge">
                    <?php echo $totalCsvRecords; ?> enregistrements
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($csvAttendance)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-camera-retro fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune présence détectée</h5>
                        <p class="text-muted">Les présences capturées par reconnaissance faciale apparaîtront ici.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Heure</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($csvAttendance as $record): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($record['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['time']); ?></td>
                                        <td><?php echo htmlspecialchars($record['date']); ?></td>
                                        <td>
                                            <span class="badge bg-success attendance-badge">
                                                <i class="fas fa-check-circle me-1"></i> Reconnu
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Database Attendance -->
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-database me-2"></i>Base de Données
                </h5>
                <span class="badge bg-success attendance-badge">
                    <?php echo $totalDbRecords; ?> enregistrements
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($dbAttendance)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-database fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune présence enregistrée</h5>
                        <p class="text-muted">Les présences manuelles ou importées apparaîtront ici.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Cours</th>
                                    <th>Date</th>
                                    <th>Heure</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dbAttendance as $record): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($record['nom'] . ' ' . $record['prenom']); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($record['course_title'])): ?>
                                                <span class="badge bg-info text-dark attendance-badge">
                                                    <?php echo htmlspecialchars($record['course_title']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary attendance-badge">Non défini</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($record['date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($record['heure'])); ?></td>
                                        <td>
                                            <span class="badge bg-primary attendance-badge">
                                                <i class="fas fa-user-check me-1"></i> Confirmé
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function refreshData() {
            location.reload();
        }

        // Auto-refresh every 30 seconds when viewing a specific session
        <?php if (!empty($selectedSession)): ?>
            setTimeout(function() {
                location.reload();
            }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>