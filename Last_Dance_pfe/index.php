<?php
session_start();

// Redirect based on existing session
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } elseif ($_SESSION['user_type'] === 'student') {
        header('Location: student/dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTrack - Système de Gestion Éducative</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center">
        <div class="row w-100 justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-6">
                <div class="card shadow-purple-lg border-0 fade-in-up">
                    <div class="card-body p-5">
                        <div class="text-center mb-5">
                            <h1 class="display-4 gradient-text mb-3">
                                <i class="fas fa-graduation-cap me-3"></i>
                                EduTrack
                            </h1>
                            <p class="lead text-muted">Système de Gestion Éducative avec Reconnaissance Faciale</p>
                        </div>

                        <div class="row g-4">
                            <!-- Admin Login Card -->
                            <div class="col-md-6">
                                <div class="card border-0 h-100 login-card shadow-purple">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-user-shield fa-3x text-primary float-animation"></i>
                                        </div>
                                        <h4 class="card-title text-primary mb-2">Administration</h4>
                                        <p class="card-text text-muted mb-4">
                                            Gérer les étudiants, cours, notes et présences
                                        </p>
                                        <a href="admin/login.php" class="btn btn-primary btn-lg w-100">
                                            <i class="fas fa-sign-in-alt me-2"></i>
                                            Connexion Admin
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Student Login Card -->
                            <div class="col-md-6">
                                <div class="card border-0 h-100 login-card shadow-purple">
                                    <div class="card-body text-center p-4">
                                        <div class="mb-3">
                                            <i class="fas fa-user-graduate fa-3x text-success float-animation" style="animation-delay: 0.5s;"></i>
                                        </div>
                                        <h4 class="card-title text-success mb-2">Étudiants</h4>
                                        <p class="card-text text-muted mb-4">
                                            Consulter vos notes, présences et événements
                                        </p>
                                        <a href="student/login.php" class="btn btn-success btn-lg w-100">
                                            <i class="fas fa-sign-in-alt me-2"></i>
                                            Connexion Étudiant
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Features Section -->
                        <div class="mt-5">
                            <h5 class="text-center mb-4 text-muted">Fonctionnalités</h5>
                            <div class="row text-center">
                                <div class="col-md-4 mb-3">
                                    <div class="glass-effect p-3 rounded">
                                        <i class="fas fa-camera fa-2x text-info mb-2 float-animation"></i>
                                        <p class="small text-muted mb-0">Reconnaissance Faciale</p>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="glass-effect p-3 rounded">
                                        <i class="fas fa-chart-line fa-2x text-warning mb-2 float-animation" style="animation-delay: 1s;"></i>
                                        <p class="small text-muted mb-0">Gestion des Notes</p>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="glass-effect p-3 rounded">
                                        <i class="fas fa-calendar-check fa-2x text-danger mb-2 float-animation" style="animation-delay: 1.5s;"></i>
                                        <p class="small text-muted mb-0">Suivi des Présences</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Welcome Message -->
                        <div class="mt-4 text-center">
                            <p class="text-muted mb-0">
                                <i class="fas fa-shield-alt me-2"></i>
                                Plateforme sécurisée et moderne pour l'éducation
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enhanced animations script -->
    <script>
        // Add intersection observer for animation triggers
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, observerOptions);

        // Observe all animated elements
        document.querySelectorAll('.float-animation').forEach(el => {
            observer.observe(el);
        });

        // Add smooth hover effects
        document.querySelectorAll('.login-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>
