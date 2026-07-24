const asyncHandler = require('express-async-handler');
const Prescription = require('../models/Prescription');
const InventoryItem = require('../models/InventoryItem');
const Encounter = require('../models/Encounter');
const { logAction } = require('../utils/logger');

// @desc  View active queue of electronic prescriptions, searchable by patient.
// @route GET /api/pharmacist/prescriptions?patientId=&token=
const getPrescriptionQueue = asyncHandler(async (req, res) => {
  const filter = { status: 'Pending' };
  if (req.query.patientId) filter.patient = req.query.patientId;

  const prescriptions = await Prescription.find(filter)
    .populate('patient', 'name age gender')
    .populate('doctor', 'name')
    .sort({ createdAt: 1 });
  res.json(prescriptions);
});

// @desc  Dispense medication: subtracts quantity from stock ledger and sends
//        cost items to the Billing Accountant's dashboard.
// @route PUT /api/pharmacist/prescriptions/:id/dispense
const dispenseMedication = asyncHandler(async (req, res) => {
  const prescription = await Prescription.findById(req.params.id);
  if (!prescription) {
    res.status(404);
    throw new Error('Prescription not found');
  }
  if (prescription.status === 'Dispensed') {
    res.status(400);
    throw new Error('Prescription already dispensed');
  }

  let totalCost = 0;
  for (const line of prescription.medicines) {
    const stockItem = await InventoryItem.findOne({ name: line.name });
    if (!stockItem || stockItem.quantity < line.quantity) {
      res.status(400);
      throw new Error(`Insufficient stock for ${line.name}`);
    }
    stockItem.quantity -= line.quantity;
    await stockItem.save();
    totalCost += stockItem.unitPrice * line.quantity;
  }

  prescription.status = 'Dispensed';
  prescription.dispensedBy = req.user._id;
  prescription.dispensedAt = new Date();
  prescription.totalCost = totalCost;
  await prescription.save();

  const encounter = await Encounter.findById(prescription.encounter);
  if (encounter) {
    encounter.status = 'AwaitingBilling';
    await encounter.save();
  }

  await logAction(req.user._id, 'DISPENSE_MEDICATION', { prescription: prescription._id, totalCost }, req);
  res.json(prescription);
});

// @desc  Add a new batch of medicine to inventory.
// @route POST /api/pharmacist/inventory
const addInventoryBatch = asyncHandler(async (req, res) => {
  const item = await InventoryItem.create(req.body);
  res.status(201).json(item);
});

// @desc  View inventory, including low-stock items.
// @route GET /api/pharmacist/inventory
const getInventory = asyncHandler(async (req, res) => {
  const items = await InventoryItem.find().sort({ name: 1 });
  const lowStock = items.filter((i) => i.quantity <= i.lowStockThreshold);
  res.json({ items, lowStockAlerts: lowStock });
});

// @route PUT /api/pharmacist/inventory/:id
const updateInventoryItem = asyncHandler(async (req, res) => {
  const item = await InventoryItem.findByIdAndUpdate(req.params.id, req.body, {
    new: true,
    runValidators: true,
  });
  if (!item) {
    res.status(404);
    throw new Error('Inventory item not found');
  }
  res.json(item);
});

module.exports = {
  getPrescriptionQueue,
  dispenseMedication,
  addInventoryBatch,
  getInventory,
  updateInventoryItem,
};
