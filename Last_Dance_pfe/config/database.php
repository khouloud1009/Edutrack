<?php
/**
 * Database Configuration
 * EduTrack Management System
 */

// Database credentials
$host = 'localhost';
$dbname = 'edutrack_db';
$username = 'root';
$password = '';
// // Get database credentials from environment variables
// $host = $_ENV['PGHOST'] ?? 'localhost';
// $dbname = $_ENV['PGDATABASE'] ?? 'edutrack_db';
// $username = $_ENV['PGUSER'] ?? 'root';
// $password = $_ENV['PGPASSWORD'] ?? 'root';
// $port = $_ENV['PGPORT'] ?? '5432';

// PDO options for better security and error handling
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
    
    // Set timezone
    $pdo->exec("SET time_zone = '+00:00'");
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}

/**
 * Function to test database connection
 */
function testDatabaseConnection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return $stmt !== false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Function to get database version
 */
function getDatabaseVersion() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch();
        return $result['version'];
    } catch (PDOException $e) {
        return "Unknown";
    }
}

/**
 * Function to safely execute prepared statements
 */
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query execution failed: " . $e->getMessage() . " Query: " . $query);
        throw new Exception("Erreur lors de l'exécution de la requête.");
    }
}

/**
 * Function to begin transaction
 */
function beginTransaction() {
    global $pdo;
    return $pdo->beginTransaction();
}

/**
 * Function to commit transaction
 */
function commitTransaction() {
    global $pdo;
    return $pdo->commit();
}

/**
 * Function to rollback transaction
 */
function rollbackTransaction() {
    global $pdo;
    return $pdo->rollBack();
}
?>
