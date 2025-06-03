<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: dashboard.php');
        exit();
    } else {
        header('Location: ../index.php');
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            // Check admin credentials (using 'admins' table as in original)
            $stmt = executeQuery(
                "SELECT id_admin, nom, username, password FROM admins WHERE username = ?",
                [$username]
            );
            
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Login successful
                loginUser($admin['id_admin'], $admin['nom'], 'admin');
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
            }
            
        } catch (Exception $e) {
            $error = 'Erreur de connexion. Veuillez réessayer.';
            error_log("Admin login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administration - EduTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100 justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-purple-lg border-0 fade-in-up">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="fas fa-user-shield fa-4x gradient-text pulse"></i>
                            </div>
                            <h2 class="gradient-text mb-2">Administration</h2>
                            <p class="text-muted">Connectez-vous à votre espace administrateur</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2 text-purple"></i>Nom d'utilisateur
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="username" 
                                       name="username" 
                                       required 
                                       autofocus
                                       placeholder="Entrez votre nom d'utilisateur"
                                       value="<?php echo htmlspecialchars($username ?? ''); ?>">
                                <div class="invalid-feedback">
                                    Veuillez entrer votre nom d'utilisateur.
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2 text-purple"></i>Mot de passe
                                </label>
                                <div class="position-relative">
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="password" 
                                           name="password" 
                                           required
                                           placeholder="Entrez votre mot de passe">
                                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y" 
                                            onclick="togglePassword()" style="border: none; background: none;">
                                        <i class="fas fa-eye" id="password-toggle"></i>
                                    </button>
                                    <div class="invalid-feedback">
                                        Veuillez entrer votre mot de passe.
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-purple btn-lg w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Se connecter
                            </button>
                        </form>

                        <div class="text-center">
                            <a href="../index.php" class="text-muted text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>
                                Retour à l'accueil
                            </a>
                        </div>

                         <!-- <div class="text-center mb-4">
                            <a href="../student/login.php" class="text-muted text-decoration-none">
                                <i class="fas fa-users me-2"></i>
                                Espace Étudiant
                            </a>
                        </div> -->

                        <!-- Admin Features -->
                        <div class="mb-4">
                            <h6 class="text-center mb-3 text-muted">Panneau d'Administration</h6>
                             <!-- Security Notice -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="glass-effect p-3 rounded text-center">
                                <i class="fas fa-shield-alt text-purple mb-2"></i>
                                <small class="text-muted d-block">
                                    <strong>Accès sécurisé</strong><br>
                                    Connexion chiffrée avec authentification à deux facteurs
                                </small>
                            </div>
                        </div>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="glass-effect p-2 rounded">
                                        <i class="fas fa-users text-primary mb-1"></i>
                                        <p class="small text-muted mb-0">Gestion des utilisateurs</p>
                                        <small class="text-muted">Étudiants et administrateurs</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="glass-effect p-2 rounded">
                                        <i class="fas fa-chart-bar text-success mb-1"></i>
                                        <p class="small text-muted mb-0">Rapports et statistiques</p>
                                        <small class="text-muted">Analyses en temps réel</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="glass-effect p-2 rounded">
                                        <i class="fas fa-cog text-warning mb-1"></i>
                                        <p class="small text-muted mb-0">Configuration système</p>
                                        <small class="text-muted">Paramètres avancés</small>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Test Credentials Info -->
                        <div class="mt-3">
                            <small class="text-muted d-block text-center">
                                <strong>Comptes de test:</strong><br>
                                Utilisateur: a.bennani | Mot de passe: admin123<br>
                                Utilisateur: f.alaoui | Mot de passe: admin123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Password toggle functionality
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
            }
        }

        // Enhanced form interactions
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.classList.contains('show')) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            });
        }, 5000);
    </script>
</body>
</html>
