<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireStudent();

$studentId = getCurrentUserId();
$success = '';
$error = '';

// Get student information
try {
    $stmt = executeQuery("SELECT * FROM etudiants WHERE id_etu = ?", [$studentId]);
    $student = $stmt->fetch();
} catch (Exception $e) {
    $student = null;
    error_log("Get student error: " . $e->getMessage());
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $niveau = trim($_POST['niveau'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($nom) || empty($prenom) || empty($niveau)) {
        $error = 'Tous les champs obligatoires doivent être remplis.';
    } else {
        try {
            // Update basic info
            $updateQuery = "UPDATE etudiants SET nom = ?, prenom = ?, niveau = ? WHERE id_etu = ?";
            $updateParams = [$nom, $prenom, $niveau, $studentId];
            
            // Check if password update is requested
            if (!empty($currentPassword) && !empty($newPassword)) {
                if ($newPassword !== $confirmPassword) {
                    $error = 'Les nouveaux mots de passe ne correspondent pas.';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
                } else {
                    // Verify current password
                    if (password_verify($currentPassword, $student['password'])) {
                        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updateQuery = "UPDATE etudiants SET nom = ?, prenom = ?, niveau = ?, password = ? WHERE id_etu = ?";
                        $updateParams = [$nom, $prenom, $niveau, $hashedPassword, $studentId];
                    } else {
                        $error = 'Le mot de passe actuel est incorrect.';
                    }
                }
            }
            
            if (empty($error)) {
                executeQuery($updateQuery, $updateParams);
                
                // Update session name if changed
                $_SESSION['user_name'] = $nom . ' ' . $prenom;
                
                // Refresh student data
                $stmt = executeQuery("SELECT * FROM etudiants WHERE id_etu = ?", [$studentId]);
                $student = $stmt->fetch();
                
                $success = 'Profil mis à jour avec succès!';
            }
            
        } catch (Exception $e) {
            $error = 'Erreur lors de la mise à jour du profil.';
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}

// Get student statistics
try {
    // Count total grades
    $stmt = executeQuery("SELECT COUNT(*) as total FROM grades WHERE id_etu = ?", [$studentId]);
    $totalGrades = $stmt->fetch()['total'];
    
    // Calculate average grade
    $stmt = executeQuery("SELECT AVG(grade) as average FROM grades WHERE id_etu = ?", [$studentId]);
    $averageGrade = $stmt->fetch()['average'] ?? 0;
    
    // Count total attendance
    $stmt = executeQuery("SELECT COUNT(*) as total FROM attendance WHERE id_etu = ?", [$studentId]);
    $totalAttendance = $stmt->fetch()['total'];
    
    // Count attendance this month
    $stmt = executeQuery("
        SELECT COUNT(*) as total 
        FROM attendance 
        WHERE id_etu = ? 
        AND MONTH(date) = MONTH(CURRENT_DATE()) 
        AND YEAR(date) = YEAR(CURRENT_DATE())
    ", [$studentId]);
    $monthlyAttendance = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $totalGrades = 0;
    $averageGrade = 0;
    $totalAttendance = 0;
    $monthlyAttendance = 0;
    error_log("Get statistics error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - EduTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/professional-style.css" rel="stylesheet">
    <link href="../assets/css/animations.css" rel="stylesheet">
    <link href="../assets/css/responsive.css" rel="stylesheet">
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
                        <a class="nav-link" href="events.php">
                            <i class="fas fa-calendar me-1"></i>Événements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
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
                                    <i class="fas fa-user-circle me-2"></i>
                                    Mon Profil
                                </h3>
                                <p class="text-muted mb-0">Gérer vos informations personnelles et paramètres de compte</p>
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

        <div class="row">
            <!-- Profile Form -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Informations Personnelles
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">
                                        <i class="fas fa-user me-1"></i>Nom *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nom" 
                                           name="nom" 
                                           value="<?php echo htmlspecialchars($student['nom'] ?? ''); ?>" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="prenom" class="form-label">
                                        <i class="fas fa-user me-1"></i>Prénom *
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="prenom" 
                                           name="prenom" 
                                           value="<?php echo htmlspecialchars($student['prenom'] ?? ''); ?>" 
                                           required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-at me-1"></i>Nom d'utilisateur
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           value="<?php echo htmlspecialchars($student['username'] ?? ''); ?>" 
                                           readonly>
                                    <div class="form-text">Le nom d'utilisateur ne peut pas être modifié</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="niveau" class="form-label">
                                        <i class="fas fa-graduation-cap me-1"></i>Niveau *
                                    </label>
                                    <select class="form-control" id="niveau" name="niveau" required>
                                        <option value="">Sélectionner un niveau</option>
                                        <option value="Licence 1" <?php echo ($student['niveau'] ?? '') === 'Licence 1' ? 'selected' : ''; ?>>Licence 1</option>
                                        <option value="Licence 2" <?php echo ($student['niveau'] ?? '') === 'Licence 2' ? 'selected' : ''; ?>>Licence 2</option>
                                        <option value="Licence 3" <?php echo ($student['niveau'] ?? '') === 'Licence 3' ? 'selected' : ''; ?>>Licence 3</option>
                                        <option value="Master 1" <?php echo ($student['niveau'] ?? '') === 'Master 1' ? 'selected' : ''; ?>>Master 1</option>
                                        <option value="Master 2" <?php echo ($student['niveau'] ?? '') === 'Master 2' ? 'selected' : ''; ?>>Master 2</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="mb-3">
                                <i class="fas fa-lock me-2"></i>
                                Changer le mot de passe (optionnel)
                            </h6>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="current_password" class="form-label">
                                        Mot de passe actuel
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="current_password" 
                                           name="current_password">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="new_password" class="form-label">
                                        Nouveau mot de passe
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="new_password" 
                                           name="new_password">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="confirm_password" class="form-label">
                                        Confirmer le mot de passe
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password">
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save me-2"></i>
                                    Mettre à jour le profil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistics Sidebar -->
            <div class="col-lg-4">
                <!-- Profile Summary -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-id-card me-2"></i>
                            Résumé du Profil
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="profile-avatar bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                                 style="width: 80px; height: 80px; font-size: 2rem;">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                        <div class="text-center">
                            <h5 class="mb-1"><?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($student['niveau'] ?? 'Non défini'); ?></p>
                            <small class="text-muted">ID: <?php echo htmlspecialchars($student['id_etu']); ?></small>
                        </div>
                    </div>
                </div>

                <!-- Academic Statistics -->
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Statistiques Académiques
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="stat-item">
                                    <div class="h4 text-primary mb-1"><?php echo $totalGrades; ?></div>
                                    <small class="text-muted">Notes Total</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="stat-item">
                                    <div class="h4 text-success mb-1">
                                        <?php echo $totalGrades > 0 ? number_format($averageGrade, 1) : '0'; ?>/20
                                    </div>
                                    <small class="text-muted">Moyenne</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item">
                                    <div class="h4 text-info mb-1"><?php echo $totalAttendance; ?></div>
                                    <small class="text-muted">Présences Total</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-item">
                                    <div class="h4 text-warning mb-1"><?php echo $monthlyAttendance; ?></div>
                                    <small class="text-muted">Ce Mois</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength');
            
            if (password.length === 0) {
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            const colors = ['bg-danger', 'bg-warning', 'bg-info', 'bg-success', 'bg-success'];
            const texts = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
            
            if (strengthBar) {
                strengthBar.className = `progress-bar ${colors[strength - 1]}`;
                strengthBar.style.width = `${(strength / 5) * 100}%`;
                strengthBar.textContent = texts[strength - 1];
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;
            
            if ((newPassword || confirmPassword) && !currentPassword) {
                e.preventDefault();
                alert('Veuillez saisir votre mot de passe actuel pour le modifier.');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les nouveaux mots de passe ne correspondent pas.');
                return;
            }
        });
    </script>
</body>
</html>
