ALTER TABLE `invitations`
    MODIFY `created_at` DATETIME(3) NOT NULL;

ALTER TABLE `users`
    MODIFY `created_at` DATETIME(3) NOT NULL;

ALTER TABLE `authentications`
    MODIFY `expires_at` DATETIME(3) NOT NULL;

ALTER TABLE `posts`
    MODIFY `created_at` DATETIME(3) NOT NULL,
    MODIFY `expires_at` DATETIME(3) NOT NULL,
    ADD `resolved_at` DATETIME(3) NULL AFTER `expires_at`;
