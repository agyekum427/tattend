-- ============================================================
-- T ATTEND — Database Schema
-- An On-Demand Attendance System for Universities
-- Import this file in phpMyAdmin, or run:
--   mysql -u root -p < tattend.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS tattend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tattend;

-- ------------------------------------------------------------
-- Admins — manage lecturer accounts on the platform
-- ------------------------------------------------------------
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Lecturers — each lecturer belongs to exactly one Admin (tenant).
-- This is what makes the platform multi-tenant: an Admin can only
-- ever see/manage lecturers (and, transitively, courses, students,
-- sessions, and attendance records) where admin_id matches them.
-- ------------------------------------------------------------
CREATE TABLE lecturers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','disabled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Courses — a Lecturer teaches one or more Courses
-- ------------------------------------------------------------
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    course_name VARCHAR(150) NOT NULL,
    course_code VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Students — enrolled under a Course, identified by index number
-- ------------------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    index_number VARCHAR(40) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_index_per_course (course_id, index_number),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Attendance Sessions — time-limited, generates link/QR code
-- ------------------------------------------------------------
CREATE TABLE attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    session_code VARCHAR(20) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    opens_at DATETIME NOT NULL,
    closes_at DATETIME NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES lecturers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Attendance Records — one auditable record per student per session
-- ------------------------------------------------------------
CREATE TABLE attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_submission (session_id, student_id),
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seed data (accounts are NOT inserted here because passwords
-- must be hashed with PHP's password_hash(). After importing
-- this file, open http://localhost/tattend/database/seed_users.php
-- once in your browser to create the demo admin + lecturer logins
-- and a demo course with sample students. That script deletes
-- itself after running for security.)
-- ------------------------------------------------------------
