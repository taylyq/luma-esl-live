INSERT INTO users (role, name, email, password, avatar, status) VALUES
('admin', 'Admin', 'admin@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NULL, 'active'),
('student', 'Minh Tran', 'student@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NULL, 'active'),
('educator', 'Amelia Carter', 'amelia@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NULL, 'active'),
('educator', 'Linh Nguyen', 'linh@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NULL, 'active'),
('educator', 'Marcus Lee', 'marcus@luma.test', '$2y$12$6ITd8uWlC9v28xa50NSbweUEM0Dm.L.BozvZ1PJNy8oDibFtKqqVu', NULL, 'active');

INSERT INTO educator_profiles (user_id, headline, bio, years_experience, native_language, teaching_languages, specialties, hourly_rate, timezone, verified, approval_status, profile_photo)
SELECT id, 'Interview English and calm conversation coaching', 'I help Vietnamese professionals speak clearly in interviews, meetings, and everyday conversations. My sessions are structured, kind, and practical.', 8, 'English', 'English,Vietnamese', 'Interview prep,Business English,Conversation', 24.00, 'Asia/Ho_Chi_Minh', 1, 'approved', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=900&q=80' FROM users WHERE email = 'amelia@luma.test';

INSERT INTO educator_profiles (user_id, headline, bio, years_experience, native_language, teaching_languages, specialties, hourly_rate, timezone, verified, approval_status, profile_photo)
SELECT id, 'Bilingual English lessons for Vietnamese beginners', 'A gentle bilingual approach for adults who want to build confidence from the ground up, with clear explanations in Vietnamese when needed.', 6, 'Vietnamese', 'Vietnamese,English', 'Beginner English,Pronunciation,Travel English', 18.00, 'Asia/Ho_Chi_Minh', 1, 'approved', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=900&q=80' FROM users WHERE email = 'linh@luma.test';

INSERT INTO educator_profiles (user_id, headline, bio, years_experience, native_language, teaching_languages, specialties, hourly_rate, timezone, verified, approval_status, profile_photo)
SELECT id, 'Fluency workshops for confident speaking', 'Small group Zoom workshops focused on natural rhythm, clear pronunciation, and useful speaking habits for real life.', 10, 'English', 'English', 'Group classes,Conversation,Pronunciation', 20.00, 'Asia/Ho_Chi_Minh', 1, 'approved', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=900&q=80' FROM users WHERE email = 'marcus@luma.test';

INSERT INTO class_listings (educator_id, title, description, class_type, english_level, capacity, price, zoom_link, start_time, end_time, recurrence_rule, status)
SELECT ep.id, 'Interview Practice Lab', 'Mock interviews, confident answers, and targeted feedback for Vietnamese professionals.', 'group', 'Intermediate', 6, 15.00, 'https://zoom.us/j/1234567890', datetime('now', '+2 days'), datetime('now', '+2 days', '+60 minutes'), 'Weekly', 'published'
FROM educator_profiles ep JOIN users u ON u.id = ep.user_id WHERE u.email = 'amelia@luma.test';

INSERT INTO class_listings (educator_id, title, description, class_type, english_level, capacity, price, zoom_link, start_time, end_time, recurrence_rule, status)
SELECT ep.id, 'Beginner Conversation Circle', 'A warm class for speaking your first full conversations with practical vocabulary.', 'group', 'Beginner', 8, 10.00, 'https://zoom.us/j/2234567890', datetime('now', '+3 days'), datetime('now', '+3 days', '+60 minutes'), 'Weekly', 'published'
FROM educator_profiles ep JOIN users u ON u.id = ep.user_id WHERE u.email = 'linh@luma.test';

INSERT INTO class_listings (educator_id, title, description, class_type, english_level, capacity, price, zoom_link, start_time, end_time, recurrence_rule, status)
SELECT ep.id, 'Pronunciation Studio', 'Live drills and feedback for clearer, more natural English pronunciation.', 'private', 'All levels', 1, 22.00, 'https://zoom.us/j/3234567890', datetime('now', '+4 days'), datetime('now', '+4 days', '+45 minutes'), NULL, 'published'
FROM educator_profiles ep JOIN users u ON u.id = ep.user_id WHERE u.email = 'marcus@luma.test';

INSERT INTO reviews (educator_id, student_id, class_id, rating, review_text, status)
SELECT ep.id, s.id, NULL, 5, 'Clear, patient, and practical. I felt more confident after one class.', 'published'
FROM educator_profiles ep JOIN users u ON u.id = ep.user_id JOIN users s ON s.email = 'student@luma.test'
WHERE u.email = 'amelia@luma.test';

INSERT INTO chats (student_id, educator_id)
SELECT s.id, ep.id FROM users s, educator_profiles ep JOIN users u ON u.id = ep.user_id
WHERE s.email = 'student@luma.test' AND u.email = 'amelia@luma.test';

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
LIMIT 1;

INSERT INTO enrollments (class_id, student_id, payment_status, join_status)
SELECT cr.class_id, cr.student_id, 'manual', 'approved'
FROM class_requests cr
WHERE cr.status = 'approved'
LIMIT 1;
