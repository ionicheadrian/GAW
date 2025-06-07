DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS waste_categories;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS collections;

-- useri
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('citizen', 'staff', 'admin') DEFAULT 'citizen',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- tipuri de deseuri 
CREATE TABLE waste_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('menajer', 'hartie', 'plastic') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- locatii (atat pt rapoarte cat si pentru colectari si statistici :P)
CREATE TABLE locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    address TEXT,
    neighborhood VARCHAR(100),
    city VARCHAR(50) DEFAULT 'iasi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- rapoartele
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    location_id INT,
    waste_category_id INT,
    title VARCHAR(200),
    description TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('new', 'in_progress', 'resolved') DEFAULT 'new',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (waste_category_id) REFERENCES waste_categories(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

-- colectariile
CREATE TABLE collections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT,
    staff_id INT,
    collection_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    quantity_kg DECIMAL(8, 2),
    notes TEXT,
    FOREIGN KEY (report_id) REFERENCES reports(id),
    FOREIGN KEY (staff_id) REFERENCES users(id)
);
