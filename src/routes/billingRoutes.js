const express = require('express');
const {
  getInvoicePreview,
  createInvoiceAndPay,
  getInvoice,
} = require('../controllers/billingController');
const { protect, authorize } = require('../middleware/authMiddleware');

const router = express.Router();

router.use(protect, authorize('BillingAccountant', 'Admin'));

router.get('/encounters/:id/invoice-preview', getInvoicePreview);
router.post('/encounters/:id/invoice', createInvoiceAndPay);
router.get('/invoices/:id', getInvoice);

module.exports = router;
