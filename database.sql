-- Quotation Management System Database Schema

CREATE DATABASE IF NOT EXISTS qms_db;
USE qms_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Clients table
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    company VARCHAR(150),
    email VARCHAR(100),
    phone VARCHAR(50),
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Company settings table
CREATE TABLE IF NOT EXISTS company_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    logo VARCHAR(255),
    favicon VARCHAR(255),
    address TEXT,
    phone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(100),
    currency VARCHAR(10) DEFAULT '৳',
    default_validity_days INT DEFAULT 30,
    default_terms TEXT,
    smtp_host VARCHAR(100),
    smtp_port VARCHAR(10),
    smtp_username VARCHAR(100),
    smtp_password VARCHAR(255),
    smtp_encryption VARCHAR(10),
    from_name VARCHAR(100),
    from_email VARCHAR(100),
    email_template TEXT,
    table_font_family VARCHAR(50) DEFAULT 'inherit',
    table_font_size VARCHAR(10) DEFAULT '14px',
    table_font_style VARCHAR(20) DEFAULT 'normal',
    table_font_weight VARCHAR(20) DEFAULT 'normal',
    table_line_height VARCHAR(10) DEFAULT '1.4',
    table_letter_spacing VARCHAR(10) DEFAULT 'normal',
    table_text_transform VARCHAR(20) DEFAULT 'none',
    table_text_decoration VARCHAR(30) DEFAULT 'none',
    table_text_color VARCHAR(20) DEFAULT '#374151',
    default_authorized_signature VARCHAR(255) DEFAULT 'Authorized Signature',
    block_typography TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Quotations table
CREATE TABLE IF NOT EXISTS quotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_number VARCHAR(50) UNIQUE NOT NULL,
    client_id INT,
    subject VARCHAR(255),
    introduction TEXT,
    overview TEXT,
    support_services TEXT,
    time_period TEXT,
    validity_text TEXT,
    terms_conditions TEXT,
    status ENUM('draft', 'sent', 'accepted', 'rejected', 'expired') DEFAULT 'draft',
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    tax_percent DECIMAL(5,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    grand_total DECIMAL(10,2) DEFAULT 0.00,
    prepared_by VARCHAR(100),
    signature_image VARCHAR(255),
    authorized_signature VARCHAR(255) DEFAULT 'Authorized Signature',
    quotation_date DATE,
    valid_until DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
);

-- Quotation items table
CREATE TABLE IF NOT EXISTS quotation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT NOT NULL,
    sort_order INT DEFAULT 0,
    description TEXT,
    quantity DECIMAL(10,2) DEFAULT 1.00,
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    amount DECIMAL(10,2) DEFAULT 0.00,
    remarks VARCHAR(255),
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
);

-- Email logs table
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quotation_id INT,
    sent_to VARCHAR(255),
    cc VARCHAR(255),
    bcc VARCHAR(255),
    subject VARCHAR(255),
    message TEXT,
    sent_by INT,
    status ENUM('success', 'failed') DEFAULT 'success',
    error_message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE SET NULL,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Administrator', 'admin@qms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default company settings
INSERT INTO company_settings (company_name, address, phone, email, website, currency, default_validity_days, default_terms, from_name, from_email, table_font_family, table_font_size, table_font_style, table_font_weight, table_line_height, table_letter_spacing, table_text_transform, table_text_decoration, table_text_color, default_authorized_signature) VALUES 
('Web Space IT', '123 Business Street, Dhaka, Bangladesh', '+880 1234567890', 'info@webspaceit.com', 'www.webspaceit.com', '৳', 30, 
'1. Payment Terms: 50% advance, 50% before delivery\n2. This quotation is valid for the mentioned period only\n3. Prices are subject to change without prior notice\n4. All services are subject to our standard terms and conditions',
'Web Space IT', 'info@webspaceit.com', 'inherit', '14px', 'normal', 'normal', '1.4', 'normal', 'none', 'none', '#374151', 'Authorized Signature');
