<?php
function install() {
    $db = db();
    $db->exec("
    CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      first_name VARCHAR(80),
      last_name VARCHAR(80),
      email VARCHAR(180) UNIQUE NOT NULL,
      phone VARCHAR(30),
      city VARCHAR(80),
      country VARCHAR(80),
      bio TEXT,
      photo VARCHAR(255),
      role ENUM('user','admin') DEFAULT 'user',
      password VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS trips (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      name VARCHAR(255) NOT NULL,
      description TEXT,
      start_date DATE,
      end_date DATE,
      cover_photo VARCHAR(255),
      budget DECIMAL(12,2) DEFAULT 0,
      is_public TINYINT(1) DEFAULT 0,
      status ENUM('upcoming','ongoing','completed') DEFAULT 'upcoming',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS stops (
      id INT AUTO_INCREMENT PRIMARY KEY,
      trip_id INT NOT NULL,
      city VARCHAR(120),
      country VARCHAR(80),
      start_date DATE,
      end_date DATE,
      notes TEXT,
      budget DECIMAL(12,2) DEFAULT 0,
      sort_order INT DEFAULT 0,
      FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS activities (
      id INT AUTO_INCREMENT PRIMARY KEY,
      stop_id INT NOT NULL,
      name VARCHAR(255),
      category VARCHAR(80),
      cost DECIMAL(10,2) DEFAULT 0,
      duration_hrs DECIMAL(4,1) DEFAULT 1,
      description TEXT,
      activity_date DATE,
      FOREIGN KEY (stop_id) REFERENCES stops(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS expenses (
      id INT AUTO_INCREMENT PRIMARY KEY,
      trip_id INT NOT NULL,
      category VARCHAR(80),
      description VARCHAR(255),
      qty VARCHAR(80),
      unit_cost DECIMAL(10,2) DEFAULT 0,
      amount DECIMAL(10,2) DEFAULT 0,
      FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS checklist_items (
      id INT AUTO_INCREMENT PRIMARY KEY,
      trip_id INT NOT NULL,
      category VARCHAR(80) DEFAULT 'General',
      item VARCHAR(255),
      packed TINYINT(1) DEFAULT 0,
      FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS trip_notes (
      id INT AUTO_INCREMENT PRIMARY KEY,
      trip_id INT NOT NULL,
      stop_id INT,
      title VARCHAR(255),
      content TEXT,
      note_day DATE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
    );
    CREATE TABLE IF NOT EXISTS community_posts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_id INT NOT NULL,
      trip_id INT,
      title VARCHAR(255),
      content TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ");
    // Seed admin (email: admin@traveloop.com, password: admin123)
    $chk = $db->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
    if (!$chk) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (first_name,last_name,email,role,password) VALUES ('Admin','User','admin@traveloop.com','admin',?)")->execute([$hash]);
    }
}