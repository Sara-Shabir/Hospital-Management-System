const asyncHandler = require('express-async-handler');
const Encounter = require('../models/Encounter');
const LabTestOrder = require('../models/LabTestOrder');
const Prescription = require('../models/Prescription');
const Invoice = require('../models/Invoice');
const { logAction } = require('../utils/logger');

// @desc  Open a unified invoice screen: pulls a compiled, itemized list of
//        charges from every previous role (Receptionist, Nurse, Doctor,
//        Lab Tech, Pharmacist) for the given encounter.
// @route GET /api/billing/encounters/:id/invoice-preview
const getInvoicePreview = asyncHandler(async (req, res) => {
  const encounter = await Encounter.findById(req.params.id).populate('patient');
  if (!encounter) {
    res.status(404);
    throw new Error('Encounter not found');
  }

  const [labOrders, prescriptions] = await Promise.all([
    LabTestOrder.find({ encounter: encounter._id, status: 'Published' }),
    Prescription.find({ encounter: encounter._id, status: 'Dispensed' }),
  ]);

  const lineItems = [
    { description: 'Registration / Token Fee', sourceRole: 'Receptionist', amount: encounter.registrationFee || 0 },
    { description: 'Triage / Consumables Fee', sourceRole: 'Nurse', amount: encounter.triageFee || 0 },
    { description: 'Consultation Fee', sourceRole: 'Doctor', amount: encounter.consultationFee || 0 },
    ...labOrders.map((o) => ({
      description: `Lab Test: ${o.testName}`,
      sourceRole: 'LabTechnician',
      amount: o.cost || 0,
    })),
    ...prescriptions.map((p) => ({
      description: 'Dispensed Medication',
      sourceRole: 'Pharmacist',
      amount: p.totalCost || 0,
    })),
  ].filter((li) => li.amount > 0);

  const subtotal = lineItems.reduce((sum, li) => sum + li.amount, 0);

  res.json({ encounter, lineItems, subtotal });
});

// @desc  Apply insurance/discount and process the unified invoice/payment.
// @route POST /api/billing/encounters/:id/invoice
const createInvoiceAndPay = asyncHandler(async (req, res) => {
  const { discount = 0, insuranceCovered = 0, paymentMethod } = req.body;
  const encounter = await Encounter.findById(req.params.id);
  if (!encounter) {
    res.status(404);
    throw new Error('Encounter not found');
  }

  const [labOrders, prescriptions] = await Promise.all([
    LabTestOrder.find({ encounter: encounter._id, status: 'Published' }),
    Prescription.find({ encounter: encounter._id, status: 'Dispensed' }),
  ]);

  const lineItems = [
    { description: 'Registration / Token Fee', sourceRole: 'Receptionist', amount: encounter.registrationFee || 0 },
    { description: 'Triage / Consumables Fee', sourceRole: 'Nurse', amount: encounter.triageFee || 0 },
    { description: 'Consultation Fee', sourceRole: 'Doctor', amount: encounter.consultationFee || 0 },
    ...labOrders.map((o) => ({ description: `Lab Test: ${o.testName}`, sourceRole: 'LabTechnician', amount: o.cost || 0 })),
    ...prescriptions.map((p) => ({ description: 'Dispensed Medication', sourceRole: 'Pharmacist', amount: p.totalCost || 0 })),
  ].filter((li) => li.amount > 0);

  const subtotal = lineItems.reduce((sum, li) => sum + li.amount, 0);
  const totalAmount = Math.max(subtotal - discount - insuranceCovered, 0);

  const invoice = await Invoice.create({
    encounter: encounter._id,
    patient: encounter.patient,
    lineItems,
    subtotal,
    discount,
    insuranceCovered,
    totalAmount,
    paymentMethod,
    status: 'Paid',
    processedBy: req.user._id,
    paidAt: new Date(),
  });

  encounter.status = 'Discharged';
  encounter.dischargedAt = new Date();
  await encounter.save();

  await logAction(req.user._id, 'PROCESS_PAYMENT_DISCHARGE', { invoice: invoice._id, totalAmount }, req);
  res.status(201).json(invoice);
});

// @route GET /api/billing/invoices/:id
const getInvoice = asyncHandler(async (req, res) => {
  const invoice = await Invoice.findById(req.params.id).populate('patient').populate('encounter');
  if (!invoice) {
    res.status(404);
    throw new Error('Invoice not found');
  }
  res.json(invoice);
});

module.exports = { getInvoicePreview, createInvoiceAndPay, getInvoice };
