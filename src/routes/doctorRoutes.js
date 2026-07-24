const express = require('express');
const {
  getConsultationQueue,
  getPatientRecord,
  addClinicalNote,
  orderLabTest,
  prescribeMedication,
  closeConsultation,
} = require('../controllers/doctorController');
const { protect, authorize } = require('../middleware/authMiddleware');

const router = express.Router();

router.use(protect, authorize('Doctor', 'Admin'));

router.get('/queue', getConsultationQueue);
router.get('/encounters/:id', getPatientRecord);
router.post('/encounters/:id/notes', addClinicalNote);
router.post('/encounters/:id/lab-orders', orderLabTest);
router.post('/encounters/:id/prescriptions', prescribeMedication);
router.put('/encounters/:id/close', closeConsultation);

module.exports = router;
