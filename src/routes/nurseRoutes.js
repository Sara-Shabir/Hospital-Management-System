const express = require('express');
const {
  getWaitingQueue,
  recordVitals,
  logMedicationAdministration,
} = require('../controllers/nurseController');
const { protect, authorize } = require('../middleware/authMiddleware');

const router = express.Router();

router.use(protect, authorize('Nurse', 'Admin'));

router.get('/queue', getWaitingQueue);
router.put('/encounters/:id/vitals', recordVitals);
router.put('/encounters/:id/medication-administration', logMedicationAdministration);

module.exports = router;
