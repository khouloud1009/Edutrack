<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

function clearAttendanceCSV() {
    $file = '../admin/Attendance.csv'; // Adjust path if needed
    
    if (!file_exists($file)) {
        return false;
    }

    // 1. Read the header (first line)
    $header = '';
    $handle = fopen($file, 'r');
    if ($handle !== false) {
        $header = fgets($handle); // Gets first line
        fclose($handle);
    }

    // 2. Reopen file in write mode (clears content)
    $handle = fopen($file, 'w');
    if ($handle !== false) {
        // 3. Write back ONLY the header
        if (!empty($header)) {
            fwrite($handle, $header);
        }
        fclose($handle);
        return true;
    }
    
    return false;
}

$success = '';
$error = '';
$session_status = 'none'; // Default status when no session exists

// Check current session status first
try {
    $stmt = executeQuery("SELECT * FROM attendance_sessions WHERE status = 'active' AND id_admin = ?", [getCurrentUserId()]);
    if ($stmt->rowCount() > 0) {
        $session_status = 'active';
        $current_session = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Check if there was a recently stopped session
        $stmt = executeQuery("SELECT * FROM attendance_sessions WHERE status = 'stopped' AND id_admin = ? ORDER BY actual_end_time DESC LIMIT 1", [getCurrentUserId()]);
        if ($stmt->rowCount() > 0) {
            $session_status = 'stopped';
        } else {
            $session_status = 'none'; // No session has been created yet
        }
    }
} catch (Exception $e) {
    error_log("Session status check error: " . $e->getMessage());
    $session_status = 'error';
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'start_session':
                $course_id = $_POST['course_id'] ?? '';
                $duration = $_POST['duration'] ?? 120; // Default 2 hours
                
                if (empty($course_id)) {
                    $error = 'Veuillez sélectionner un cours.';
                } else {
                    try {
                        // Check if there's already an active session
                        $stmt = executeQuery("SELECT * FROM attendance_sessions WHERE status = 'active' AND id_admin = ?", [getCurrentUserId()]);
                        if ($stmt->rowCount() > 0) {
                            $error = 'Une session est déjà active. Arrêtez-la avant d\'en démarrer une nouvelle.';
                        } else {
                            // Clear attendance file while preserving header
                            if (!clearAttendanceCSV()) {
                                $error = 'Impossible de vider le fichier de présence.';
                                break;
                            }
                            
                            // Start new session
                            $end_time = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));
                            executeQuery(
                                "INSERT INTO attendance_sessions (id_admin, id_course, start_time, end_time, duration_minutes, status) VALUES (?, ?, NOW(), ?, ?, 'active')",
                                [getCurrentUserId(), $course_id, $end_time, $duration]
                            );
                            
                            // Start the face recognition process
                            exec("python3 face_attendance_UI.py > /dev/null 2>&1 &");
                            
                            $success = 'Session de présence démarrée avec succès.';
                            $session_status = 'active'; // Update status after successful start
                        }
                    } catch (Exception $e) {
                        $error = 'Erreur lors du démarrage de la session.';
                        error_log("Start session error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'stop_session':
                try {
                    $stmt = executeQuery(
                        "UPDATE attendance_sessions SET status = 'stopped', actual_end_time = NOW() WHERE status = 'active' AND id_admin = ?",
                        [getCurrentUserId()]
                    );
                    
                    if ($stmt->rowCount() > 0) {
                        // Stop the face recognition process
                        exec("pkill -f face_attendance_UI.py");
                        $success = 'Session de présence arrêtée.';
                        $session_status = 'stopped'; // Update status after successful stop
                    } else {
                        $error = 'Aucune session active à arrêter.';
                    }
                } catch (Exception $e) {
                    $error = 'Erreur lors de l\'arrêt de la session.';
                    error_log("Stop session error: " . $e->getMessage());
                }
                break;
        }
    }
}

// Display appropriate message based on session status
if (empty($success) && empty($error)) {
    switch ($session_status) {
        case 'active':
            $info = 'Session de présence en cours.';
            break;
        case 'stopped':
            $info = 'Dernière session arrêtée.';
            break;
        case 'none':
            $info = 'Aucune session de présence.';
            break;
        case 'error':
            $error = 'Erreur lors de la vérification du statut de la session.';
            break;
    }
}

// Get admin's courses
try {
    $stmt = executeQuery(
        "SELECT * FROM cours WHERE id_admin = ? ORDER BY titre",
        [getCurrentUserId()]
    );
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    $courses = [];
}

// Get current active session
try {
    $stmt = executeQuery(
        "SELECT s.*, c.titre as course_title FROM attendance_sessions s 
         LEFT JOIN cours c ON s.id_course = c.id_course 
         WHERE s.status = 'active' AND s.id_admin = ?",
        [getCurrentUserId()]
    );
    $activeSession = $stmt->fetch();
} catch (Exception $e) {
    $activeSession = null;
}

