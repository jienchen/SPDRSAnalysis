USE users;

CREATE TABLE IF NOT EXISTS users
(
  id              int unsigned NOT NULL AUTO_INCREMENT,
  username        varchar(16) NOT NULL,
  password        varchar(20) NOT NULL,
  name            varchar(50) NOT NUll,
  email			  varchar(50) NOT NULL,

  PRIMARY KEY(id),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)

);