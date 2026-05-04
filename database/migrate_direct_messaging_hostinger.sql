-- Run once in Hostinger phpMyAdmin after deploying the direct messaging feature.
-- Select the existing app database first: u223591156_lumaesl.

ALTER TABLE chats
    DROP FOREIGN KEY fk_chats_student,
    DROP FOREIGN KEY fk_chats_educator;

ALTER TABLE chats
    ADD COLUMN user_one_id BIGINT UNSIGNED NULL AFTER id,
    ADD COLUMN user_two_id BIGINT UNSIGNED NULL AFTER user_one_id;

UPDATE chats c
JOIN educator_profiles ep ON ep.id = c.educator_id
SET c.user_one_id = LEAST(c.student_id, ep.user_id),
    c.user_two_id = GREATEST(c.student_id, ep.user_id)
WHERE c.user_one_id IS NULL OR c.user_two_id IS NULL;

ALTER TABLE chats
    MODIFY student_id BIGINT UNSIGNED NULL,
    MODIFY educator_id BIGINT UNSIGNED NULL,
    MODIFY user_one_id BIGINT UNSIGNED NOT NULL,
    MODIFY user_two_id BIGINT UNSIGNED NOT NULL,
    ADD UNIQUE KEY unique_direct_chat (user_one_id, user_two_id),
    ADD CONSTRAINT fk_chats_user_one FOREIGN KEY (user_one_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_chats_user_two FOREIGN KEY (user_two_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_chats_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_chats_educator FOREIGN KEY (educator_id) REFERENCES educator_profiles(id) ON DELETE CASCADE;
