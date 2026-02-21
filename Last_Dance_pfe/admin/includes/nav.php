<?php
// Get current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <div class="brand-icon me-3">
                <i class="fas fa-graduation-cap fa-2x"></i>
            </div>
            <div>
                <div class="brand-text">EduTrack</div>
                <small class="brand-subtitle">Administration</small>
            </div>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        <span>Tableau de Bord</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'students.php' ? 'active' : ''; ?>" href="students.php">
                        <i class="fas fa-user-graduate me-2"></i>
                        <span>Étudiants</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'courses.php' ? 'active' : ''; ?>" href="courses.php">
                        <i class="fas fa-book me-2"></i>
                        <span>Cours</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'grades.php' ? 'active' : ''; ?>" href="grades.php">
                        <i class="fas fa-chart-line me-2"></i>
                        <span>Notes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'events.php' ? 'active' : ''; ?>" href="events.php">
                        <i class="fas fa-calendar me-2"></i>
                        <span>Événements</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'attendance_session.php' ? 'active' : ''; ?>" href="attendance_session.php">
                        <i class="fas fa-camera me-2"></i>
                        <span>Présences</span>
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar me-2">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info d-none d-lg-block">
                            <div class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                            <small class="user-role">Administrateur</small>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#" onclick="showProfile()">
                                <i class="fas fa-user me-2"></i>Mon Profil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="showSettings()">
                                <i class="fas fa-cog me-2"></i>Paramètres
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: var(--primary-gradient) !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    padding: 1rem 0;
}

.brand-text {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.brand-subtitle {
    font-size: 0.75rem;
    opacity: 0.8;
    line-height: 1;
}

.user-avatar {
    width: 35px;
    height: 35px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid rgba(255,255,255,0.3);
}

.user-name {
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1;
}

.user-role {
    font-size: 0.7rem;
    opacity: 0.8;
    line-height: 1;
}

.nav-link {
    border-radius: 10px;
    margin: 0 0.25rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-link.active {
    background: rgba(255,255,255,0.15) !important;
    backdrop-filter: blur(10px);
    font-weight: 600;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-radius: 15px;
    padding: 0.5rem;
}

.dropdown-item {
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: #f8f9fa;
    transform: translateX(5px);
}
</style>