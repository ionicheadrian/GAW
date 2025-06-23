DROP TABLE IF EXISTS collections;
DROP TABLE IF EXISTS waste_deposits;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS waste_categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'citizen',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE waste_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type ENUM('menajer', 'hartie', 'plastic') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    address TEXT,
    neighborhood VARCHAR(100),
    city VARCHAR(50) DEFAULT 'iasi',
    capacity_menajer DECIMAL(8,2) DEFAULT 0,
    capacity_hartie DECIMAL(8,2) DEFAULT 0,
    capacity_plastic DECIMAL(8,2) DEFAULT 0,
    current_menajer DECIMAL(8,2) DEFAULT 0,
    current_hartie DECIMAL(8,2) DEFAULT 0,
    current_plastic DECIMAL(8,2) DEFAULT 0,
    location_type ENUM('collection_point', 'reported_location') DEFAULT 'collection_point',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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
    report_type ENUM('problem', 'overflow_alert', 'maintenance') DEFAULT 'problem',
    auto_generated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (waste_category_id) REFERENCES waste_categories(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);

CREATE TABLE waste_deposits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    location_id INT NOT NULL,
    waste_category_id INT NOT NULL,
    quantity_kg DECIMAL(8,2) NOT NULL,
    deposit_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (waste_category_id) REFERENCES waste_categories(id),
    FOREIGN KEY (verified_by) REFERENCES users(id)
);

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

DELIMITER //
CREATE TRIGGER check_capacity_overflow 
AFTER UPDATE ON locations
FOR EACH ROW
BEGIN
    DECLARE overflow_message TEXT DEFAULT '';
    DECLARE needs_alert BOOLEAN DEFAULT FALSE;
    DECLARE category_id INT DEFAULT 1;
    
    IF NEW.current_menajer > NEW.capacity_menajer AND NEW.capacity_menajer > 0 THEN
        SET overflow_message = CONCAT(overflow_message, 'Menajer: ', NEW.current_menajer, 'kg/', NEW.capacity_menajer, 'kg. ');
        SET needs_alert = TRUE;
        SET category_id = 1;
    END IF;
    
    IF NEW.current_hartie > NEW.capacity_hartie AND NEW.capacity_hartie > 0 THEN
        SET overflow_message = CONCAT(overflow_message, 'Hartie: ', NEW.current_hartie, 'kg/', NEW.capacity_hartie, 'kg. ');
        SET needs_alert = TRUE;
        SET category_id = 2;
    END IF;
    
    IF NEW.current_plastic > NEW.capacity_plastic AND NEW.capacity_plastic > 0 THEN
        SET overflow_message = CONCAT(overflow_message, 'Plastic: ', NEW.current_plastic, 'kg/', NEW.capacity_plastic, 'kg. ');
        SET needs_alert = TRUE;
        SET category_id = 3;
    END IF;
    
    IF needs_alert THEN
        INSERT INTO reports (
            user_id, location_id, waste_category_id, title, description, 
            latitude, longitude, status, priority, report_type, auto_generated, created_at
        ) VALUES (
            1,
            NEW.id,
            category_id,
            CONCAT('ALERTA: Capacitate depasita la ', NEW.name),
            CONCAT('Capacitatile au fost depasite: ', overflow_message, 'Este necesara interventia urgenta pentru golirea containerelor.'),
            NEW.latitude,
            NEW.longitude,
            'new',
            'high',
            'overflow_alert',
            TRUE,
            NOW()
        );
    END IF;
END//
DELIMITER ;

INSERT INTO waste_categories (type, description, created_at) VALUES
('menajer', 'Deseuri menajere generale', NOW()),
('hartie', 'Hartie si carton', NOW()),
('plastic', 'Materiale plastice', NOW());

INSERT INTO locations (name, latitude, longitude, address, neighborhood, city, capacity_menajer, capacity_hartie, capacity_plastic, location_type, is_active, created_at) VALUES
('Punct Colectare Copou', 47.1585, 27.6014, 'Strada Copou nr. 15', 'Copou', 'iasi', 50.00, 30.00, 20.00, 'collection_point', TRUE, NOW()),
('Punct Colectare Pacurari', 47.1820, 27.5590, 'Strada Pacurari nr. 45', 'Pacurari', 'iasi', 40.00, 25.00, 15.00, 'collection_point', TRUE, NOW()),
('Punct Colectare Tatarasi', 47.1920, 27.6180, 'Strada Tatarasi nr. 20', 'Tatarasi', 'iasi', 60.00, 40.00, 30.00, 'collection_point', TRUE, NOW()),
('Punct Colectare Bucium', 47.1320, 27.5840, 'Strada Bucium nr. 8', 'Bucium', 'iasi', 35.00, 20.00, 25.00, 'collection_point', TRUE, NOW()),
('Punct Colectare Galata', 47.1650, 27.6250, 'Strada Galata nr. 12', 'Galata', 'iasi', 45.00, 35.00, 20.00, 'collection_point', TRUE, NOW()),
('Punct Colectare Nicolina', 47.2050, 27.6390, 'Strada Nicolina nr. 35', 'Nicolina', 'iasi', 55.00, 30.00, 25.00, 'collection_point', TRUE, NOW()),
('Punct Colectare Mircea cel Batran', 47.1750, 27.5850, 'Boulevard Mircea cel Batran nr. 18', 'Tudor Vladimirescu', 'iasi', 70.00, 45.00, 35.00, 'collection_point', TRUE, NOW()),
('Punct Colectare Frumoasa', 47.1580, 27.5720, 'Strada Frumoasa nr. 7', 'Frumoasa', 'iasi', 30.00, 20.00, 15.00, 'collection_point', TRUE, NOW());

CREATE INDEX idx_waste_deposits_location_category ON waste_deposits(location_id, waste_category_id);
CREATE INDEX idx_waste_deposits_user_date ON waste_deposits(user_id, deposit_date);
CREATE INDEX idx_reports_type_status ON reports(report_type, status);
CREATE INDEX idx_locations_active_type ON locations(is_active, location_type);