// Get recent sessions
try {
    $stmt = executeQuery(
        "SELECT s.*, c.titre as course_title FROM attendance_sessions s 
         LEFT JOIN cours c ON s.id_course = c.id_course 
         WHERE s.id_admin = ? ORDER BY s.start_time DESC LIMIT 10",
        [getCurrentUserId()]
    );
    $recentSessions = $stmt->fetchAll();
} catch (Exception $e) {
    $recentSessions = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session de Présence - EduTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/admin-professional.css" rel="stylesheet">
    <script>
        function startVideo() {
            console.log('Démarrage du flux vidéo...');
          
            return fetch('http://localhost:5000/start_video', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Réponse start video:', data);
                if (data.status === 'started' || data.status === 'already_running') {
                    showVideoFeed();
                    showAlert('Flux vidéo démarré avec succès!', 'success');
                    return true;
                } else {
                    showAlert('Échec du démarrage du flux vidéo: ' + data.message, 'error');
                    return false;
                }
            })
            .catch(error => {
                console.error('Erreur lors du démarrage de la vidéo:', error);
                showAlert('Erreur de connexion au service de reconnaissance faciale. Assurez-vous que le script Python est en cours d\'exécution.', 'error');
                return false;
            });
        }

        function stopVideo() {
            console.log('Arrêt du flux vidéo...');
            
            return fetch('http://localhost:5000/stop_video', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Réponse stop video:', data);
                hideVideoFeed();
                showAlert('Flux vidéo arrêté avec succès!', 'success');
                return true;
            })
            .catch(error => {
                console.error('Erreur lors de l\'arrêt de la vidéo:', error);
                hideVideoFeed();
                showAlert('Erreur de connexion au service de reconnaissance faciale.', 'error');
                return false;
            });
        }

        function showVideoFeed() {
            const videoFeed = document.getElementById('videoFeed');
            videoFeed.src = 'http://localhost:5000/video_feed';
            videoFeed.style.display = 'block';
        }

        function hideVideoFeed() {
            const videoFeed = document.getElementById('videoFeed');
            videoFeed.src = '';
            videoFeed.style.display = 'none';
        }

        function checkVideoStatus() {
            return fetch('http://localhost:5000/status')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.video_running) {
                    showVideoFeed();
                    return true;
                } else {
                    hideVideoFeed();
                    return false;
                }
            })
            .catch(error => {
                console.log('Service de reconnaissance faciale non disponible:', error);
                hideVideoFeed();
                return false;
            });
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container-fluid').prepend(alertDiv);
            setTimeout(() => alertDiv.remove(), 5000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (<?php echo json_encode($session_status === 'active'); ?>) {
                startVideo();
            }
        });
    </script>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid py-4">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Sessions de Présence</h1>
                    <p class="page-subtitle">Gérez la reconnaissance faciale pour l'enregistrement automatique des présences</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="face_attendance_view.php" class="btn btn-info">
                        <i class="fas fa-eye me-2"></i>Voir Présences
                    </a>
                    <button class="btn btn-success" onclick="refreshSessions()">
                        <i class="fas fa-sync-alt me-2"></i>Actualiser
                    </button>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($activeSession): ?>
            <div class="card border-success shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-play-circle me-2"></i>
                        Session Active
                        <span class="badge bg-light text-success ms-2">EN COURS</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6><strong>Cours:</strong> <?php echo htmlspecialchars($activeSession['course_title']); ?></h6>
                            <p class="mb-2">
                                <strong>Démarré:</strong> <?php echo date('d/m/Y à H:i', strtotime($activeSession['start_time'])); ?><br>
                                <strong>Fin prévue:</strong> <?php echo date('d/m/Y à H:i', strtotime($activeSession['end_time'])); ?><br>
                                <strong>Durée:</strong> <?php echo $activeSession['duration_minutes']; ?> minutes
                            </p>
                            <div id="session-timer" class="alert alert-info">
                                <i class="fas fa-clock me-2"></i>
                                <span id="time-remaining"></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="stop_session">
                                <button type="submit" id="stop" class="btn btn-danger btn-lg">
                                    <i class="fas fa-stop me-2"></i>Arrêter Session
                                </button>
                            </form>
                            <a href="face_attendance_view.php" class="btn btn-outline-primary btn-lg ms-2">
                                <i class="fas fa-eye me-2"></i>Voir Présences
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Session en cours:</strong> La reconnaissance faciale est active via le processus Python en arrière-plan.
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div>
            <img id="videoFeed" src="" style="width: 50%; height: auto; display: none;" alt="Video Feed">
        </div>

        <!-- Start New Session Card -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Démarrer une Nouvelle Session
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="start_session">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Sélectionner un cours</label>
                                <select class="form-control" id="course_id" name="course_id" required>
                                    <option value="">Choisir un cours...</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id_course']; ?>">
                                            <?php echo htmlspecialchars($course['titre'] . ' - ' . $course['niveau']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="duration" class="form-label">Durée (minutes)</label>
                                <select class="form-control" id="duration" name="duration">
                                    <option value="60">1 heure</option>
                                    <option value="90">1h 30min</option>
                                    <option value="120" selected>2 heures</option>
                                    <option value="180">3 heures</option>
                                    <option value="240">4 heures</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="mb-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" id="start" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-play me-2"></i>Démarrer
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Instructions Card -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Instructions d'utilisation
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Fonctionnement:</h6>
                        <ul>
                            <li>Démarrez une session pour un cours spécifique</li>
                            <li>La reconnaissance faciale s'active automatiquement via Python</li>
                            <li>Les étudiants reconnus sont enregistrés automatiquement</li>
                            <li>Les visages non reconnus affichent une alerte</li>
                            <li>La session s'arrête automatiquement après la durée définie</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Prérequis:</h6>
                        <ul>
                            <li>Caméra fonctionnelle connectée</li>
                            <li>Photos des étudiants dans le dossier "known_faces"</li>
                            <li>Noms des fichiers = nom de l'étudiant</li>
                            <li>Python et les bibliothèques installées</li>
                        </ul>
                   