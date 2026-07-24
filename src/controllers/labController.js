const asyncHandler = require('express-async-handler');
const LabTestOrder = require('../models/LabTestOrder');
const Encounter = require('../models/Encounter');
const { logAction } = require('../utils/logger');

// @desc  View incoming diagnostic test requests, sorted by urgency (STAT first).
// @route GET /api/lab/worklist
const getWorklist = asyncHandler(async (req, res) => {
  const orders = await LabTestOrder.find({ status: { $ne: 'Published' } })
    .populate({ path: 'patient', select: 'name age gender' })
    .populate({ path: 'orderedBy', select: 'name' })
    .sort({ priority: -1, createdAt: 1 }); // 'STAT' > 'Routine' alphabetically, so STAT surfaces first
  res.json(orders);
});

// @desc  Log sample collection details.
// @route PUT /api/lab/orders/:id/collect
const logSampleCollection = asyncHandler(async (req, res) => {
  const order = await LabTestOrder.findById(req.params.id);
  if (!order) {
    res.status(404);
    throw new Error('Lab order not found');
  }
  order.status = 'SampleCollected';
  order.sampleCollectedAt = new Date();
  order.processedBy = req.user._id;
  await order.save();
  res.json(order);
});

// @desc  Enter quantitative/qualitative results and upload digital attachments.
// @route PUT /api/lab/orders/:id/results
const enterResults = asyncHandler(async (req, res) => {
  const { resultText, attachments } = req.body;
  const order = await LabTestOrder.findById(req.params.id);
  if (!order) {
    res.status(404);
    throw new Error('Lab order not found');
  }
  order.resultText = resultText;
  if (attachments) order.attachments = attachments;
  order.status = 'ResultEntered';
  await order.save();
  res.json(order);
});

// @desc  Publish results: locks the record, notifies the ordering Doctor's
//        dashboard, and updates the Patient's portal. Returns encounter to Doctor.
// @route PUT /api/lab/orders/:id/publish
const publishResults = asyncHandler(async (req, res) => {
  const order = await LabTestOrder.findById(req.params.id);
  if (!order) {
    res.status(404);
    throw new Error('Lab order not found');
  }
  order.status = 'Published';
  order.publishedAt = new Date();
  await order.save();

  const encounter = await Encounter.findById(order.encounter);
  if (encounter) {
    encounter.status = 'WaitingForDoctor';
    await encounter.save();
  }

  await logAction(req.user._id, 'PUBLISH_LAB_RESULT', { order: order._id }, req);
  res.json(order);
});

module.exports = { getWorklist, logSampleCollection, enterResults, publishResults };
