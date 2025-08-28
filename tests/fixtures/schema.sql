
CREATE TABLE test_default (
    id BIGINT UNSIGNED NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
    meta JSON DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO test_default VALUES
(101319533431619584, 'Alice', 'active', JSON_OBJECT('age', 30, 'city', 'New York')),
(101319533431619585, 'Bob', 'inactive', JSON_OBJECT('role', 'admin', 'logins', 12)),
(101319533431619586, 'Charlie', 'pending', JSON_OBJECT('interests', JSON_ARRAY('music', 'sports')))
;

CREATE TABLE test_alter (
    example VARCHAR(8) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE test_drop (
    example VARCHAR(8) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE test_truncate (
    example VARCHAR(10) NOT NULL
) ENGINE=InnoDB;

INSERT INTO test_truncate VALUES
('example001'),('example002'),('example003')
;

CREATE TABLE test_index (
    example VARCHAR(10) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE test_index_drop (
    example VARCHAR(10) NOT NULL,
    INDEX idx_example (example)
) ENGINE=InnoDB;

CREATE TABLE test_insert_id (
	id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    example VARCHAR(10) NOT NULL
) ENGINE=InnoDB;
