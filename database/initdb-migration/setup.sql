CREATE TABLE invitations
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(256)       NOT NULL UNIQUE,
    token        BINARY(16)         NOT NULL UNIQUE,
    is_admin     BOOL               NOT NULL DEFAULT FALSE,
    is_delivered BOOL               NULL,
    created_at   TIMESTAMP          DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO invitations (email, token, is_admin)
VALUES ('foo@bar.baz', RANDOM_BYTES(16), TRUE);

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

DELIMITER //

CREATE TRIGGER enforce_single_owner_insert
    BEFORE INSERT ON users
    FOR EACH ROW
BEGIN
    IF NEW.role = 'owner' THEN
        IF (SELECT COUNT(*) FROM users WHERE role = 'owner') > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only one owner allowed';
        END IF;
    END IF;
END//

CREATE TRIGGER enforce_single_owner_update
    BEFORE UPDATE ON users
    FOR EACH ROW
BEGIN
    IF OLD.role != 'owner' AND NEW.role = 'owner' THEN
        IF (SELECT COUNT(*) FROM users WHERE role = 'owner') > 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only one owner allowed';
        END IF;
    END IF;
END//

-- https://github.com/lukaso/mysql_utf8_url_encode_url_decode/blob/master/url_encode_utf8.sql
DROP FUNCTION IF EXISTS `url_encode` //
CREATE DEFINER=`root`@`%` FUNCTION `url_encode`(original_text text) RETURNS text CHARSET utf8
BEGIN
	declare new_text text DEFAULT NULL;
	declare current_char varchar(10) DEFAULT '';
	declare ascii_current_char int DEFAULT 0;
	declare pointer int DEFAULT 1;
	declare hex_pointer int DEFAULT 1;

	IF original_text IS NOT NULL then
		SET new_text = '';
		while pointer <= char_length(original_text) do
			SET current_char = mid(original_text,pointer,1);
			SET ascii_current_char = ascii(current_char);
			IF current_char = ' ' then
				SET current_char = '+';
			ELSEIF NOT (ascii_current_char BETWEEN 48 AND 57 || ascii_current_char BETWEEN 65 AND 90 || ascii_current_char BETWEEN 97 AND 122
									 || ascii_current_char = 45 || ascii_current_char = 95 || ascii_current_char = 46 || ascii_current_char = 126
									 || ascii_current_char = 34
									) then
				SET current_char = hex(current_char);
				SET hex_pointer = char_length(current_char)-1;
				while hex_pointer > 0 do
					SET current_char = INSERT(current_char, hex_pointer, 0, "%");
					SET hex_pointer = hex_pointer - 2;
end while;
end IF;
			SET new_text = concat(new_text,current_char);
			SET pointer = pointer + 1;
end while;
end IF;

RETURN new_text;
END //