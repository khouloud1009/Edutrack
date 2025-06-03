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

// Get events for student's level and general events
try {
    $stmt = executeQuery("
        SELECT e.*, c.titre as course_title, c.niveau, a.nom as admin_name 
        FROM events e 
        LEFT JOIN cours c ON e.id_course = c.id_course 
        LEFT JOIN admins a ON e.id_admin = a.id_admin 
        WHERE c.niveau = ? OR e.id_course IS NULL 
        ORDER BY e.date ASC
    ", [$student['niveau'] ?? '']);
    $allEvents = $stmt->fetchAll();
} catch (Exception $e) {
    $allEvents = [];
    error_log("Get events error: " . $e->getMessage());
}

// Separate events into categories
$upcomingEvents = [];
$todayEvents = [];
$pastEvents = [];
$today = date('Y-m-d');

foreach ($allEvents as $event) {
    if ($event['date'] == $today) {
        $todayEvents[] = $event;
    } elseif ($event['date'] > $today) {
        $upcomingEvents[] = $event;
    } else {
        $pastEvents[] = $event;
    }
}

// Get event statistics
$totalEvents = count($allEvents);
$upcomingCount = count($upcomingEvents);
$todayCount = count($todayEvents);
$thisWeekEvents = count(array_filter($allEvents, function($event) {
    $eventDate = new DateTime($event['date']);
    $startOfWeek = new DateTime('monday this week');
    $endOfWeek = new DateTime('sunday this week');
    return $eventDate >= $startOfWeek && $eventDate <= $endOfWeek;
}));

// Get monthly event count for chart
try {
    $stmt = executeQuery("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            COUNT(*) as count
        FROM events e
        LEFT JOIN cours c ON e.id_course = c.id_course 
        WHERE c.niveau = ? OR e.id_course IS NULL
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ", [$student['niveau'] ?? '']);
    $monthlyEventData = $stmt->fetchAll();
} catch (Exception $e) {
    $monthlyEventData = [];
    error_log("Get monthly event data error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements - EduTrack</title>
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
                        <a class="nav-link" href="dashboard.php">
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
                        <a class="nav-link active" href="events.php">
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success shadow">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="text-success mb-1">
                                    <i class="fas fa-calendar me-2"></i>
                                    Événements
                                </h3>
                                <p class="text-muted mb-0">Calendrier des événements académiques et activités</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Événements
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalEvents; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    À Venir
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $upcomingCount; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Aujourd'hui
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $todayCount; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-star fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Cette Semaine
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $thisWeekEvents; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Today's Events -->
            <?php if (!empty($todayEvents)): ?>
            <div class="col-12 mb-4">
                <div class="card shadow border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-star me-2"></i>
                            Événements d'Aujourd'hui
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($todayEvents as $event): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border-warning h-100">
                                        <div class="card-body">
                                            <h6 class="card-title text-warning">
                                                <i class="fas fa-star me-1"></i>
                                                <?php echo htmlspecialchars($event['course_title'] ?? 'Événement Général'); ?>
                                            </h6>
                                            <p class="card-text"><?php echo htmlspecialchars($event['description']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?php echo htmlspecialchars($event['admin_name'] ?? 'Admin'); ?>
                                                </small>
                                                <span class="badge bg-warning text-dark">Aujourd'hui</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upcoming Events -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-calendar-plus me-2"></i>
                            Événements à Venir
                            <span class="badge bg-primary ms-2"><?php echo count($upcomingEvents); ?></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcomingEvents)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Aucun événement à venir</h6>
                                <p class="text-muted">Aucun événement n'est programmé pour le moment.</p>
                            </div>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach (array_slice($upcomingEvents, 0, 10) as $index => $event): ?>
                                    <?php
                                    $eventDate = new DateTime($event['date']);
                                    $now = new DateTime();
                                    $diff = $now->diff($eventDate);
                                    $daysUntil = $diff->days;
                                    ?>
                                    <div class="timeline-item mb-4">
                                        <div class="row">
                                            <div class="col-md-3 text-center">
                                                <div class="event-date bg-primary text-white rounded p-2">
                                                    <div class="h5 mb-0"><?php echo $eventDate->format('d'); ?></div>
                                                    <div class="small"><?php echo $eventDate->format('M Y'); ?></div>
                                                </div>
                                                <small class="text-muted">
                                                    Dans <?php echo $daysUntil; ?> jour<?php echo $daysUntil > 1 ? 's' : ''; ?>
                                                </small>
                                            </div>
                                            <div class="col-md-9">
                                                <div class="card border-primary">
                                                    <div class="card-body">
                                                        <h6 class="card-title text-primary">
                                                            <?php echo htmlspecialchars($event['course_title'] ?? 'Événement Général'); ?>
                                                        </h6>
                                                        <p class="card-text"><?php echo htmlspecialchars($event['description']); ?></p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">
                                                                <i class="fas fa-user me-1"></i>
                                                                <?php echo htmlspecialchars($event['admin_name'] ?? 'Admin'); ?>
                                                            </small>
                                                            <?php if (!empty($event['niveau'])): ?>
                                                                <span class="badge bg-secondary">
                                                                    <?php echo htmlspecialchars($event['niveau']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Event Statistics and Calendar -->
            <div class="col-lg-4 mb-4">
                <!-- Event Statistics Chart -->
                <div class="card shadow mb-3">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-bar me-2"></i>
                            Événements par Mois
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="eventChart" width="400" height="300"></canvas>
                    </div>
                </div>

                <!-- Quick Calendar -->
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Calendrier Rapide
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="calendar-widget">
                            <div class="text-center mb-3">
                                <h5 class="text-primary"><?php echo date('F Y'); ?></h5>
                            </div>
                            
                            <!-- Event Summary -->
                            <div class="event-summary">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Événements ce mois:</span>
                                    <span class="badge bg-info">
                                        <?php 
                                        $thisMonth = date('Y-m');
                                        $thisMonthEvents = count(array_filter($allEvents, function($event) use ($thisMonth) {
                                            return strpos($event['date'], $thisMonth) === 0;
                                        }));
                                        echo $thisMonthEvents;
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Événements la semaine prochaine:</span>
                                    <span class="badge bg-warning">
                                        <?php 
                                        $nextWeekStart = date('Y-m-d', strtotime('next monday'));
                                        $nextWeekEnd = date('Y-m-d', strtotime('next sunday'));
                                        $nextWeekEvents = count(array_filter($allEvents, function($event) use ($nextWeekStart, $nextWeekEnd) {
                                            return $event['date'] >= $nextWeekStart && $event['date'] <= $nextWeekEnd;
                                        }));
                                        echo $nextWeekEvents;
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Past Events -->
        <?php if (!empty($pastEvents)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-secondary">
                            <i class="fas fa-history me-2"></i>
                            Événements Passés
                            <span class="badge bg-secondary ms-2"><?php echo count($pastEvents); ?></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach (array_slice(array_reverse($pastEvents), 0, 6) as $event): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card border-secondary h-100 opacity-75">
                                        <div class="card-body">
                                            <h6 class="card-title text-secondary">
                                                <?php echo htmlspecialchars($event['course_title'] ?? 'Événement Général'); ?>
                                            </h6>
                                            <p class="card-text small"><?php echo htmlspecialchars($event['description']); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($event['date'])); ?>
                                                </small>
                                                <span class="badge bg-secondary">Terminé</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Event Chart
        const ctx = document.getElementById('eventChart').getContext('2d');
        const monthlyEventData = <?php echo json_encode(array_reverse($monthlyEventData)); ?>;
        
        const labels = monthlyEventData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'short' });
        });
        
        const data = monthlyEventData.map(item => item.count);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Événements',
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
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
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
