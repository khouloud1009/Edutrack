-- EduTrack Database Setup for MySQL
CREATE DATABASE IF NOT EXISTS edutrack_db;
USE edutrack_db;

-- Create tables with MySQL-compatible syntax
CREATE TABLE IF NOT EXISTS admins (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    password VARCHAR(100),
    username VARCHAR(100) UNIQUE
);

CREATE TABLE IF NOT EXISTS etudiants (
    id_etu INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    niveau VARCHAR(50),
    image INT,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS cours (
    id_course INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(100),
    niveau VARCHAR(50),
    id_admin INT,
    FOREIGN KEY (id_admin) REFERENCES admins(id_admin)
);

CREATE TABLE IF NOT EXISTS attendance (
    id_att INT AUTO_INCREMENT PRIMARY KEY,
    heure TIME,
    date DATE,
    id_etu INT,
    id_course INT,
    FOREIGN KEY (id_etu) REFERENCES etudiants(id_etu),
    FOREIGN KEY (id_course) REFERENCES cours(id_course)
);

CREATE TABLE IF NOT EXISTS grades (
    id_grades INT AUTO_INCREMENT PRIMARY KEY,
    grade FLOAT,
    matiere VARCHAR(100),
    id_etu INT,
    id_course INT,
    id_admin INT,
    FOREIGN KEY (id_etu) REFERENCES etudiants(id_etu),
    FOREIGN KEY (id_course) REFERENCES cours(id_course),
    FOREIGN KEY (id_admin) REFERENCES admins(id_admin)
);

CREATE TABLE IF NOT EXISTS events (
    id_event INT AUTO_INCREMENT PRIMARY KEY,
    id_admin INT,
    id_course INT,
    description TEXT,
    date DATE,
    FOREIGN KEY (id_admin) REFERENCES admins(id_admin),
    FOREIGN KEY (id_course) REFERENCES cours(id_course)
);

CREATE TABLE IF NOT EXISTS attendance_sessions (
    id_session INT AUTO_INCREMENT PRIMARY KEY,
    id_admin INT,
    id_course INT,
    start_time TIMESTAMP,
    end_time TIMESTAMP,
    actual_end_time TIMESTAMP NULL,
    duration_minutes INT DEFAULT 120,
    status VARCHAR(20) DEFAULT 'active',
    FOREIGN KEY (id_admin) REFERENCES admins(id_admin),
    FOREIGN KEY (id_course) REFERENCES cours(id_course)
);

-- Insert test data
-- Password for all admins: "admin123"
INSERT IGNORE INTO admins (nom, password, username) VALUES
('Ahmed Bennani', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'a.bennani'),
('Fatima Alaoui', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'f.alaoui'),
('Omar Chakir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'o.chakir');

-- Password for all students: "student123"
INSERT IGNORE INTO etudiants (nom, prenom, niveau, image, username, password) VALUES
('Idrissi', 'Youssef', 'Licence 2', 1, 'y.idrissi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Benali', 'Aicha', 'Master 1', 2, 'a.benali', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Tazi', 'Mehdi', 'Licence 3', 3, 'm.tazi', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample courses
INSERT IGNORE INTO cours (titre, niveau, id_admin) VALUES
('Mathématiques Appliquées', 'Licence 2', 1),
('Informatique Avancée', 'Master 1', 2),
('Gestion de Projet', 'Licence 3', 3);

-- Insert sample grades
INSERT IGNORE INTO grades (grade, matiere, id_etu, id_course, id_admin) VALUES
(16.5, 'Algèbre', 1, 1, 1),
(14.0, 'Analyse', 1, 1, 1),
(18.0, 'Programmation', 2, 2, 2),
(15.5, 'Base de données', 2, 2, 2),
(13.5, 'Management', 3, 3, 3),
(16.0, 'Leadership', 3, 3, 3);

-- Insert sample attendance
INSERT IGNORE INTO attendance (heure, date, id_etu, id_course) VALUES
('08:00:00', '2025-05-15', 1, 1),
('08:00:00', '2025-05-15', 2, 2),
('08:00:00', '2025-05-15', 3, 3),
('08:00:00', '2025-05-16', 1, 1),
('08:00:00', '2025-05-16', 2, 2);

-- Insert sample events
INSERT IGNORE INTO events (id_admin, id_course, description, date) VALUES
(1, 1, 'Examen de Mathématiques Appliquées', '2025-06-10'),
(2, 2, 'Projet final Informatique', '2025-06-15'),
(3, 3, 'Présentation Gestion de Projet', '2025-06-20');