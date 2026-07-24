const express = require('express');
const {
  getPrescriptionQueue,
  dispenseMedication,
  addInventoryBatch,
  getInventory,
  updateInventoryItem,
} = require('../controllers/pharmacistController');
const { protect, authorize } = require('../middleware/authMiddleware');

const router = express.Router();

router.use(protect, authorize('Pharmacist', 'Admin'));

router.get('/prescriptions', getPrescriptionQueue);
router.put('/prescriptions/:id/dispense', dispenseMedication);
router.route('/inventory').get(getInventory).post(addInventoryBatch);
router.put('/inventory/:id', updateInventoryItem);

module.exports = router;
