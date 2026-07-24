const express = require('express');
const {
  getMyLabReports,
  getMyPrescriptions,
  getMyBilling,
  bookMyAppointment,
  getMyAppointments,
} = require('../controllers/patientPortalController');
const { protect, authorize } = require('../middleware/authMiddleware');

const router = express.Router();

// Strictly read-only, except for booking an appointment, per spec.
router.use(protect, authorize('Patient'));

router.get('/lab-reports', getMyLabReports);
router.get('/prescriptions', getMyPrescriptions);
router.get('/billing', getMyBilling);
router.route('/appointments').get(getMyAppointments).post(bookMyAppointment);

module.exports = router;
