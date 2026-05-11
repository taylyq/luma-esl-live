ALTER TABLE users
    ADD COLUMN age_confirmed_at DATETIME NULL AFTER status;

ALTER TABLE users
    ADD COLUMN terms_accepted_at DATETIME NULL AFTER age_confirmed_at;
