const express = require('express');
const {
  searchPatients,
  registerPatient,
  getDoctorAvailability,
  bookAppointment,
  updateAppointment,
  checkInPatient,
} = require('../controllers/receptionistController');
const { protect, authorize } = require('../middleware/authMiddleware');

const router = express.Router();

router.use(protect, authorize('Receptionist', 'Admin'));

router.get('/patients/search', searchPatients);
router.post('/patients', registerPatient);
router.get('/doctors/:doctorId/availability', getDoctorAvailability);
router.post('/appointments', bookAppointment);
router.put('/appointments/:id', updateAppointment);
router.post('/checkin', checkInPatient);

module.exports = router;
