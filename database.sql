CREATE DATABASE IF NOT EXISTS grading_system;
USE grading_system;

CREATE TABLE IF NOT EXISTS judges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(50) NOT NULL,
  role VARCHAR(20) NOT NULL
);

INSERT INTO judges (username, password, role) VALUES
('judge1','123','judge'),
('judge2','123','judge'),
('judge3','123','judge'),
('judge4','123','judge'),
('admin','admin123','admin')
ON DUPLICATE KEY UPDATE password=VALUES(password), role=VALUES(role);

CREATE TABLE IF NOT EXISTS grades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_number VARCHAR(50) NOT NULL,
  judge_name VARCHAR(50) NOT NULL,
  total INT NOT NULL,
  comments TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);