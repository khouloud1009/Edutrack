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

// Get filter parameters
$filterMonth = $_GET['month'] ?? date('Y-m');
$filterCourse = $_GET['course'] ?? '';

// Get student's attendance with filters
try {
    $whereClause = "WHERE att.id_etu = ?";
    $params = [$studentId];
    
    if (!empty($filterMonth)) {
        $whereClause .= " AND DATE_FORMAT(att.date, '%Y-%m') = ?";
        $params[] = $filterMonth;
    }
    
    if (!empty($filterCourse)) {
        $whereClause .= " AND att.id_course = ?";
        $params[] = $filterCourse;
    }
    
    $stmt = executeQuery("
        SELECT att.*, c.titre as course_title, c.niveau 
        FROM attendance att 
        LEFT JOIN cours c ON att.id_course = c.id_course 
        $whereClause 
        ORDER BY att.date DESC, att.heure DESC
    ", $params);
    $attendance = $stmt->fetchAll();
} catch (Exception $e) {
    $attendance = [];
    error_log("Get attendance error: " . $e->getMessage());
}

// Get available courses for filter
try {
    $stmt = executeQuery("
        SELECT DISTINCT c.id_course, c.titre 
        FROM cours c 
        INNER JOIN attendance att ON c.id_course = att.id_course 
        WHERE att.id_etu = ? 
        ORDER BY c.titre
    ", [$studentId]);
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    $courses = [];
    error_log("Get courses error: " . $e->getMessage());
}

// Get attendance statistics
try {
    // Total attendance
    $stmt = executeQuery("SELECT COUNT(*) as total FROM attendance WHERE id_etu = ?", [$studentId]);
    $totalAttendance = $stmt->fetch()['total'];
    
    // This month attendance
    $stmt = executeQuery("
        SELECT COUNT(*) as total 
        FROM attendance 
        WHERE id_etu = ? 
        AND MONTH(date) = MONTH(CURRENT_DATE()) 
        AND YEAR(date) = YEAR(CURRENT_DATE())
    ", [$studentId]);
    $monthlyAttendance = $stmt->fetch()['total'];
    
    // This week attendance
    $stmt = executeQuery("
        SELECT COUNT(*) as total 
        FROM attendance 
        WHERE id_etu = ? 
        AND WEEK(date) = WEEK(CURRENT_DATE()) 
        AND YEAR(date) = YEAR(CURRENT_DATE())
    ", [$studentId]);
    $weeklyAttendance = $stmt->fetch()['total'];
    
    // Average attendance per month
    $stmt = executeQuery("
        SELECT COUNT(*) / COUNT(DISTINCT CONCAT(YEAR(date), '-', MONTH(date))) as average 
        FROM attendance 
        WHERE id_etu = ?
    ", [$studentId]);
    $averageMonthly = $stmt->fetch()['average'] ?? 0;
    
} catch (Exception $e) {
    $totalAttendance = 0;
    $monthlyAttendance = 0;
    $weeklyAttendance = 0;
    $averageMonthly = 0;
    error_log("Get statistics error: " . $e->getMessage());
}

// Get monthly attendance data for chart
try {
    $stmt = executeQuery("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            COUNT(*) as count
        FROM attendance 
        WHERE id_etu = ? 
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ", [$studentId]);
    $monthlyData = $stmt->fetchAll();
} catch (Exception $e) {
    $monthlyData = [];
    error_log("Get monthly data error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Présences - EduTrack</title>
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
                        <a class="nav-link active" href="attendance.php">
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success shadow">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="text-success mb-1">
                                    <i class="fas fa-calendar-check me-2"></i>
                                    Mes Présences
                                </h3>
                                <p class="text-muted mb-0">Suivi de votre assiduité et historique des présences</p>
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
                                    Total Présences
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalAttendance; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
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
                                    Ce Mois
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $monthlyAttendance; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
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
                                    <?php echo $weeklyAttendance; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
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
                                    Moyenne Mensuelle
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($averageMonthly, 1); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Attendance Chart -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-area me-2"></i>
                            Évolution des Présences
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="attendanceChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-filter me-2"></i>
                            Filtres
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="mb-3">
                                <label for="month" class="form-label">Mois</label>
                                <input type="month" 
                                       class="form-control" 
                                       id="month" 
                                       name="month" 
                                       value="<?php echo htmlspecialchars($filterMonth); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="course" class="form-label">Cours</label>
                                <select class="form-control" id="course" name="course">
                                    <option value="">Tous les cours</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id_course']; ?>" 
                                                <?php echo $filterCourse == $course['id_course'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['titre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filtrer
                                </button>
                                <a href="attendance.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Réinitialiser
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance List -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>
                            Historique des Présences
                            <span class="badge bg-primary ms-2"><?php echo count($attendance); ?></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($attendance)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucune présence enregistrée</h5>
                                <p class="text-muted">Aucune présence ne correspond aux critères sélectionnés.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-success">
                                        <tr>
                                            <th><i class="fas fa-calendar me-1"></i>Date</th>
                                            <th><i class="fas fa-clock me-1"></i>Heure</th>
                                            <th><i class="fas fa-book me-1"></i>Cours</th>
                                            <th><i class="fas fa-graduation-cap me-1"></i>Niveau</th>
                                            <th><i class="fas fa-check me-1"></i>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($attendance as $record): ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold">
                                                        <?php echo date('d/m/Y', strtotime($record['date'])); ?>
                                                    </span>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php 
                                                        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                                                        echo $days[date('w', strtotime($record['date']))];
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo date('H:i', strtotime($record['heure'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($record['course_title'] ?? 'Non défini'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($record['niveau'] ?? 'Non défini'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Présent
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Attendance Chart
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        const monthlyData = <?php echo json_encode(array_reverse($monthlyData)); ?>;
        
        const labels = monthlyData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long' });
        });
        
        const data = monthlyData.map(item => item.count);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Présences',
                    data: data,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    fill: true
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
