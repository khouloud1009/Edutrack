<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireStudent();

$studentId = getCurrentUserId();

// Get student information
try {
    $stmt = executeQuery("SELECT * FROM etudiants WHERE id_etu = ?", [$studentId]);
    $student = $stmt->fetch();
} catch (Exception $e) {
    $student = null;
    error_log("Get student error: " . $e->getMessage());
}

// Get student's grades
try {
    $stmt = executeQuery("
        SELECT g.*, c.titre as course_title, a.nom as admin_name 
        FROM grades g 
        LEFT JOIN cours c ON g.id_course = c.id_course 
        LEFT JOIN admins a ON g.id_admin = a.id_admin 
        WHERE g.id_etu = ? 
        ORDER BY g.matiere, c.titre
    ", [$studentId]);
    $grades = $stmt->fetchAll();
} catch (Exception $e) {
    $grades = [];
    error_log("Get grades error: " . $e->getMessage());
}

// Get student's attendance
try {
    $stmt = executeQuery("
        SELECT att.*, c.titre as course_title 
        FROM attendance att 
        LEFT JOIN cours c ON att.id_course = c.id_course 
        WHERE att.id_etu = ? 
        ORDER BY att.date DESC, att.heure DESC 
        LIMIT 20
    ", [$studentId]);
    $attendance = $stmt->fetchAll();
} catch (Exception $e) {
    $attendance = [];
    error_log("Get attendance error: " . $e->getMessage());
}

// Get events for student's level
try {
    $stmt = executeQuery("
        SELECT e.*, c.titre as course_title, a.nom as admin_name 
        FROM events e 
        LEFT JOIN cours c ON e.id_course = c.id_course 
        LEFT JOIN admins a ON e.id_admin = a.id_admin 
        WHERE c.niveau = ? OR e.id_course IS NULL 
        ORDER BY e.date ASC 
        LIMIT 10
    ", [$student['niveau'] ?? '']);
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    $events = [];
    error_log("Get events error: " . $e->getMessage());
}

// Calculate statistics
$totalGrades = count($grades);
$averageGrade = $totalGrades > 0 ? array_sum(array_column($grades, 'grade')) / $totalGrades : 0;
$totalAttendance = count($attendance);
$attendanceThisMonth = count(array_filter($attendance, function($att) {
    return date('Y-m', strtotime($att['date'])) === date('Y-m');
}));

// Get monthly attendance for chart
try {
    $stmt = executeQuery("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            COUNT(*) as count
        FROM attendance 
        WHERE id_etu = ? 
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 6
    ", [$studentId]);
    $monthlyAttendance = $stmt->fetchAll();
} catch (Exception $e) {
    $monthlyAttendance = [];
    error_log("Get monthly attendance error: " . $e->getMessage());
}

// Get grade distribution for chart
$gradeRanges = [
    'Excellent' => 0,
    'Très Bien' => 0,
    'Bien' => 0,
    'Passable' => 0,
    'Insuffisant' => 0
];

foreach ($grades as $grade) {
    $g = $grade['grade'];
    if ($g >= 18) $gradeRanges['Excellent']++;
    elseif ($g >= 16) $gradeRanges['Très Bien']++;
    elseif ($g >= 14) $gradeRanges['Bien']++;
    elseif ($g >= 10) $gradeRanges['Passable']++;
    else $gradeRanges['Insuffisant']++;
}

// Filter upcoming events
$upcomingEvents = array_filter($events, function($event) {
    return $event['date'] >= date('Y-m-d');
});

// Create date formatter for French locale
$dateFormatter = new IntlDateFormatter(
    'fr_FR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    null,
    null,
    'EEEE d MMMM Y'
);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Étudiant - EduTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/professional-style.css" rel="stylesheet">
    <link href="../assets/css/animations.css" rel="stylesheet">
    <link href="../assets/css/responsive.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap me-2"></i>
                EduTrack Étudiant
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Tableau de Bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="grades.php">
                            <i class="fas fa-chart-line me-1"></i>Mes Notes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">
                            <i class="fas fa-calendar-check me-1"></i>Mes Présences
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="fas fa-calendar me-1"></i>Événements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i>Mon Profil
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-professional fade-in-up card-hover-effect">
                    <div class="card-body bg-gradient-subtle">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="text-gradient mb-1">
                                    <i class="fas fa-hand-wave me-2"></i>
                                    Bienvenue, <?php echo htmlspecialchars($student['prenom']); ?>!
                                </h3>
                                <p class="text-muted mb-0">
                                    <strong>Niveau:</strong> <?php echo htmlspecialchars($student['niveau']); ?> |
                                    <strong>ID Étudiant:</strong> <?php echo htmlspecialchars($student['id_etu']); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo $dateFormatter->format(new DateTime()); ?>
                                </div>
                                <div class="session-timer mt-2">
                                    <i class="fas fa-clock me-1"></i>
                                    <span id="session-time">Session active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4 stagger-animation">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-primary shadow-professional h-100 py-2 hover-lift">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Moyenne Générale
                                </div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalGrades > 0 ? number_format($averageGrade, 1) . '/20' : 'N/A'; ?>
                                </div>
                                <div class="progress mt-2" style="height: 4px;">
                                    <div class="progress-bar bg-primary" 
                                         style="width: <?php echo $totalGrades > 0 ? ($averageGrade / 20) * 100 : 0; ?>%"></div>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-primary opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-success shadow-professional h-100 py-2 hover-lift">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Notes
                                </div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalGrades; ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo $totalGrades > 0 ? 'Dernière: ' . end($grades)['matiere'] : 'Aucune note'; ?>
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-success opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-info shadow-professional h-100 py-2 hover-lift">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Présences Totales
                                </div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalAttendance; ?>
                                </div>
                                <small class="text-muted">
                                    Taux: <?php echo $totalAttendance > 0 ? '95%' : '0%'; ?>
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-check fa-2x text-info opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-warning shadow-professional h-100 py-2 hover-lift">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Présences ce Mois
                                </div>
                                <div class="h4 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $attendanceThisMonth; ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('F Y'); ?>
                                </small>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-day fa-2x text-warning opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Attendance Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-area me-2"></i>
                            Évolution des Présences (6 derniers mois)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grade Distribution -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i>
                            Répartition des Notes
                        </h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="gradesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            <!-- Recent Grades -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-line me-2"></i>
                            Notes Récentes
                        </h6>
                        <a href="grades.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>Voir tout
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($grades)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Aucune note disponible</h6>
                                <p class="text-muted">Vos notes apparaîtront ici une fois qu'elles seront saisies.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($grades, 0, 5) as $grade): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($grade['matiere']); ?></h6>
                                                <p class="mb-1 text-muted small">
                                                    <?php echo htmlspecialchars($grade['course_title'] ?? 'Non défini'); ?>
                                                </p>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge <?php echo $grade['grade'] >= 16 ? 'bg-success' : ($grade['grade'] >= 14 ? 'bg-info' : ($grade['grade'] >= 12 ? 'bg-warning' : ($grade['grade'] >= 10 ? 'bg-primary' : 'bg-danger'))); ?> fs-6">
                                                    <?php echo number_format($grade['grade'], 1); ?>/20
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance & Upcoming Events -->
            <div class="col-lg-6">
                <!-- Recent Attendance -->
                <div class="card shadow mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="fas fa-calendar-check me-2"></i>
                            Présences Récentes
                        </h6>
                        <a href="attendance.php" class="btn btn-success btn-sm">
                            <i class="fas fa-eye me-1"></i>Voir tout
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($attendance)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Aucune présence enregistrée</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($attendance, 0, 3) as $record): ?>
                                    <div class="list-group-item border-0 px-0 attendance-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold text-success">
                                                    <?php echo date('d/m/Y', strtotime($record['date'])); ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($record['course_title'] ?? 'Cours non défini'); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-info">
                                                    <?php echo date('H:i', strtotime($record['heure'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-warning">
                            <i class="fas fa-calendar me-2"></i>
                            Événements à Venir
                        </h6>
                        <a href="events.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-eye me-1"></i>Voir tout
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingEvents)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-calendar-plus fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Aucun événement à venir</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($upcomingEvents, 0, 3) as $event): ?>
                                    <?php
                                    $eventDate = new DateTime($event['date']);
                                    $now = new DateTime();
                                    $diff = $now->diff($eventDate);
                                    $daysUntil = $diff->days;
                                    ?>
                                    <div class="list-group-item border-0 px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 text-warning">
                                                    <?php echo htmlspecialchars($event['course_title'] ?? 'Événement Général'); ?>
                                                </h6>
                                                <p class="mb-1 small"><?php echo htmlspecialchars($event['description']); ?></p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo $eventDate->format('d/m/Y'); ?>
                                                    (dans <?php echo $daysUntil; ?> jour<?php echo $daysUntil > 1 ? 's' : ''; ?>)
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-dark">
                            <i class="fas fa-bolt me-2"></i>
                            Actions Rapides
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="grades.php" class="btn btn-outline-primary btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                    <span>Consulter mes Notes</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="attendance.php" class="btn btn-outline-success btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <span>Voir mes Présences</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="events.php" class="btn btn-outline-warning btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-calendar fa-2x mb-2"></i>
                                    <span>Événements</span>
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="profile.php" class="btn btn-outline-info btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                    <i class="fas fa-user fa-2x mb-2"></i>
                                    <span>Mon Profil</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/professional-interactions.js"></script>
    <script>
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            // Attendance Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            const monthlyAttendanceData = <?php echo json_encode(array_reverse($monthlyAttendance)); ?>;
            
            const attendanceLabels = monthlyAttendanceData.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long' });
            });
            
            const attendanceData = monthlyAttendanceData.map(item => item.count);
            
            new Chart(attendanceCtx, {
                type: 'line',
                data: {
                    labels: attendanceLabels,
                    datasets: [{
                        label: 'Présences',
                        data: attendanceData,
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(40, 167, 69, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    elements: {
                        point: {
                            hoverRadius: 8
                        }
                    }
                }
            });

            // Grades Chart
            const gradesCtx = document.getElementById('gradesChart').getContext('2d');
            const gradeRanges = <?php echo json_encode($gradeRanges); ?>;
            
            const gradeLabels = Object.keys(gradeRanges);
            const gradeData = Object.values(gradeRanges);
            const gradeColors = [
                '#28a745', // Excellent
                '#17a2b8', // Très Bien
                '#6f42c1', // Bien
                '#ffc107', // Passable
                '#dc3545'  // Insuffisant
            ];
            
            new Chart(gradesCtx, {
                type: 'doughnut',
                data: {
                    labels: gradeLabels,
                    datasets: [{
                        data: gradeData,
                        backgroundColor: gradeColors,
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    cutout: '50%'
                }
            });

            // Session timer
            let sessionStart = new Date();
            function updateSessionTimer() {
                const now = new Date();
                const elapsed = Math.floor((now - sessionStart) / 1000);
                const hours = Math.floor(elapsed / 3600);
                const minutes = Math.floor((elapsed % 3600) / 60);
                const seconds = elapsed % 60;
                
                document.getElementById('session-time').textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            
            setInterval(updateSessionTimer, 1000);
            updateSessionTimer();

            // Add animation to cards on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'fadeIn 0.6s ease-out';
                    }
                });
            });

            document.querySelectorAll('.card').forEach(card => {
                observer.observe(card);
            });
        });
    </script>
</body>
</html>
