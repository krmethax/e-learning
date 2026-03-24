-- Create Database
CREATE DATABASE IF NOT EXISTS elearning_db CHARACTER SET utf8 COLLATE utf8_general_ci;
USE elearning_db;

-- Create Table: faculties
CREATE TABLE IF NOT EXISTS faculties (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    faculty_name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create Table: branches
CREATE TABLE IF NOT EXISTS branches (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT(11) NOT NULL,
    branch_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create Table: subjects
CREATE TABLE IF NOT EXISTS subjects (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    branch_id INT(11) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- New Table: instructors
CREATE TABLE IF NOT EXISTS instructors (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    instructor_name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- New Junction Table: subject_instructors
CREATE TABLE IF NOT EXISTS subject_instructors (
    subject_id INT(11) NOT NULL,
    instructor_id INT(11) NOT NULL,
    PRIMARY KEY (subject_id, instructor_id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


