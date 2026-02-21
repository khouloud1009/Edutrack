<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isStudent()) {
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
            // Check student credentials
            $stmt = executeQuery(
                "SELECT id_etu, nom, prenom, username, password FROM etudiants WHERE username = ?",
                [$username]
            );
            
            $student = $stmt->fetch();
            
            if ($student && password_verify($password, $student['password'])) {
                // Login successful
                loginUser($student['id_etu'], $student['nom'] . ' ' . $student['prenom'], 'student');
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
            }
            
        } catch (Exception $e) {
            $error = 'Erreur de connexion. Veuillez réessayer.';
            error_log("Student login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Étudiant - EduTrack</title>
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
                                <i class="fas fa-user-graduate fa-4x gradient-text pulse"></i>
                            </div>
                            <h2 class="gradient-text mb-2">Espace Étudiant</h2>
                            <p class="text-muted">Connectez-vous à votre compte étudiant</p>
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

                        <div class="text-center mb-4">
                            <a href="../index.php" class="text-muted text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>
                                Retour à l'accueil
                            </a>
                        </div>

                        <!-- Features for Students -->
                        <div class="mb-4">
                            <h6 class="text-center mb-3 text-muted">Bienvenue sur EduTrack</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="glass-effect p-2 rounded">
                                        <i class="fas fa-chart-line text-success mb-1"></i>
                                        <p class="small text-muted mb-0">Visualisez vos performances</p>
                                        <small class="text-muted">Accédez à vos notes et statistiques en temps réel</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="glass-effect p-2 rounded">
                                        <i class="fas fa-shield-alt text-info mb-1"></i>
                                        <p class="small text-muted mb-0">Connexion sécurisée</p>
                                        <small class="text-muted">Authentification par mot de passe et reconnaissance faciale</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="glass-effect p-2 rounded">
                                        <i class="fas fa-cogs text-warning mb-1"></i>
                                        <p class="small text-muted mb-0">Gestion complète</p>
                                        <small class="text-muted">Pour les étudiants et administrateurs</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Test Credentials Info -->
                        <div class="pt-3 border-top">
                            <small class="text-muted d-block text-center">
                                <strong>Comptes test:</strong><br>
                                Utilisateur: y.idrissi | Mot de passe: password<br>
                                Utilisateur: a.benali | Mot de passe: password<br>
                                Utilisateur: m.tazi | Mot de passe: password
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
                this.style.transform = 'translateY(-1px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
                this.style.transform = 'translateY(0)';
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

        // Smooth entrance animation
        window.addEventListener('load', function() {
            const card = document.querySelector('.fade-in-up');
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.8s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>