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
                $nom = trim($_POST['nom'] ?? '');
                $prenom = trim($_POST['prenom'] ?? '');
                $niveau = trim($_POST['niveau'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($nom) || empty($prenom) || empty($niveau) || empty($username) || empty($password)) {
                    $error = 'Tous les champs sont requis.';
                } else {
                    try {
                        // Check if username already exists
                        $stmt = executeQuery("SELECT COUNT(*) as count FROM etudiants WHERE username = ?", [$username]);
                        if ($stmt->fetch()['count'] > 0) {
                            $error = 'Ce nom d\'utilisateur existe déjà.';
                        } else {
                            // Hash password and insert student
                            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                            executeQuery(
                                "INSERT INTO etudiants (nom, prenom, niveau, username, password, image) VALUES (?, ?, ?, ?, ?, ?)",
                                [$nom, $prenom, $niveau, $username, $hashedPassword, 1]
                            );
                            $success = 'Étudiant ajouté avec succès.';
                        }
                    } catch (Exception $e) {
                        $error = 'Erreur lors de l\'ajout de l\'étudiant.';
                        error_log("Add student error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'update':
                $id = $_POST['id'] ?? '';
                $nom = trim($_POST['nom'] ?? '');
                $prenom = trim($_POST['prenom'] ?? '');
                $niveau = trim($_POST['niveau'] ?? '');
                $username = trim($_POST['username'] ?? '');
                
                if (empty($id) || empty($nom) || empty($prenom) || empty($niveau) || empty($username)) {
                    $error = 'Tous les champs sont requis.';
                } else {
                    try {
                        // Check if username exists for other students
                        $stmt = executeQuery(
                            "SELECT COUNT(*) as count FROM etudiants WHERE username = ? AND id_etu != ?", 
                            [$username, $id]
                        );
                        if ($stmt->fetch()['count'] > 0) {
                            $error = 'Ce nom d\'utilisateur existe déjà.';
                        } else {
                            executeQuery(
                                "UPDATE etudiants SET nom = ?, prenom = ?, niveau = ?, username = ? WHERE id_etu = ?",
                                [$nom, $prenom, $niveau, $username, $id]
                            );
                            $success = 'Étudiant modifié avec succès.';
                        }
                    } catch (Exception $e) {
                        $error = 'Erreur lors de la modification de l\'étudiant.';
                        error_log("Update student error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? '';
                if (empty($id)) {
                    $error = 'ID étudiant requis.';
                } else {
                    try {
                        beginTransaction();
                        
                        // Delete related records first
                        executeQuery("DELETE FROM attendance WHERE id_etu = ?", [$id]);
                        executeQuery("DELETE FROM grades WHERE id_etu = ?", [$id]);
                        
                        // Delete student
                        executeQuery("DELETE FROM etudiants WHERE id_etu = ?", [$id]);
                        
                        commitTransaction();
                        $success = 'Étudiant supprimé avec succès.';
                    } catch (Exception $e) {
                        rollbackTransaction();
                        $error = 'Erreur lors de la suppression de l\'étudiant.';
                        error_log("Delete student error: " . $e->getMessage());
                    }
                }
                break;
        }
    }
}

// Get all students
try {
    $stmt = executeQuery("SELECT * FROM etudiants ORDER BY nom, prenom");
    $students = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Erreur lors du chargement des étudiants.';
    error_log("Load students error: " . $e->getMessage());
    $students = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - EduTrack</title>
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
                    <h1 class="page-title">Gestion des Étudiants</h1>
                    <p class="page-subtitle">Gérez les profils et informations des étudiants</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fas fa-user-plus me-2"></i>Ajouter Étudiant
                    </button>
                    <button class="btn btn-success" onclick="exportStudents()">
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

                <!-- Students Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card border-0 shadow-lg h-100 stats-card" style="background: var(--primary-gradient);">
                            <div class="card-body p-4 text-white position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="small text-white-50 text-uppercase fw-bold mb-2">
                                            Total Étudiants
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php echo count($students); ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-users me-1"></i>
                                            <span>Inscrits</span>
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
                    <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                        <div class="card border-0 shadow-lg h-100 stats-card" style="background: var(--success-gradient);">
                            <div class="card-body p-4 text-white position-relative">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="small text-white-50 text-uppercase fw-bold mb-2">
                                            Actifs
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php echo count($students); ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <span>Étudiants actifs</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-user-check fa-2x text-white"></i>
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
                                            Niveaux
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php 
                                            $niveaux = array_unique(array_column($students, 'niveau'));
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
                                            <i class="fas fa-graduation-cap fa-2x text-white"></i>
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
                                            Récents
                                        </div>
                                        <div class="h1 mb-0 fw-bold" style="font-size: 2.5rem;">
                                            <?php echo count($students); ?>
                                        </div>
                                        <div class="small text-white-50 mt-2 d-flex align-items-center">
                                            <i class="fas fa-clock me-1"></i>
                                            <span>Ce mois</span>
                                        </div>
                                    </div>
                                    <div class="stats-icon ms-3">
                                        <div class="icon-circle">
                                            <i class="fas fa-user-plus fa-2x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="card border-0 shadow-lg">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Liste des Étudiants
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Niveau</th>
                                        <th>Nom d'utilisateur</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                Aucun étudiant trouvé
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['id_etu']); ?></td>
                                                <td><?php echo htmlspecialchars($student['nom']); ?></td>
                                                <td><?php echo htmlspecialchars($student['prenom']); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo htmlspecialchars($student['niveau']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                                            onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteStudent(<?php echo $student['id_etu']; ?>, '<?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenom']); ?>')">
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

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Ajouter un Étudiant
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" required>
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
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
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

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Modifier l'Étudiant
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="edit_prenom" name="prenom" required>
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
                        
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
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
    <div class="modal fade" id="deleteStudentModal" tabindex="-1">
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
                        
                        <p>Êtes-vous sûr de vouloir supprimer l'étudiant <strong id="delete_name"></strong> ?</p>
                        <p class="text-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Cette action supprimera également toutes les données associées (notes, présences).
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
        function editStudent(student) {
            document.getElementById('edit_id').value = student.id_etu;
            document.getElementById('edit_nom').value = student.nom;
            document.getElementById('edit_prenom').value = student.prenom;
            document.getElementById('edit_niveau').value = student.niveau;
            document.getElementById('edit_username').value = student.username;
            
            new bootstrap.Modal(document.getElementById('editStudentModal')).show();
        }

        function deleteStudent(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_name').textContent = name;
            
            new bootstrap.Modal(document.getElementById('deleteStudentModal')).show();
        }
    </script>
</body>
</html>
