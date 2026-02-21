<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

// Get dashboard statistics
try {
    // Count total students
    $stmt = executeQuery("SELECT COUNT(*) as count FROM etudiants");
    $totalStudents = $stmt->fetch()['count'];
    
    // Count total courses
    $stmt = executeQuery("SELECT COUNT(*) as count FROM cours");
    $totalCourses = $stmt->fetch()['count'];
    
    // Count total grades
    $stmt = executeQuery("SELECT COUNT(*) as count FROM grades");
    $totalGrades = $stmt->fetch()['count'];
    
    // Count attendance today
    $stmt = executeQuery("SELECT COUNT(*) as count FROM attendance WHERE date = CURDATE()");
    $todayAttendance = $stmt->fetch()['count'];
    
    // Get recent events
    $stmt = executeQuery("
        SELECT e.*, c.titre as course_title, a.nom as admin_name 
        FROM events e 
        LEFT JOIN cours c ON e.id_course = c.id_course 
        LEFT JOIN admins a ON e.id_admin = a.id_admin 
        ORDER BY e.date DESC 
        LIMIT 5
    ");
    $recentEvents = $stmt->fetchAll();
    
    // Get recent attendance
    $stmt = executeQuery("
        SELECT att.*, et.nom, et.prenom, c.titre as course_title 
        FROM attendance att 
        LEFT JOIN etudiants et ON att.id_etu = et.id_etu 
        LEFT JOIN cours c ON att.id_course = c.id_course 
        ORDER BY att.date DESC, att.heure DESC 
        LIMIT 10
    ");
    $recentAttendance = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Erreur lors du chargement des données.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - EduTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/admin-professional.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid py-4">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Tableau de Bord</h1>
                    <p class="page-subtitle">Vue d'ensemble de votre plateforme éducative</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt me-2"></i>Actualiser
                    </button>
                    <button class="btn btn-success" onclick="exportReport()">
                        <i class="fas fa-download me-2"></i>Rapport
                    </button>
                </div>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Enhanced Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card border-0 shadow-lg h-100 stats-card" style="background: var(--primary-gradient);">
                    <div class="card-body p-4 text-white position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="small text-white-50 text-uppercase fw-bold mb-2">
                                    Total Étudiants
                                </div>
                                <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                    <?php echo number_format($totalStudents ?? 0); ?>
                                </div>
                                <div class="small text-white-50 mt-2 d-flex align-items-center">
                                    <i class="fas fa-arrow-up me-1"></i>
                                    <span>Système global</span>
                                </div>
                            </div>
                            <div class="stats-icon ms-3">
                                <div class="icon-circle">
                                    <i class="fas fa-user-graduate fa-2x text-white"></i>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 4px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-white" style="width: 85%;" role="progressbar"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card border-0 shadow-lg h-100 stats-card" style="background: var(--success-gradient);">
                    <div class="card-body p-4 text-white position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="small text-white-50 text-uppercase fw-bold mb-2">
                                    Mes Cours
                                </div>
                                <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                    <?php echo number_format($totalCourses ?? 0); ?>
                                </div>
                                <div class="small text-white-50 mt-2 d-flex align-items-center">
                                    <i class="fas fa-plus me-1"></i>
                                    <span>Cours gérés</span>
                                </div>
                            </div>
                            <div class="stats-icon ms-3">
                                <div class="icon-circle">
                                    <i class="fas fa-book fa-2x text-white"></i>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 4px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-white" style="width: 70%;" role="progressbar"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card border-0 shadow-lg h-100 stats-card" style="background: var(--info-gradient);">
                    <div class="card-body p-4 text-white position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="small text-white-50 text-uppercase fw-bold mb-2">
                                    Notes Saisies
                                </div>
                                <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                    <?php echo number_format($totalGrades ?? 0); ?>
                                </div>
                                <div class="small text-white-50 mt-2 d-flex align-items-center">
                                    <i class="fas fa-chart-line me-1"></i>
                                    <span>Évaluations</span>
                                </div>
                            </div>
                            <div class="stats-icon ms-3">
                                <div class="icon-circle">
                                    <i class="fas fa-chart-bar fa-2x text-white"></i>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 4px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-white" style="width: 60%;" role="progressbar"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card border-0 shadow-lg h-100 stats-card" style="background: var(--warning-gradient);">
                    <div class="card-body p-4 text-white position-relative">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="small text-white-50 text-uppercase fw-bold mb-2">
                                    Présences Aujourd'hui
                                </div>
                                <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                    <?php echo number_format($todayAttendance ?? 0); ?>
                                </div>
                                <div class="small text-white-50 mt-2 d-flex align-items-center">
                                    <i class="fas fa-users me-1"></i>
                                    <span>Étudiants présents</span>
                                </div>
                            </div>
                            <div class="stats-icon ms-3">
                                <div class="icon-circle">
                                    <i class="fas fa-calendar-check fa-2x text-white"></i>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 4px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-white" style="width: 45%;" role="progressbar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Quick Actions Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                    <div class="card-header border-0 py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h4 class="mb-0 text-white fw-bold">
                            <i class="fas fa-rocket me-3"></i>Actions Rapides
                        </h4>
                        <p class="text-white-50 mb-0 mt-1">Accès direct aux fonctionnalités principales</p>
                    </div>
                    <div class="card-body p-4" style="background: #f8f9fa;">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <a href="students.php" class="text-decoration-none">
                                    <div class="action-card p-4 h-100 border-0 shadow-sm" style="background: white; border-radius: 15px; transition: all 0.3s ease;">
                                        <div class="text-center">
                                            <div class="action-icon mb-3" style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                <i class="fas fa-user-plus fa-lg text-white"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-2">Gérer Étudiants</h6>
                                            <p class="small text-muted mb-0">Ajouter, modifier ou supprimer des étudiants</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="courses.php" class="text-decoration-none">
                                    <div class="action-card p-4 h-100 border-0 shadow-sm" style="background: white; border-radius: 15px; transition: all 0.3s ease;">
                                        <div class="text-center">
                                            <div class="action-icon mb-3" style="width: 60px; height: 60px; background: linear-gradient(135deg, #11998e, #38ef7d); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                <i class="fas fa-book fa-lg text-white"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-2">Mes Cours</h6>
                                            <p class="small text-muted mb-0">Créer et organiser vos cours</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="grades.php" class="text-decoration-none">
                                    <div class="action-card p-4 h-100 border-0 shadow-sm" style="background: white; border-radius: 15px; transition: all 0.3s ease;">
                                        <div class="text-center">
                                            <div class="action-icon mb-3" style="width: 60px; height: 60px; background: linear-gradient(135deg, #4facfe, #00f2fe); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                <i class="fas fa-chart-line fa-lg text-white"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-2">Saisir Notes</h6>
                                            <p class="small text-muted mb-0">Évaluer et noter les étudiants</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="attendance_session.php" class="text-decoration-none">
                                    <div class="action-card p-4 h-100 border-0 shadow-sm" style="background: white; border-radius: 15px; transition: all 0.3s ease;">
                                        <div class="text-center">
                                            <div class="action-icon mb-3" style="width: 60px; height: 60px; background: linear-gradient(135deg, #f093fb, #f5576c); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                                <i class="fas fa-camera fa-lg text-white"></i>
                                            </div>
                                            <h6 class="fw-bold text-dark mb-2">Présences</h6>
                                            <p class="small text-muted mb-0">Gérer les sessions de présence</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Recent Activity -->
        <div class="row">
            <!-- Recent Events -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                    <div class="card-header border-0 py-4" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <h5 class="mb-0 text-white fw-bold">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Événements Récents
                        </h5>
                        <p class="text-white-50 mb-0 mt-1 small">Dernières activités planifiées</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentEvents)): ?>
                            <p class="text-muted text-center">Aucun événement récent</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentEvents as $event): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($event['description']); ?></h6>
                                            <small><?php echo date('d/m/Y', strtotime($event['date'])); ?></small>
                                        </div>
                                        <p class="mb-1">
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($event['course_title'] ?? 'Général'); ?>
                                            </small>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="events.php" class="btn btn-primary btn-sm">
                                Voir tous les événements
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="col-lg-6 mb-4">
                <div class="card border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                    <div class="card-header border-0 py-4" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <h5 class="mb-0 text-white fw-bold">
                            <i class="fas fa-user-check me-2"></i>
                            Présences Récentes
                        </h5>
                        <p class="text-white-50 mb-0 mt-1 small">Derniers enregistrements</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentAttendance)): ?>
                            <p class="text-muted text-center">Aucune présence récente</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentAttendance as $attendance): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($attendance['nom'] . ' ' . $attendance['prenom']); ?>
                                            </h6>
                                            <small>
                                                <?php echo date('d/m/Y', strtotime($attendance['date'])); ?>
                                                à <?php echo date('H:i', strtotime($attendance['heure'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($attendance['course_title'] ?? 'Non défini'); ?>
                                            </small>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="face_attendance_view.php" class="btn btn-primary btn-sm">
                                Voir toutes les présences
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-bolt me-2"></i>
                            Actions Rapides
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <a href="students.php" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fas fa-user-plus fa-2x mb-2 d-block"></i>
                                    Ajouter Étudiant
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="courses.php" class="btn btn-outline-success btn-lg w-100">
                                    <i class="fas fa-plus fa-2x mb-2 d-block"></i>
                                    Ajouter Cours
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="attendance_session.php" class="btn btn-outline-warning btn-lg w-100">
                                    <i class="fas fa-camera fa-2x mb-2 d-block"></i>
                                    Session Présence
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="events.php" class="btn btn-outline-info btn-lg w-100">
                                    <i class="fas fa-calendar-plus fa-2x mb-2 d-block"></i>
                                    Ajouter Événement
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Professional Dashboard JavaScript
        function refreshDashboard() {
            const btn = document.querySelector('[onclick="refreshDashboard()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualisation...';
            btn.disabled = true;
            
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
        
        function exportReport() {
            const btn = document.querySelector('[onclick="exportReport()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Export...';
            btn.disabled = true;
            
            setTimeout(() => {
                const data = [
                    ['Statistique', 'Valeur'],
                    ['Total Étudiants', '<?php echo $totalStudents ?? 0; ?>'],
                    ['Total Cours', '<?php echo $totalCourses ?? 0; ?>'],
                    ['Notes Saisies', '<?php echo $totalGrades ?? 0; ?>'],
                    ['Présences Aujourd\'hui', '<?php echo $todayAttendance ?? 0; ?>']
                ];
                
                const csvContent = data.map(row => row.join(',')).join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'rapport-dashboard-' + new Date().toISOString().split('T')[0] + '.csv';
                a.click();
                URL.revokeObjectURL(url);
                
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1500);
        }
        
        function showProfile() {
            alert('Fonctionnalité de profil en développement');
        }
        
        function showSettings() {
            alert('Paramètres en développement');
        }
        
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.stats-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
