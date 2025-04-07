CREATE TABLE invitations
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(256)       NOT NULL UNIQUE,
    token        BINARY(16)         NOT NULL UNIQUE,
    is_admin     BOOL               NOT NULL DEFAULT FALSE,
    is_delivered BOOL               NULL,
    created_at   TIMESTAMP          DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users
(
    id                     INT AUTO_INCREMENT               PRIMARY KEY,
    encrypted_name         VARBINARY(80)                    NOT NULL,
    encrypted_email        VARBINARY(300)                   NOT NULL,
    encrypted_phone_number VARBINARY(50)                    NOT NULL,
    email_hash             BINARY(32)                       NOT NULL UNIQUE,
    password_hash          VARCHAR(255)                     NOT NULL,
    max_active_posts       INT                              NOT NULL,
    role                   ENUM('owner', 'admin', 'member') NOT NULL DEFAULT 'member',
    created_at             TIMESTAMP                        NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE authentications
(
    token_hash BINARY(32)   PRIMARY KEY,
    user_id    INT          NOT NULL,
    expires_at DATETIME     NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE posts
(
    id                    BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id               INT                   NOT NULL,
    encrypted_description VARBINARY(300)        NOT NULL,
    encrypted_link        VARBINARY(300)        NULL,

    pin_color             ENUM('red', 'blue', 'green', 'yellow', 'purple', 'pink') NOT NULL,

    created_at            TIMESTAMP             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at            DATETIME              NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

CREATE TABLE setup
(
    ready BOOL NOT NULL DEFAULT FALSE
);

INSERT INTO setup (ready)
VALUES (FALSE);

CREATE TRIGGER enforce_single_owner_insert
    BEFORE INSERT ON users
    FOR EACH ROW
BEGIN
    IF NEW.role = 'owner' THEN
        IF (SELECT COUNT(*) FROM users WHERE role = 'owner') > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only one owner allowed';
        END IF;
    END IF;
END;

CREATE TRIGGER enforce_single_owner_update
    BEFORE UPDATE ON users
    FOR EACH ROW
BEGIN
    IF OLD.role != 'owner' AND NEW.role = 'owner' THEN
        IF (SELECT COUNT(*) FROM users WHERE role = 'owner') > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only one owner allowed';
        END IF;
    END IF;
END;