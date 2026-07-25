-- Hospital Management System — MySQL schema (used by the PHP frontend)
-- Run this once against a fresh database, e.g.:
--   mysql -u root -p -e "CREATE DATABASE hospital_management_system"
--   mysql -u root -p hospital_management_system < schema.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('Admin','Receptionist','Nurse','Doctor','LabTechnician','Pharmacist','BillingAccountant','Patient') NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_activity_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS patients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  age INT NOT NULL,
  gender ENUM('Male','Female','Other') NOT NULL,
  cnic VARCHAR(30),
  phone VARCHAR(30),
  emergency_contact VARCHAR(100),
  allergies TEXT,               -- comma separated
  chronic_conditions TEXT,      -- comma separated
  user_account_id INT NULL,     -- linked login for the Patient Portal
  registered_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_account_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (registered_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX (cnic), INDEX (phone), INDEX (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  scheduled_at DATETIME NOT NULL,
  status ENUM('Booked','Rescheduled','Cancelled','CheckedIn','Completed') NOT NULL DEFAULT 'Booked',
  booked_by INT NOT NULL,
  booked_via_portal TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (booked_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS encounters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  token_number VARCHAR(20) NOT NULL UNIQUE,
  status ENUM('WaitingForNurse','WaitingForDoctor','InConsultation','AwaitingLab','AwaitingPharmacy','AwaitingBilling','Discharged') NOT NULL DEFAULT 'WaitingForNurse',
  checked_in_by INT NOT NULL,
  registration_fee DECIMAL(10,2) NOT NULL DEFAULT 500,

  blood_pressure VARCHAR(20),
  pulse INT,
  temperature DECIMAL(4,1),
  respiratory_rate INT,
  weight DECIMAL(5,1),
  is_high_risk TINYINT(1) NOT NULL DEFAULT 0,
  vitals_recorded_by INT NULL,
  vitals_recorded_at DATETIME NULL,
  triage_fee DECIMAL(10,2) NULL,

  assigned_doctor INT NULL,
  consultation_fee DECIMAL(10,2) NULL,

  discharged_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (checked_in_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (vitals_recorded_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (assigned_doctor) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clinical_notes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  encounter_id INT NOT NULL,
  notes TEXT NOT NULL,
  written_by INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (encounter_id) REFERENCES encounters(id) ON DELETE CASCADE,
  FOREIGN KEY (written_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lab_test_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  encounter_id INT NOT NULL,
  patient_id INT NOT NULL,
  ordered_by INT NOT NULL,
  test_name VARCHAR(150) NOT NULL,
  priority ENUM('Routine','STAT') NOT NULL DEFAULT 'Routine',
  status ENUM('Pending','SampleCollected','ResultEntered','Published') NOT NULL DEFAULT 'Pending',
  sample_collected_at DATETIME NULL,
  result_text TEXT,
  cost DECIMAL(10,2) NOT NULL DEFAULT 1000,
  processed_by INT NULL,
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (encounter_id) REFERENCES encounters(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (ordered_by) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prescriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  encounter_id INT NOT NULL,
  patient_id INT NOT NULL,
  doctor_id INT NOT NULL,
  status ENUM('Pending','Dispensed') NOT NULL DEFAULT 'Pending',
  dispensed_by INT NULL,
  dispensed_at DATETIME NULL,
  total_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (encounter_id) REFERENCES encounters(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (dispensed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prescription_medicines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  prescription_id INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  dosage VARCHAR(100) NOT NULL,
  quantity INT NOT NULL,
  instructions VARCHAR(255),
  FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  batch_number VARCHAR(50) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  unit_price DECIMAL(10,2) NOT NULL,
  expiry_date DATE NULL,
  low_stock_threshold INT NOT NULL DEFAULT 20,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  encounter_id INT NOT NULL UNIQUE,
  patient_id INT NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount DECIMAL(10,2) NOT NULL DEFAULT 0,
  insurance_covered DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_method ENUM('Cash','Card','InsuranceClaim') NULL,
  status ENUM('Pending','Paid') NOT NULL DEFAULT 'Pending',
  processed_by INT NULL,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (encounter_id) REFERENCES encounters(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoice_line_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  description VARCHAR(200) NOT NULL,
  source_role VARCHAR(50) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(100) NOT NULL,
  details TEXT,
  ip VARCHAR(45),
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
