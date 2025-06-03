<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireAdmin();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $description = trim($_POST['description'] ?? '');
                $date = $_POST['date'] ?? '';
                $id_course = $_POST['id_course'] ?? '';
                $id_admin = getCurrentUserId();
                
                if (empty($description) || empty($date)) {
                    $error = 'La description et la date sont requises.';
                } else {
                    try {
                        executeQuery(
                            "INSERT INTO events (description, date, id_course, id_admin) VALUES (?, ?, ?, ?)",
                            [$description, $date, $id_course ?: null, $id_admin]
                        );
                        $success = 'Événement ajouté avec succès.';
                    } catch (Exception $e) {
                        $error = 'Erreur lors de l\'ajout de l\'événement.';
                        error_log("Add event error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'update':
                $id = $_POST['id'] ?? '';
                $description = trim($_POST['description'] ?? '');
                $date = $_POST['date'] ?? '';
                $id_course = $_POST['id_course'] ?? '';
                
                if (empty($id) || empty($description) || empty($date)) {
                    $error = 'Tous les champs requis doivent être remplis.';
                } else {
                    try {
                        executeQuery(
                            "UPDATE events SET description = ?, date = ?, id_course = ? WHERE id_event = ? AND id_admin = ?",
                            [$description, $date, $id_course ?: null, $id, getCurrentUserId()]
                        );
                        $success = 'Événement modifié avec succès.';
                    } catch (Exception $e) {
                        $error = 'Erreur lors de la modification de l\'événement.';
                        error_log("Update event error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? '';
                if (empty($id)) {
                    $error = 'ID événement requis.';
                } else {
                    try {
                        executeQuery(
                            "DELETE FROM events WHERE id_event = ? AND id_admin = ?",
                            [$id, getCurrentUserId()]
                        );
                        $success = 'Événement supprimé avec succès.';
                    } catch (Exception $e) {
                        $error = 'Erreur lors de la suppression de l\'événement.';
                        error_log("Delete event error: " . $e->getMessage());
                    }
                }
                break;
        }
    }
}

// Get filter parameters
$selectedCourse = $_GET['course'] ?? '';

// Get admin's courses
try {
    $stmt = executeQuery(
        "SELECT * FROM cours WHERE id_admin = ? ORDER BY titre",
        [getCurrentUserId()]
    );
    $adminCourses = $stmt->fetchAll();
} catch (Exception $e) {
    $adminCourses = [];
}

// Get events
try {
    $query = "
        SELECT e.*, c.titre as course_title, a.nom as admin_name 
        FROM events e 
        LEFT JOIN cours c ON e.id_course = c.id_course 
        LEFT JOIN admins a ON e.id_admin = a.id_admin 
        WHERE e.id_admin = ?
    ";
    $params = [getCurrentUserId()];
    
    if ($selectedCourse) {
        $query .= " AND e.id_course = ?";
        $params[] = $selectedCourse;
    }
    
    $query .= " ORDER BY e.date DESC";
    
    $stmt = executeQuery($query, $params);
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Erreur lors du chargement des événements.';
    error_log("Load events error: " . $e->getMessage());
    $events = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Événements - EduTrack</title>
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
                    <h1 class="page-title">Gestion des Événements</h1>
                    <p class="page-subtitle">Planifiez et organisez les événements académiques</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEventModal">
                        <i class="fas fa-plus me-2"></i>Ajouter Événement
                    </button>
                    <button class="btn btn-success" onclick="exportEvents()">
                        <i class="fas fa-download me-2"></i>Exporter
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

                <!-- Filter Section -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <form method="GET" class="row align-items-end">
                            <div class="col-md-4">
                                <label for="course" class="form-label">Filtrer par cours</label>
                                <select class="form-control" id="course" name="course" onchange="this.form.submit()">
                                    <option value="">Tous les événements</option>
                                    <?php foreach ($adminCourses as $course): ?>
                                        <option value="<?php echo $course['id_course']; ?>" 
                                                <?php echo $selectedCourse == $course['id_course'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['titre'] . ' - ' . $course['niveau']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-filter me-1"></i>Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Events Cards -->
                <div class="row">
                    <?php if (empty($events)): ?>
                        <div class="col-12">
                            <div class="card shadow">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Aucun événement trouvé</h5>
                                    <p class="text-muted">Commencez par ajouter un nouvel événement.</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card shadow h-100 <?php echo strtotime($event['date']) < time() ? 'border-muted' : 'border-primary'; ?>">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            <?php echo date('d/m/Y', strtotime($event['date'])); ?>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="#" 
                                                       onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                                        <i class="fas fa-edit me-2"></i>Modifier
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger" href="#" 
                                                       onclick="deleteEvent(<?php echo $event['id_event']; ?>, '<?php echo htmlspecialchars(substr($event['description'], 0, 50)); ?>')">
                                                        <i class="fas fa-trash me-2"></i>Supprimer
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo htmlspecialchars($event['description']); ?></p>
                                        
                                        <?php if ($event['course_title']): ?>
                                            <div class="mb-2">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-book me-1"></i>
                                                    <?php echo htmlspecialchars($event['course_title']); ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="mb-2">
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-globe me-1"></i>
                                                    Événement général
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $eventDate = strtotime($event['date']);
                                        $today = strtotime('today');
                                        $diffDays = ($eventDate - $today) / (60 * 60 * 24);
                                        ?>
                                        
                                        <?php if ($diffDays < 0): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Passé (il y a <?php echo abs($diffDays); ?> jour<?php echo abs($diffDays) > 1 ? 's' : ''; ?>)
                                            </small>
                                        <?php elseif ($diffDays == 0): ?>
                                            <small class="text-danger fw-bold">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                Aujourd'hui
                                            </small>
                                        <?php elseif ($diffDays <= 7): ?>
                                            <small class="text-warning fw-bold">
                                                <i class="fas fa-clock me-1"></i>
                                                Dans <?php echo $diffDays; ?> jour<?php echo $diffDays > 1 ? 's' : ''; ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                Dans <?php echo $diffDays; ?> jours
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Ajouter un Événement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="id_course" class="form-label">Cours (optionnel)</label>
                            <select class="form-control" id="id_course" name="id_course">
                                <option value="">Événement général</option>
                                <?php foreach ($adminCourses as $course): ?>
                                    <option value="<?php echo $course['id_course']; ?>" 
                                            <?php echo $selectedCourse == $course['id_course'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['titre'] . ' - ' . $course['niveau']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Modifier l'Événement
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="edit_date" name="date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_id_course" class="form-label">Cours (optionnel)</label>
                            <select class="form-control" id="edit_id_course" name="id_course">
                                <option value="">Événement général</option>
                                <?php foreach ($adminCourses as $course): ?>
                                    <option value="<?php echo $course['id_course']; ?>">
                                        <?php echo htmlspecialchars($course['titre'] . ' - ' . $course['niveau']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteEventModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirmer la Suppression
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        
                        <p>Êtes-vous sûr de vouloir supprimer cet événement ?</p>
                        <p><strong id="delete_description"></strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today for new events
        document.getElementById('date').min = new Date().toISOString().split('T')[0];

        function editEvent(event) {
            document.getElementById('edit_id').value = event.id_event;
            document.getElementById('edit_description').value = event.description;
            document.getElementById('edit_date').value = event.date;
            document.getElementById('edit_id_course').value = event.id_course || '';
            
            new bootstrap.Modal(document.getElementById('editEventModal')).show();
        }

        function deleteEvent(id, description) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_description').textContent = description;
            
            new bootstrap.Modal(document.getElementById('deleteEventModal')).show();
        }
    </script>
</body>
</html>
