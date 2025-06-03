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
                $titre = trim($_POST['titre'] ?? '');
                $niveau = trim($_POST['niveau'] ?? '');
                $id_admin = getCurrentUserId();
                
                if (empty($titre) || empty($niveau)) {
                    $error = 'Tous les champs sont requis.';
                } else {
                    try {
                        executeQuery(
                            "INSERT INTO cours (titre, niveau, id_admin) VALUES (?, ?, ?)",
                            [$titre, $niveau, $id_admin]
                        );
                        $success = 'Cours ajouté avec succès.';
                    } catch (Exception $e) {
                        $error = 'Erreur lors de l\'ajout du cours.';
                        error_log("Add course error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'update':
                $id = $_POST['id'] ?? '';
                $titre = trim($_POST['titre'] ?? '');
                $niveau = trim($_POST['niveau'] ?? '');
                
                if (empty($id) || empty($titre) || empty($niveau)) {
                    $error = 'Tous les champs sont requis.';
                } else {
                    try {
                        executeQuery(
                            "UPDATE cours SET titre = ?, niveau = ? WHERE id_course = ?",
                            [$titre, $niveau, $id]
                        );
                        $success = 'Cours modifié avec succès.';
                    } catch (Exception $e) {
                        $error = 'Erreur lors de la modification du cours.';
                        error_log("Update course error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? '';
                if (empty($id)) {
                    $error = 'ID cours requis.';
                } else {
                    try {
                        beginTransaction();
                        
                        // Delete related records first
                        executeQuery("DELETE FROM attendance WHERE id_course = ?", [$id]);
                        executeQuery("DELETE FROM grades WHERE id_course = ?", [$id]);
                        executeQuery("DELETE FROM events WHERE id_course = ?", [$id]);
                        
                        // Delete course
                        executeQuery("DELETE FROM cours WHERE id_course = ?", [$id]);
                        
                        commitTransaction();
                        $success = 'Cours supprimé avec succès.';
                    } catch (Exception $e) {
                        rollbackTransaction();
                        $error = 'Erreur lors de la suppression du cours.';
                        error_log("Delete course error: " . $e->getMessage());
                    }
                }
                break;
        }
    }
}

// Get all courses with admin names
try {
    $stmt = executeQuery("
        SELECT c.*, a.nom as admin_name 
        FROM cours c 
        LEFT JOIN admins a ON c.id_admin = a.id_admin 
        ORDER BY c.titre
    ");
    $courses = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Erreur lors du chargement des cours.';
    error_log("Load courses error: " . $e->getMessage());
    $courses = [];
}

// Get admin courses (for current admin)
try {
    $stmt = executeQuery(
        "SELECT * FROM cours WHERE id_admin = ? ORDER BY titre",
        [getCurrentUserId()]
    );
    $adminCourses = $stmt->fetchAll();
} catch (Exception $e) {
    $adminCourses = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Cours - EduTrack</title>
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
                    <h1 class="page-title">Gestion des Cours</h1>
                    <p class="page-subtitle">Organisez et gérez votre catalogue de cours</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                        <i class="fas fa-plus me-2"></i>Ajouter Cours
                    </button>
                    <button class="btn btn-success" onclick="exportCourses()">
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

                <!-- Courses Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card border-0 shadow-lg h-100 stats-card" style="background: var(--success-gradient);">
                            <div class="card-body p-4 text-white position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="small text-white-50 text-uppercase fw-bold mb-2">
                                            Total Cours
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php echo count($courses); ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-book me-1"></i>
                                            <span>Disponibles</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-book fa-2x text-white"></i>
                                        </div>
                                    </div>
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
                                            Mes Cours
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php echo count($adminCourses); ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-user-tie me-1"></i>
                                            <span>Que je gère</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-chalkboard-teacher fa-2x text-white"></i>
                                        </div>
                                    </div>
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
                                            Niveaux
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php 
                                            $niveaux = array_unique(array_column($courses, 'niveau'));
                                            echo count($niveaux);
                                            ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-layer-group me-1"></i>
                                            <span>Différents</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-sitemap fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card border-0 shadow-lg h-100 stats-card" style="background: var(--primary-gradient);">
                            <div class="card-body p-4 text-white position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="small text-white-50 text-uppercase fw-bold mb-2">
                                            Actifs
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php echo count($courses); ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-play-circle me-1"></i>
                                            <span>En cours</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-play fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses Table -->
                <div class="card border-0 shadow-lg">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Liste des Cours
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Titre</th>
                                        <th>Niveau</th>
                                        <th>Responsable</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($courses)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                Aucun cours trouvé
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['id_course']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($course['titre']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo htmlspecialchars($course['niveau']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($course['admin_name'] ?? 'Non défini'); ?></td>
                                                <td>
                                                    <?php if ($course['id_admin'] == getCurrentUserId()): ?>
                                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                                                onclick="editCourse(<?php echo htmlspecialchars(json_encode($course)); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteCourse(<?php echo $course['id_course']; ?>, '<?php echo htmlspecialchars($course['titre']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">Non autorisé</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- My Courses Section -->
                <?php if (!empty($adminCourses)): ?>
                    <div class="card shadow mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-tie me-2"></i>Mes Cours
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($adminCourses as $course): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card border-primary">
                                            <div class="card-body">
                                                <h6 class="card-title text-primary">
                                                    <?php echo htmlspecialchars($course['titre']); ?>
                                                </h6>
                                                <p class="card-text">
                                                    <span class="badge bg-info">
                                                        <?php echo htmlspecialchars($course['niveau']); ?>
                                                    </span>
                                                </p>
                                                <div class="btn-group w-100" role="group">
                                                    <a href="grades.php?course=<?php echo $course['id_course']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-chart-line"></i> Notes
                                                    </a>
                                                    <a href="events.php?course=<?php echo $course['id_course']; ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-calendar"></i> Événements
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Ajouter un Cours
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="titre" class="form-label">Titre du Cours</label>
                            <input type="text" class="form-control" id="titre" name="titre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="niveau" class="form-label">Niveau</label>
                            <select class="form-control" id="niveau" name="niveau" required>
                                <option value="">Sélectionner un niveau</option>
                                <option value="Licence 1">Licence 1</option>
                                <option value="Licence 2">Licence 2</option>
                                <option value="Licence 3">Licence 3</option>
                                <option value="Master 1">Master 1</option>
                                <option value="Master 2">Master 2</option>
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

    <!-- Edit Course Modal -->
    <div class="modal fade" id="editCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Modifier le Cours
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_titre" class="form-label">Titre du Cours</label>
                            <input type="text" class="form-control" id="edit_titre" name="titre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_niveau" class="form-label">Niveau</label>
                            <select class="form-control" id="edit_niveau" name="niveau" required>
                                <option value="">Sélectionner un niveau</option>
                                <option value="Licence 1">Licence 1</option>
                                <option value="Licence 2">Licence 2</option>
                                <option value="Licence 3">Licence 3</option>
                                <option value="Master 1">Master 1</option>
                                <option value="Master 2">Master 2</option>
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
    <div class="modal fade" id="deleteCourseModal" tabindex="-1">
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
                        
                        <p>Êtes-vous sûr de vouloir supprimer le cours <strong id="delete_name"></strong> ?</p>
                        <p class="text-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Cette action supprimera également toutes les données associées (notes, présences, événements).
                        </p>
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
        function editCourse(course) {
            document.getElementById('edit_id').value = course.id_course;
            document.getElementById('edit_titre').value = course.titre;
            document.getElementById('edit_niveau').value = course.niveau;
            
            new bootstrap.Modal(document.getElementById('editCourseModal')).show();
        }

        function deleteCourse(id, title) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = title;
            
            new bootstrap.Modal(document.getElementById('deleteCourseModal')).show();
        }
    </script>
</body>
</html>
