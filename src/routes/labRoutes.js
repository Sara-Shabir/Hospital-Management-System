const express = require('express');
const {
  getWorklist,
  logSampleCollection,
  enterResults,
  publishResults,
} = require('../controllers/labController');
const { protect, authorize } = require('../middleware/authMiddleware');

const router = express.Router();

router.use(protect, authorize('LabTechnician', 'Admin'));

router.get('/worklist', getWorklist);
router.put('/orders/:id/collect', logSampleCollection);
router.put('/orders/:id/results', enterResults);
router.put('/orders/:id/publish', publishResults);

module.exports = router;
