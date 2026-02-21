<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

// function clear file
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

$error = '';
$success = '';

// Handle session management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'start_session': //hna khasni nbdl
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
                // Clear the attendance file before starting new session
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
                            
                            // Get the newly created session ID
                            $session_id = $pdo->lastInsertId();
                            
                            // Start the face recognition process
                            exec("python3 face_attendance.py > /dev/null 2>&1 &");
                            
                            $success = 'Session de présence démarrée avec succès.';
                        }
                    } catch (Exception $e) {
                        $error = 'Erreur lors du démarrage de la session.';
                        error_log("Start session error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'stop_session':
                try {
                    executeQuery(
                        "UPDATE attendance_sessions SET status = 'stopped', actual_end_time = NOW() WHERE status = 'active' AND id_admin = ?",
                        [getCurrentUserId()]
                    );
                    
                    // Stop the face recognition process
                    exec("pkill -f face_attendance.py");
                    
                    $success = 'Session de présence arrêtée.';
                } catch (Exception $e) {
                    $error = 'Erreur lors de l\'arrêt de la session.';
                    error_log("Stop session error: " . $e->getMessage());
                }
                break;
        }
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
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container-fluid py-4">
        <!-- Page Header -->
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
                    <!-- Active Session Card -->
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
                                        <button type="submit" id="stop" class="btn btn-danger btn-lg" onclick="return confirm('Êtes-vous sûr de vouloir arrêter cette session ?')">
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
            <div class="row">
                <div class="col-md-12">
                    <h5><i class="fas fa-camera me-2"></i>Reconnaissance Faciale</h5>
                    <div class="camera-container">
                        <video id="video" width="640" height="480" autoplay></video>
                        <canvas id="canvas" width="640" height="480" style="display:none;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ESSAY DIV -->
      <video id="video" width="640" height="480" autoplay></video>
<canvas id="canvas" style="display:none;"></canvas>

<script>
const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const context = canvas.getContext('2d');

// Demander l'accès à la webcam
navigator.mediaDevices.getUserMedia({ video: true })
  .then(stream => {
    video.srcObject = stream;
  })
  .catch(err => {
    console.error("Erreur caméra : ", err);
  });

// Capturer une image toutes les 3 secondes
setInterval(() => {
  context.drawImage(video, 0, 0, canvas.width = 640, canvas.height = 480);
  let imageData = canvas.toDataURL('image/jpeg');

  // Envoi au serveur
  fetch('upload_image.php', {
    method: 'POST',
    body: JSON.stringify({ image: imageData }),
    headers: {
      'Content-Type': 'application/json'
    }
  });
}, 3000);
</script>

<script> //had script bach nzid lance python code face_attendance.py
    document.getElementById('start').addEventListener('click', function() {
        fetch('/run-python', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            alert(data.result);
        })
        .catch((error) => {
            console.error('Error:', error);
        });
    }); 
</script>

                    <script>
                       // Update timer every second
        function updateTimer() {
            const endTime = new Date("<?php echo $activeSession['end_time']; ?>").getTime();
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance > 0) {
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000;

                document.getElementById("time-remaining").innerHTML = 
                    "Temps restant: " + hours + "h " + minutes + "m " + seconds + "s";
            } else {
                document.getElementById("time-remaining").innerHTML = "Session expirée";
                document.getElementById("session-timer").className = "alert alert-warning";
                
                // Automatically stop the session when expired
                setTimeout(() => {
                    fetch('stop_session.php', { method: 'POST' })
                        .then(response => location.reload());
                }, 5000);
            }
        }

        updateTimer();
        setInterval(updateTimer, 1000);

        // Camera access and face detection
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const context = canvas.getContext('2d');

        // Access the camera
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(function(stream) {
                    video.srcObject = stream;
                    video.play();
                    
                    // Periodically send frames to server for processing
                    setInterval(captureFrame, 5000); // Every 5 seconds
                })
                .catch(function(error) {
                    console.error("Camera access error:", error);
                });
        }

        function captureFrame() {
            // Draw video frame to canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert canvas to blob and send to server
            canvas.toBlob(function(blob) {
                const formData = new FormData();
                formData.append('frame', blob);
                formData.append('session_id', '<?php echo $activeSession['id_session']; ?>');
                
                fetch('process_frame.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Face processed:", data.name);
                    }
                })
                .catch(error => console.error('Error:', error));
            }, 'image/jpeg', 0.8);
        }
                    document.getElementById("start").onclick = () => {
                    fetch("start_session.php");
                    };

                    document.getElementById("stop").onclick = () => {
                    fetch("stop_session.php")
                    .then(() => window.location.href = "face_attendance_view.php");
                    };
                    </script>
                <?php else: ?>
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
                <?php endif; ?>

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
                                    <li>La reconnaissance faciale s'active automatiquement</li>
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
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Sessions -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Sessions Récentes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentSessions)): ?>
                            <p class="text-muted text-center">Aucune session trouvée</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Cours</th>
                                            <th>Début</th>
                                            <th>Fin Prévue</th>
                                            <th>Durée</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentSessions as $session): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($session['course_title']); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($session['start_time'])); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($session['end_time'])); ?></td>
                                                <td><?php echo $session['duration_minutes']; ?> min</td>
                                                <td>
                                                    <?php if ($session['status'] === 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php elseif ($session['status'] === 'completed'): ?>
                                                        <span class="badge bg-primary">Complétée</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Arrêtée</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="face_attendance_view.php?session=<?php echo $session['id_session']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i> Voir
                                                    </a>
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
</body>
</html>