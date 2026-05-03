CREATE DATABASE IF NOT EXISTS luma_esl CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE luma_esl;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student','educator','admin') NOT NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    status ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS educator_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    headline VARCHAR(190) NOT NULL,
    bio TEXT NOT NULL,
    years_experience TINYINT UNSIGNED DEFAULT 0,
    native_language VARCHAR(80) DEFAULT NULL,
    teaching_languages VARCHAR(190) DEFAULT NULL,
    specialties VARCHAR(255) DEFAULT NULL,
    hourly_rate DECIMAL(8,2) NOT NULL DEFAULT 0,
    timezone VARCHAR(80) DEFAULT 'Asia/Ho_Chi_Minh',
    verified TINYINT(1) NOT NULL DEFAULT 0,
    approval_status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'pending',
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_educator_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS educator_credentials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    educator_id BIGINT UNSIGNED NOT NULL,
    credential_type VARCHAR(120) NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    verified_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_credentials_educator FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS class_listings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    educator_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    class_type ENUM('private','group') NOT NULL DEFAULT 'private',
    english_level VARCHAR(80) NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 1,
    price DECIMAL(8,2) NOT NULL DEFAULT 0,
    zoom_link VARCHAR(255) NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    recurrence_rule VARCHAR(160) NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_classes_educator FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS availabilities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    educator_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT UNSIGNED NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    timezone VARCHAR(80) NOT NULL DEFAULT 'Asia/Ho_Chi_Minh',
    CONSTRAINT fk_availability_educator FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    educator_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_educator_chat (student_id, educator_id),
    CONSTRAINT fk_chats_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_chats_educator FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chat_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    message_body TEXT NOT NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_chat FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS class_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_class_student_request (class_id, student_id),
    CONSTRAINT fk_requests_class FOREIGN KEY (class_id) REFERENCES class_listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_requests_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    payment_status ENUM('manual','paid','refunded') NOT NULL DEFAULT 'manual',
    join_status ENUM('approved','attended','missed') NOT NULL DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_class_student_enrollment (class_id, student_id),
    CONSTRAINT fk_enrollments_class FOREIGN KEY (class_id) REFERENCES class_listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    educator_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NULL,
    rating TINYINT UNSIGNED NOT NULL,
    review_text TEXT NOT NULL,
    status ENUM('pending','published','hidden') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_educator FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(80) NOT NULL,
    data_json JSON NOT NULL,
    read_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
