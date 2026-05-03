PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS class_requests;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS chats;
DROP TABLE IF EXISTS availabilities;
DROP TABLE IF EXISTS class_listings;
DROP TABLE IF EXISTS educator_credentials;
DROP TABLE IF EXISTS educator_profiles;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role TEXT NOT NULL CHECK (role IN ('student','educator','admin')),
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    email_verified_at TEXT NULL,
    email_verification_token TEXT NULL,
    avatar TEXT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','pending','suspended')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token TEXT NOT NULL UNIQUE,
    expires_at TEXT NOT NULL,
    used_at TEXT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE educator_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    headline TEXT NOT NULL,
    bio TEXT NOT NULL,
    years_experience INTEGER DEFAULT 0,
    native_language TEXT DEFAULT NULL,
    teaching_languages TEXT DEFAULT NULL,
    specialties TEXT DEFAULT NULL,
    hourly_rate REAL NOT NULL DEFAULT 0,
    timezone TEXT DEFAULT 'Asia/Ho_Chi_Minh',
    verified INTEGER NOT NULL DEFAULT 0,
    approval_status TEXT NOT NULL DEFAULT 'pending' CHECK (approval_status IN ('draft','pending','approved','rejected')),
    profile_photo TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE educator_credentials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    educator_id INTEGER NOT NULL,
    credential_type TEXT NOT NULL,
    file_url TEXT NOT NULL,
    verified_status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE
);

CREATE TABLE class_listings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    educator_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    class_type TEXT NOT NULL DEFAULT 'private' CHECK (class_type IN ('private','group')),
    english_level TEXT NOT NULL,
    capacity INTEGER NOT NULL DEFAULT 1,
    price REAL NOT NULL DEFAULT 0,
    zoom_link TEXT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    recurrence_rule TEXT NULL,
    status TEXT NOT NULL DEFAULT 'published' CHECK (status IN ('draft','published','archived')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE
);

CREATE TABLE availabilities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    educator_id INTEGER NOT NULL,
    day_of_week INTEGER NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    timezone TEXT NOT NULL DEFAULT 'Asia/Ho_Chi_Minh',
    FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE
);

CREATE TABLE chats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    educator_id INTEGER NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (student_id, educator_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE
);

CREATE TABLE messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    chat_id INTEGER NOT NULL,
    sender_id INTEGER NOT NULL,
    message_body TEXT NOT NULL,
    read_at TEXT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chat_id) REFERENCES chats(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE class_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
    message TEXT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (class_id, student_id),
    FOREIGN KEY (class_id) REFERENCES class_listings(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    class_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    payment_status TEXT NOT NULL DEFAULT 'manual',
    join_status TEXT NOT NULL DEFAULT 'approved',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (class_id, student_id),
    FOREIGN KEY (class_id) REFERENCES class_listings(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    educator_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    class_id INTEGER NULL,
    rating INTEGER NOT NULL,
    review_text TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'published',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    data_json TEXT NOT NULL,
    read_at TEXT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
