-- Hostinger-ready MySQL import for Luma ESL live demo.
-- Import this into an empty Hostinger MySQL database using phpMyAdmin.
-- This file intentionally avoids database-level and privileged routine statements.

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student','educator','admin') NOT NULL,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email_verified_at DATETIME NULL,
    email_verification_token VARCHAR(64) NULL,
    avatar VARCHAR(255) NULL,
    status ENUM('active','pending','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
    price_currency VARCHAR(10) NOT NULL DEFAULT 'USD',
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
    user_one_id BIGINT UNSIGNED NOT NULL,
    user_two_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NULL,
    educator_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_direct_chat (user_one_id, user_two_id),
    UNIQUE KEY unique_student_educator_chat (student_id, educator_id),
    CONSTRAINT fk_chats_user_one FOREIGN KEY (user_one_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_chats_user_two FOREIGN KEY (user_two_id) REFERENCES users(id) ON DELETE CASCADE,
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


CREATE TABLE IF NOT EXISTS message_blocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blocker_id BIGINT UNSIGNED NOT NULL,
    blocked_user_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_message_block (blocker_id, blocked_user_id),
    CONSTRAINT fk_message_blocks_blocker FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_blocks_blocked FOREIGN KEY (blocked_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS message_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id BIGINT UNSIGNED NOT NULL,
    reported_user_id BIGINT UNSIGNED NOT NULL,
    chat_id BIGINT UNSIGNED NOT NULL,
    message_id BIGINT UNSIGNED NULL,
    reason ENUM('spam','inappropriate','safety','other') NOT NULL DEFAULT 'other',
    details TEXT NULL,
    status ENUM('open','reviewed','dismissed') NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_message_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_reports_reported FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_reports_chat FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    CONSTRAINT fk_message_reports_message FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE SET NULL
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

INSERT INTO users (role, name, email, password, email_verified_at, avatar, status) VALUES
('admin', 'Admin', 'admin@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NOW(), NULL, 'active'),
('student', 'Minh Tran', 'student@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NOW(), NULL, 'active'),
('educator', 'Amelia Carter', 'amelia@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NOW(), NULL, 'active'),
('educator', 'Linh Nguyen', 'linh@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NOW(), NULL, 'active'),
('educator', 'Marcus Lee', 'marcus@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NOW(), NULL, 'active')
ON DUPLICATE KEY UPDATE email = VALUES(email), email_verified_at = VALUES(email_verified_at);

INSERT INTO educator_profiles (user_id, headline, bio, years_experience, native_language, teaching_languages, specialties, hourly_rate, timezone, verified, approval_status, profile_photo)
SELECT id, 'Interview English and calm conversation coaching', 'I help Vietnamese professionals speak clearly in interviews, meetings, and everyday conversations. My sessions are structured, kind, and practical.', 8, 'English', 'English,Vietnamese', 'Interview prep,Business English,Conversation', 24.00, 'Asia/Ho_Chi_Minh', 1, 'approved', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=900&q=80' FROM users WHERE email = 'amelia@luma.test'
ON DUPLICATE KEY UPDATE headline = VALUES(headline);

INSERT INTO educator_profiles (user_id, headline, bio, years_experience, native_language, teaching_languages, specialties, hourly_rate, timezone, verified, approval_status, profile_photo)
SELECT id, 'Bilingual English lessons for Vietnamese beginners', 'A gentle bilingual approach for adults who want to build confidence from the ground up, with clear explanations in Vietnamese when needed.', 6, 'Vietnamese', 'Vietnamese,English', 'Beginner English,Pronunciation,Travel English', 18.00, 'Asia/Ho_Chi_Minh', 1, 'approved', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=900&q=80' FROM users WHERE email = 'linh@luma.test'
ON DUPLICATE KEY UPDATE headline = VALUES(headline);

INSERT INTO educator_profiles (user_id, headline, bio, years_experience, native_language, teaching_languages, specialties, hourly_rate, timezone, verified, approval_status, profile_photo)
SELECT id, 'Fluency workshops for confident speaking', 'Small group Zoom workshops focused on natural rhythm, clear pronunciation, and useful speaking habits for real life.', 10, 'English', 'English', 'Group classes,Conversation,Pronunciation', 20.00, 'Asia/Ho_Chi_Minh', 1, 'approved', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=900&q=80' FROM users WHERE email = 'marcus@luma.test'
ON DUPLICATE KEY UPDATE headline = VALUES(headline);

INSERT INTO class_listings (educator_id, title, description, class_type, english_level, capacity, price, zoom_link, start_time, end_time, recurrence_rule, status)
SELECT ep.id, 'Interview Practice Lab', 'Mock interviews, confident answers, and targeted feedback for Vietnamese professionals.', 'group', 'Intermediate', 6, 15.00, 'https://zoom.us/j/1234567890', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 2 DAY), INTERVAL 60 MINUTE), 'Weekly', 'published'
FROM educator_profiles ep JOIN users u ON u.id = ep.user_id WHERE u.email = 'amelia@luma.test';

INSERT INTO class_listings (educator_id, title, description, class_type, english_level, capacity, price, zoom_link, start_time, end_time, recurrence_rule, status)
SELECT ep.id, 'Beginner Conversation Circle', 'A warm class for speaking your first full conversations with practical vocabulary.', 'group', 'Beginner', 8, 10.00, 'https://zoom.us/j/2234567890', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 3 DAY), INTERVAL 60 MINUTE), 'Weekly', 'published'
FROM educator_profiles ep JOIN users u ON u.id = ep.user_id WHERE u.email = 'linh@luma.test';

INSERT INTO class_listings (educator_id, title, description, class_type, english_level, capacity, price, zoom_link, start_time, end_time, recurrence_rule, status)
SELECT ep.id, 'Pronunciation Studio', 'Live drills and feedback for clearer, more natural English pronunciation.', 'private', 'All levels', 1, 22.00, 'https://zoom.us/j/3234567890', DATE_ADD(NOW(), INTERVAL 4 DAY), DATE_ADD(DATE_ADD(NOW(), INTERVAL 4 DAY), INTERVAL 45 MINUTE), NULL, 'published'
FROM educator_profiles ep JOIN users u ON u.id = ep.user_id WHERE u.email = 'marcus@luma.test';

INSERT INTO reviews (educator_id, student_id, class_id, rating, review_text, status)
SELECT ep.id, s.id, NULL, 5, 'Clear, patient, and practical. I felt more confident after one class.', 'published'
FROM educator_profiles ep JOIN users u ON u.id = ep.user_id JOIN users s ON s.email = 'student@luma.test'
WHERE u.email = 'amelia@luma.test';

INSERT INTO chats (user_one_id, user_two_id, student_id, educator_id)
SELECT LEAST(s.id, ep.user_id), GREATEST(s.id, ep.user_id), s.id, ep.id FROM users s, educator_profiles ep JOIN users u ON u.id = ep.user_id
WHERE s.email = 'student@luma.test' AND u.email = 'amelia@luma.test'
ON DUPLICATE KEY UPDATE student_id = VALUES(student_id), educator_id = VALUES(educator_id);

INSERT INTO messages (chat_id, sender_id, message_body)
SELECT c.id, s.id, 'Can I join your next Zoom class? I am preparing for an interview.'
FROM chats c JOIN users s ON s.id = c.student_id
WHERE s.email = 'student@luma.test';

INSERT INTO messages (chat_id, sender_id, message_body)
SELECT c.id, ep.user_id, 'Absolutely. The next class is focused on interview answers and clear follow-up questions.'
FROM chats c JOIN educator_profiles ep ON ep.id = c.educator_id
JOIN users u ON u.id = ep.user_id
WHERE u.email = 'amelia@luma.test';

INSERT INTO class_requests (class_id, student_id, status, message)
SELECT cl.id, s.id, 'approved', 'I would like to join the interview class this week.'
FROM class_listings cl
JOIN educator_profiles ep ON ep.id = cl.educator_id
JOIN users teacher ON teacher.id = ep.user_id
JOIN users s ON s.email = 'student@luma.test'
WHERE teacher.email = 'amelia@luma.test'
LIMIT 1
ON DUPLICATE KEY UPDATE status = VALUES(status);

INSERT INTO enrollments (class_id, student_id, payment_status, join_status)
SELECT cr.class_id, cr.student_id, 'manual', 'approved'
FROM class_requests cr
WHERE cr.status = 'approved'
LIMIT 1
ON DUPLICATE KEY UPDATE join_status = VALUES(join_status);
