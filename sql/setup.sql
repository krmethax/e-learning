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
    subject_name_en VARCHAR(255) DEFAULT NULL,
    credits VARCHAR(50) DEFAULT NULL,
    description_th TEXT DEFAULT NULL,
    description_en TEXT DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    start_date DATETIME DEFAULT NULL, -- Enrollment Start
    end_date DATETIME DEFAULT NULL,   -- Enrollment End
    course_start DATETIME DEFAULT NULL,
    course_end DATETIME DEFAULT NULL,
    is_visible TINYINT(1) DEFAULT 1,
    enrollment_type VARCHAR(20) DEFAULT 'open',
    enrollment_key VARCHAR(255) DEFAULT NULL,
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
    firstname VARCHAR(100) DEFAULT NULL,
    lastname VARCHAR(100) DEFAULT NULL,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    email_display INT(1) DEFAULT 1,
    moodlenet_id VARCHAR(255) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    country VARCHAR(100) DEFAULT NULL,
    timezone VARCHAR(50) DEFAULT 'Asia/Bangkok',
    description TEXT DEFAULT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    role VARCHAR(20) DEFAULT 'user',
    last_access TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table: user_blogs
CREATE TABLE IF NOT EXISTS user_blogs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table: forum_discussions
CREATE TABLE IF NOT EXISTS forum_discussions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table: learning_plans
CREATE TABLE IF NOT EXISTS learning_plans (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    plan_name VARCHAR(255) NOT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table: browser_sessions
CREATE TABLE IF NOT EXISTS browser_sessions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    browser VARCHAR(255),
    ip_address VARCHAR(45),
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Table: system_logs (Audit Logs)
CREATE TABLE IF NOT EXISTS system_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) DEFAULT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- New Junction Table: user_subjects (Enrollments)
CREATE TABLE IF NOT EXISTS user_subjects (
    user_id INT(11) NOT NULL,
    subject_id INT(11) NOT NULL,
    PRIMARY KEY (user_id, subject_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


