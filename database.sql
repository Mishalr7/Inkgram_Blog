CREATE DATABASE IF NOT EXISTS inkwell_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inkwell_blog;

CREATE TABLE IF NOT EXISTS users (
  id INT(11) NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  bio TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(50) NOT NULL UNIQUE,
  slug VARCHAR(50) NOT NULL UNIQUE,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  category_id INT(11) DEFAULT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  excerpt TEXT DEFAULT NULL,
  content LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, slug) VALUES
  ('Literature','literature'),('Philosophy','philosophy'),
  ('Science','science'),('Travel','travel'),('Technology','technology');

INSERT INTO users (username,password,full_name,role,bio) VALUES
('admin','$2y$10$2HftN9EbdtcSUKGOjmwMf.jxXrLUCikiWh6mkY0V95VHrpk3TSOCO','Site Administrator','admin','Chief curator and administrator of Inkgram.');

INSERT INTO posts (user_id,category_id,title,slug,excerpt,content) VALUES
(1,1,'The Smell of Old Books and What It Means','smell-of-old-books','There is a word for it — bibliosmia — the love of the smell of books. But no dictionary entry can fully explain why it feels like coming home.','There is a word for it — bibliosmia — the love of the smell of books. Lignin, the substance that makes up the cell walls of wood, breaks down over time into vanillin, the same compound responsible for the warm sweetness of vanilla. So when you press your nose into the yellowed pages of an old paperback, you are inhaling the ghost of a forest.\n\nI have spent an unreasonable portion of my adult life in secondhand bookshops. A library is not a collection of texts. It is a sediment of minds.');