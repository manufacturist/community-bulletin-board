ALTER TABLE users
    ADD COLUMN theme VARCHAR(12) NOT NULL DEFAULT 'cork' AFTER max_active_posts;
