CREATE DATABASE twitter;
GRANT ALL ON twitter.* TO twitter@localhost IDENTIFIED BY 'password';
USE twitter;

DROP TABLE tweets;
CREATE TABLE tweets (
	id INT PRIMARY KEY AUTO_INCREMENT,
	status_id BIGINT NOT NULL,
	created_at DATETIME NOT NULL,
	screen_name TEXT NOT NULL,
	message TEXT NOT NULL,
	approved TINYINT NOT NULL DEFAULT false,
	shown DATETIME
);
ALTER TABLE tweets ADD INDEX status_id (status_id);
ALTER TABLE tweets ADD INDEX approved (approved);

CREATE TABLE shown (
	status_id BIGINT NOT NULL,
	display_id INT NOT NULL,
	shown DATETIME
);
ALTER TABLE shown ADD INDEX status_id (status_id);
ALTER TABLE shown ADD INDEX display_shown (display_id, shown);

CREATE TABLE auto_approve (
	screen_name VARCHAR(40) UNIQUE NOT NULL,
	approved TINYINT NOT NULL
);
