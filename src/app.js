const express = require('express');
const cors = require('cors');
const morgan = require('morgan');

const authRoutes = require('./routes/authRoutes');
const adminRoutes = require('./routes/adminRoutes');
const receptionistRoutes = require('./routes/receptionistRoutes');
const nurseRoutes = require('./routes/nurseRoutes');
const doctorRoutes = require('./routes/doctorRoutes');
const labRoutes = require('./routes/labRoutes');
const pharmacistRoutes = require('./routes/pharmacistRoutes');
const billingRoutes = require('./routes/billingRoutes');
const patientPortalRoutes = require('./routes/patientPortalRoutes');
const { notFound, errorHandler } = require('./middleware/errorMiddleware');

const app = express();

app.use(cors());
app.use(express.json());
if (process.env.NODE_ENV !== 'test') app.use(morgan('dev'));

app.get('/api/health', (req, res) => res.json({ status: 'ok', service: 'Hospital Management System API' }));

// Feature 1: Generic Authentication Module (Login/Logout)
app.use('/api/auth', authRoutes);
// Feature 9: System Administrator
app.use('/api/admin', adminRoutes);
// Feature 2: Receptionist
app.use('/api/receptionist', receptionistRoutes);
// Feature 3: Nurse
app.use('/api/nurse', nurseRoutes);
// Feature 4: Doctor
app.use('/api/doctor', doctorRoutes);
// Feature 5: Lab Technician / Radiologist
app.use('/api/lab', labRoutes);
// Feature 6: Pharmacist
app.use('/api/pharmacist', pharmacistRoutes);
// Feature 7: Billing Accountant
app.use('/api/billing', billingRoutes);
// Feature 8: Patient Portal
app.use('/api/patient-portal', patientPortalRoutes);

app.use(notFound);
app.use(errorHandler);

module.exports = app;
