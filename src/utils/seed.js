require('dotenv').config();
const mongoose = require('mongoose');
const connectDB = require('../config/db');
const User = require('../models/User');
const InventoryItem = require('../models/InventoryItem');

const demoUsers = [
  { name: 'System Admin', email: 'admin@hms.test', password: 'password123', role: 'Admin' },
  { name: 'Rita Receptionist', email: 'receptionist@hms.test', password: 'password123', role: 'Receptionist' },
  { name: 'Nadia Nurse', email: 'nurse@hms.test', password: 'password123', role: 'Nurse' },
  { name: 'Dr. Nabeel Tahir', email: 'doctor@hms.test', password: 'password123', role: 'Doctor' },
  { name: 'Liam LabTech', email: 'labtech@hms.test', password: 'password123', role: 'LabTechnician' },
  { name: 'Pia Pharmacist', email: 'pharmacist@hms.test', password: 'password123', role: 'Pharmacist' },
  { name: 'Bilal Billing', email: 'billing@hms.test', password: 'password123', role: 'BillingAccountant' },
  { name: 'Sara Shabir', email: 'patient@hms.test', password: 'password123', role: 'Patient' },
];

const demoInventory = [
  { name: 'Paracetamol 500mg', batchNumber: 'B-001', quantity: 200, unitPrice: 5, lowStockThreshold: 30 },
  { name: 'Amoxicillin 250mg', batchNumber: 'B-002', quantity: 150, unitPrice: 12, lowStockThreshold: 25 },
  { name: 'Ibuprofen 400mg', batchNumber: 'B-003', quantity: 15, unitPrice: 8, lowStockThreshold: 20 },
];

const seed = async () => {
  await connectDB();
  console.log('Seeding demo data...');

  for (const u of demoUsers) {
    const exists = await User.findOne({ email: u.email });
    if (!exists) {
      await User.create(u);
      console.log(`Created user: ${u.email} / password123 (${u.role})`);
    }
  }

  for (const item of demoInventory) {
    const exists = await InventoryItem.findOne({ name: item.name });
    if (!exists) {
      await InventoryItem.create(item);
      console.log(`Created inventory item: ${item.name}`);
    }
  }

  console.log('Seeding complete.');
  await mongoose.connection.close();
  process.exit(0);
};

seed().catch((err) => {
  console.error(err);
  process.exit(1);
});
