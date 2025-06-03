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
                $grade = $_POST['grade'] ?? '';
                $matiere = trim($_POST['matiere'] ?? '');
                $id_etu = $_POST['id_etu'] ?? '';
                $id_course = $_POST['id_course'] ?? '';
                $id_admin = getCurrentUserId();
                
                if (empty($grade) || empty($matiere) || empty($id_etu) || empty($id_course)) {
                    $error = 'Tous les champs sont requis.';
                } elseif (!is_numeric($grade) || $grade < 0 || $grade > 20) {
                    $error = 'La note doit être un nombre entre 0 et 20.';
                } else {
                    try {
                        executeQuery(
                            "INSERT INTO grades (grade, matiere, id_etu, id_course, id_admin) VALUES (?, ?, ?, ?, ?)",
                            [$grade, $matiere, $id_etu, $id_course, $id_admin]
                        );
                        $success = 'Note ajoutée avec succès.';
                    } catch (Exception $e) {
                        $error = 'Erreur lors de l\'ajout de la note.';
                        error_log("Add grade error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'update':
                $id = $_POST['id'] ?? '';
                $grade = $_POST['grade'] ?? '';
                $matiere = trim($_POST['matiere'] ?? '');
                
                if (empty($id) || empty($grade) || empty($matiere)) {
                    $error = 'Tous les champs sont requis.';
                } elseif (!is_numeric($grade) || $grade < 0 || $grade > 20) {
                    $error = 'La note doit être un nombre entre 0 et 20.';
                } else {
                    try {
                        executeQuery(
                            "UPDATE grades SET grade = ?, matiere = ? WHERE id_grades = ? AND id_admin = ?",
                            [$grade, $matiere, $id, getCurrentUserId()]
                        );
                        $success = 'Note modifiée avec succès.';
                    } catch (Exception $e) {
                        $error = 'Erreur lors de la modification de la note.';
                        error_log("Update grade error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? '';
                if (empty($id)) {
                    $error = 'ID note requis.';
                } else {
                    try {
                        executeQuery(
                            "DELETE FROM grades WHERE id_grades = ? AND id_admin = ?",
                            [$id, getCurrentUserId()]
                        );
                        $success = 'Note supprimée avec succès.';
                    } catch (Exception $e) {
                        $error = 'Erreur lors de la suppression de la note.';
                        error_log("Delete grade error: " . $e->getMessage());
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

// Get students for the selected course or all students
try {
    if ($selectedCourse) {
        // Get students based on course level
        $stmt = executeQuery("SELECT niveau FROM cours WHERE id_course = ? AND id_admin = ?", [$selectedCourse, getCurrentUserId()]);
        $course = $stmt->fetch();
        if ($course) {
            $stmt = executeQuery("SELECT * FROM etudiants WHERE niveau = ? ORDER BY nom, prenom", [$course['niveau']]);
            $students = $stmt->fetchAll();
        } else {
            $students = [];
        }
    } else {
        $stmt = executeQuery("SELECT * FROM etudiants ORDER BY nom, prenom");
        $students = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $students = [];
}

// Get grades
try {
    $query = "
        SELECT g.*, e.nom, e.prenom, c.titre as course_title, a.nom as admin_name 
        FROM grades g 
        LEFT JOIN etudiants e ON g.id_etu = e.id_etu 
        LEFT JOIN cours c ON g.id_course = c.id_course 
        LEFT JOIN admins a ON g.id_admin = a.id_admin 
        WHERE g.id_admin = ?
    ";
    $params = [getCurrentUserId()];
    
    if ($selectedCourse) {
        $query .= " AND g.id_course = ?";
        $params[] = $selectedCourse;
    }
    
    $query .= " ORDER BY e.nom, e.prenom, g.matiere";
    
    $stmt = executeQuery($query, $params);
    $grades = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Erreur lors du chargement des notes.';
    error_log("Load grades error: " . $e->getMessage());
    $grades = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Notes - EduTrack</title>
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
                    <h1 class="page-title">Gestion des Notes</h1>
                    <p class="page-subtitle">Évaluez et suivez les performances académiques</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGradeModal">
                        <i class="fas fa-plus me-2"></i>Ajouter Note
                    </button>
                    <button class="btn btn-success" onclick="exportGrades()">
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

                <!-- Grades Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card border-0 shadow-lg h-100 stats-card" style="background: var(--info-gradient);">
                            <div class="card-body p-4 text-white position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="small text-white-50 text-uppercase fw-bold mb-2">
                                            Total Notes
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php echo count($grades); ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-chart-bar me-1"></i>
                                            <span>Saisies</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-chart-line fa-2x text-white"></i>
                                        </div>
                                    </div>
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
                                            Moyenne Générale
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php 
                                            $totalGrades = array_sum(array_column($grades, 'grade'));
                                            $avgGrade = count($grades) > 0 ? round($totalGrades / count($grades), 1) : 0;
                                            echo $avgGrade;
                                            ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-trophy me-1"></i>
                                            <span>/20</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-trophy fa-2x text-white"></i>
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
                                            Matières
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php 
                                            $matieres = array_unique(array_column($grades, 'matiere'));
                                            echo count($matieres);
                                            ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-book-open me-1"></i>
                                            <span>Différentes</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-book-open fa-2x text-white"></i>
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
                                            Étudiants Notés
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php 
                                            $studentsWithGrades = array_unique(array_column($grades, 'id_etu'));
                                            echo count($studentsWithGrades);
                                            ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-users me-1"></i>
                                            <span>Évalués</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-user-graduate fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card border-0 shadow-lg mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filtres
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row align-items-end">
                            <div class="col-md-4">
                                <label for="course" class="form-label">Filtrer par cours</label>
                                <select class="form-control" id="course" name="course" onchange="this.form.submit()">
                                    <option value="">Tous les cours</option>
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

                <!-- Grades Table -->
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Liste des Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Étudiant</th>
                                        <th>Cours</th>
                                        <th>Matière</th>
                                        <th>Note</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($grades)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">
                                                Aucune note trouvée
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($grades as $grade): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($grade['nom'] . ' ' . $grade['prenom']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo htmlspecialchars($grade['course_title']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($grade['matiere']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $grade['grade'] >= 10 ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo number_format($grade['grade'], 1); ?>/20
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                                            onclick="editGrade(<?php echo htmlspecialchars(json_encode($grade)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteGrade(<?php echo $grade['id_grades']; ?>, '<?php echo htmlspecialchars($grade['nom'] . ' ' . $grade['prenom'] . ' - ' . $grade['matiere']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Grade Modal -->
    <div class="modal fade" id="addGradeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Ajouter une Note
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="id_course" class="form-label">Cours</label>
                            <select class="form-control" id="id_course" name="id_course" required onchange="loadStudents(this.value)">
                                <option value="">Sélectionner un cours</option>
                                <?php foreach ($adminCourses as $course): ?>
                                    <option value="<?php echo $course['id_course']; ?>" 
                                            <?php echo $selectedCourse == $course['id_course'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['titre'] . ' - ' . $course['niveau']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="id_etu" class="form-label">Étudiant</label>
                            <select class="form-control" id="id_etu" name="id_etu" required>
                                <option value="">Sélectionner un étudiant</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id_etu']; ?>">
                                        <?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="matiere" class="form-label">Matière</label>
                            <input type="text" class="form-control" id="matiere" name="matiere" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="grade" class="form-label">Note (sur 20)</label>
                            <input type="number" class="form-control" id="grade" name="grade" 
                                   min="0" max="20" step="0.1" required>
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

    <!-- Edit Grade Modal -->
    <div class="modal fade" id="editGradeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Modifier la Note
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_matiere" class="form-label">Matière</label>
                            <input type="text" class="form-control" id="edit_matiere" name="matiere" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_grade" class="form-label">Note (sur 20)</label>
                            <input type="number" class="form-control" id="edit_grade" name="grade" 
                                   min="0" max="20" step="0.1" required>
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
    <div class="modal fade" id="deleteGradeModal" tabindex="-1">
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
                        
                        <p>Êtes-vous sûr de vouloir supprimer cette note pour <strong id="delete_name"></strong> ?</p>
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
        function editGrade(grade) {
            document.getElementById('edit_id').value = grade.id_grades;
            document.getElementById('edit_matiere').value = grade.matiere;
            document.getElementById('edit_grade').value = grade.grade;
            
            new bootstrap.Modal(document.getElementById('editGradeModal')).show();
        }

        function deleteGrade(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteGradeModal')).show();
        }

        function loadStudents(courseId) {
            // This would typically be an AJAX call
            // For now, we'll reload the page with the course parameter
            if (courseId) {
                window.location.href = '?course=' + courseId;
            }
        }
    </script>
</body>
</html>
