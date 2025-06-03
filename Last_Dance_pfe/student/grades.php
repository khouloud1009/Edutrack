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
$filterCourse = $_GET['course'] ?? '';
$filterMatiere = $_GET['matiere'] ?? '';

// Get student's grades with filters
try {
    $whereClause = "WHERE g.id_etu = ?";
    $params = [$studentId];
    
    if (!empty($filterCourse)) {
        $whereClause .= " AND g.id_course = ?";
        $params[] = $filterCourse;
    }
    
    if (!empty($filterMatiere)) {
        $whereClause .= " AND g.matiere LIKE ?";
        $params[] = "%$filterMatiere%";
    }
    
    $stmt = executeQuery("
        SELECT g.*, c.titre as course_title, c.niveau, a.nom as admin_name 
        FROM grades g 
        LEFT JOIN cours c ON g.id_course = c.id_course 
        LEFT JOIN admins a ON g.id_admin = a.id_admin 
        $whereClause 
        ORDER BY g.matiere, c.titre
    ", $params);
    $grades = $stmt->fetchAll();
} catch (Exception $e) {
    $grades = [];
    error_log("Get grades error: " . $e->getMessage());
}

// Get available courses for filter
try {
    $stmt = executeQuery("
        SELECT DISTINCT c.id_course, c.titre 
        FROM cours c 
        INNER JOIN grades g ON c.id_course = g.id_course 
        WHERE g.id_etu = ? 
        ORDER BY c.titre
    ", [$studentId]);
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    $courses = [];
    error_log("Get courses error: " . $e->getMessage());
}

// Get available matieres for filter
try {
    $stmt = executeQuery("
        SELECT DISTINCT matiere 
        FROM grades 
        WHERE id_etu = ? 
        ORDER BY matiere
    ", [$studentId]);
    $matieres = $stmt->fetchAll();
} catch (Exception $e) {
    $matieres = [];
    error_log("Get matieres error: " . $e->getMessage());
}

// Calculate statistics
$totalGrades = count($grades);
$averageGrade = $totalGrades > 0 ? array_sum(array_column($grades, 'grade')) / $totalGrades : 0;
$maxGrade = $totalGrades > 0 ? max(array_column($grades, 'grade')) : 0;
$minGrade = $totalGrades > 0 ? min(array_column($grades, 'grade')) : 0;

// Grade distribution
$gradeRanges = [
    'Excellent (18-20)' => 0,
    'Très Bien (16-18)' => 0,
    'Bien (14-16)' => 0,
    'Assez Bien (12-14)' => 0,
    'Passable (10-12)' => 0,
    'Insuffisant (0-10)' => 0
];

foreach ($grades as $grade) {
    $g = $grade['grade'];
    if ($g >= 18) $gradeRanges['Excellent (18-20)']++;
    elseif ($g >= 16) $gradeRanges['Très Bien (16-18)']++;
    elseif ($g >= 14) $gradeRanges['Bien (14-16)']++;
    elseif ($g >= 12) $gradeRanges['Assez Bien (12-14)']++;
    elseif ($g >= 10) $gradeRanges['Passable (10-12)']++;
    else $gradeRanges['Insuffisant (0-10)']++;
}

// Group grades by subject
$gradesBySubject = [];
foreach ($grades as $grade) {
    $subject = $grade['matiere'];
    if (!isset($gradesBySubject[$subject])) {
        $gradesBySubject[$subject] = [];
    }
    $gradesBySubject[$subject][] = $grade;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Notes - EduTrack</title>
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
                        <a class="nav-link active" href="grades.php">
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
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success shadow">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="text-success mb-1">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Mes Notes
                                </h3>
                                <p class="text-muted mb-0">Suivi de vos résultats académiques et performances</p>
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
                                    Moyenne Générale
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalGrades > 0 ? number_format($averageGrade, 2) : '0'; ?>/20
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                                    Meilleure Note
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($maxGrade, 2); ?>/20
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-trophy fa-2x text-gray-300"></i>
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
                                    Note la Plus Basse
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo number_format($minGrade, 2); ?>/20
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
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
                                    Total des Notes
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $totalGrades; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Grade Distribution Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i>
                            Répartition des Notes
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="gradeDistributionChart" width="400" height="400"></canvas>
                    </div>
                </div>
            </div>

            <!-- Filters and Subject Averages -->
            <div class="col-lg-6 mb-4">
                <!-- Filters -->
                <div class="card shadow mb-3">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-filter me-2"></i>
                            Filtres
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
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
                                
                                <div class="col-md-6 mb-3">
                                    <label for="matiere" class="form-label">Matière</label>
                                    <select class="form-control" id="matiere" name="matiere">
                                        <option value="">Toutes les matières</option>
                                        <?php foreach ($matieres as $matiere): ?>
                                            <option value="<?php echo htmlspecialchars($matiere['matiere']); ?>" 
                                                    <?php echo $filterMatiere == $matiere['matiere'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($matiere['matiere']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filtrer
                                </button>
                                <a href="grades.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Réinitialiser
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Subject Averages -->
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-calculator me-2"></i>
                            Moyennes par Matière
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($gradesBySubject)): ?>
                            <p class="text-muted text-center">Aucune note disponible</p>
                        <?php else: ?>
                            <?php foreach ($gradesBySubject as $subject => $subjectGrades): ?>
                                <?php 
                                $subjectAverage = array_sum(array_column($subjectGrades, 'grade')) / count($subjectGrades);
                                $progressPercentage = ($subjectAverage / 20) * 100;
                                $progressClass = $subjectAverage >= 16 ? 'bg-success' : 
                                               ($subjectAverage >= 14 ? 'bg-info' : 
                                               ($subjectAverage >= 12 ? 'bg-warning' : 
                                               ($subjectAverage >= 10 ? 'bg-primary' : 'bg-danger')));
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold"><?php echo htmlspecialchars($subject); ?></span>
                                        <span class="badge <?php echo $progressClass; ?>">
                                            <?php echo number_format($subjectAverage, 2); ?>/20
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar <?php echo $progressClass; ?>" 
                                             style="width: <?php echo $progressPercentage; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo count($subjectGrades); ?> note(s)</small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grades Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-list me-2"></i>
                            Détail des Notes
                            <span class="badge bg-primary ms-2"><?php echo count($grades); ?></span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($grades)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Aucune note disponible</h5>
                                <p class="text-muted">Aucune note ne correspond aux critères sélectionnés.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-success">
                                        <tr>
                                            <th><i class="fas fa-book me-1"></i>Matière</th>
                                            <th><i class="fas fa-graduation-cap me-1"></i>Cours</th>
                                            <th><i class="fas fa-chart-line me-1"></i>Note</th>
                                            <th><i class="fas fa-layer-group me-1"></i>Niveau</th>
                                            <th><i class="fas fa-user-tie me-1"></i>Évaluateur</th>
                                            <th><i class="fas fa-medal me-1"></i>Mention</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grades as $grade): ?>
                                            <?php
                                            $gradeValue = $grade['grade'];
                                            $badgeClass = $gradeValue >= 16 ? 'bg-success' : 
                                                         ($gradeValue >= 14 ? 'bg-info' : 
                                                         ($gradeValue >= 12 ? 'bg-warning' : 
                                                         ($gradeValue >= 10 ? 'bg-primary' : 'bg-danger')));
                                            
                                            $mention = $gradeValue >= 18 ? 'Excellent' :
                                                      ($gradeValue >= 16 ? 'Très Bien' :
                                                      ($gradeValue >= 14 ? 'Bien' :
                                                      ($gradeValue >= 12 ? 'Assez Bien' :
                                                      ($gradeValue >= 10 ? 'Passable' : 'Insuffisant'))));
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold"><?php echo htmlspecialchars($grade['matiere']); ?></span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($grade['course_title'] ?? 'Non défini'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $badgeClass; ?> fs-6">
                                                        <?php echo number_format($gradeValue, 2); ?>/20
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($grade['niveau'] ?? 'Non défini'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($grade['admin_name'] ?? 'Non défini'); ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $badgeClass; ?>">
                                                        <?php echo $mention; ?>
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
        // Grade Distribution Chart
        const ctx = document.getElementById('gradeDistributionChart').getContext('2d');
        const gradeRanges = <?php echo json_encode($gradeRanges); ?>;
        
        const labels = Object.keys(gradeRanges);
        const data = Object.values(gradeRanges);
        const colors = [
            '#28a745', // Excellent - Green
            '#17a2b8', // Très Bien - Teal
            '#6f42c1', // Bien - Purple
            '#fd7e14', // Assez Bien - Orange
            '#ffc107', // Passable - Yellow
            '#dc3545'  // Insuffisant - Red
        ];
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
