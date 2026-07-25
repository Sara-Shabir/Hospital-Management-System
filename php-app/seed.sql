-- Demo accounts (password for all = password123) and sample pharmacy stock.
-- Run AFTER schema.sql:
--   mysql -u root -p hospital_management_system < seed.sql

INSERT INTO users (name, email, password, role) VALUES
('System Admin',        'admin@hms.test',        '$2y$10$XZ0epKfRxffjiXv56lZXGuaaLHqMJjWxon8CQNWoDlVguuoe.J2S.', 'Admin'),
('Rita Receptionist',   'receptionist@hms.test', '$2y$10$XZ0epKfRxffjiXv56lZXGuaaLHqMJjWxon8CQNWoDlVguuoe.J2S.', 'Receptionist'),
('Nadia Nurse',         'nurse@hms.test',        '$2y$10$XZ0epKfRxffjiXv56lZXGuaaLHqMJjWxon8CQNWoDlVguuoe.J2S.', 'Nurse'),
('Dr. Nabeel Tahir',    'doctor@hms.test',       '$2y$10$XZ0epKfRxffjiXv56lZXGuaaLHqMJjWxon8CQNWoDlVguuoe.J2S.', 'Doctor'),
('Liam LabTech',        'labtech@hms.test',      '$2y$10$XZ0epKfRxffjiXv56lZXGuaaLHqMJjWxon8CQNWoDlVguuoe.J2S.', 'LabTechnician'),
('Pia Pharmacist',      'pharmacist@hms.test',   '$2y$10$XZ0epKfRxffjiXv56lZXGuaaLHqMJjWxon8CQNWoDlVguuoe.J2S.', 'Pharmacist'),
('Bilal Billing',       'billing@hms.test',      '$2y$10$XZ0epKfRxffjiXv56lZXGuaaLHqMJjWxon8CQNWoDlVguuoe.J2S.', 'BillingAccountant'),
('Sara Shabir',         'patient@hms.test',      '$2y$10$XZ0epKfRxffjiXv56lZXGuaaLHqMJjWxon8CQNWoDlVguuoe.J2S.', 'Patient');

INSERT INTO inventory_items (name, batch_number, quantity, unit_price, low_stock_threshold) VALUES
('Paracetamol 500mg', 'B-001', 200, 5, 30),
('Amoxicillin 250mg', 'B-002', 150, 12, 25),
('Ibuprofen 400mg',   'B-003', 15, 8, 20);
