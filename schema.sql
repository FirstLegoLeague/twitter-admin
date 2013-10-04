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

CREATE TABLE shown (
	status_id BIGINT NOT NULL,
	display_id INT NOT NULL,
	shown DATETIME
);

CREATE TABLE auto_approve (
	screen_name VARCHAR(40) UNIQUE NOT NULL,
	approved TINYINT NOT NULL
);
