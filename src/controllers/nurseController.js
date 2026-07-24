const asyncHandler = require('express-async-handler');
const Encounter = require('../models/Encounter');
const { logAction } = require('../utils/logger');

// High-risk thresholds used to auto-flag vitals for the doctor's attention.
const isHighRisk = ({ bloodPressure, pulse, temperature }) => {
  const systolic = bloodPressure ? parseInt(bloodPressure.split('/')[0], 10) : 0;
  return systolic >= 180 || pulse > 130 || temperature > 39.5;
};

// @desc  View the active waiting queue sent over by the Receptionist.
// @route GET /api/nurse/queue
const getWaitingQueue = asyncHandler(async (req, res) => {
  const queue = await Encounter.find({ status: 'WaitingForNurse' })
    .populate('patient')
    .sort({ createdAt: 1 });
  res.json(queue);
});

// @desc  Record vital signs for a selected patient. Saving vitals removes the
//        patient from the Nurse's queue and pushes them to the Doctor's queue.
//        High-risk vitals are flagged so they highlight red on the doctor's screen.
// @route PUT /api/nurse/encounters/:id/vitals
const recordVitals = asyncHandler(async (req, res) => {
  const encounter = await Encounter.findById(req.params.id);
  if (!encounter) {
    res.status(404);
    throw new Error('Encounter not found');
  }
  if (encounter.status !== 'WaitingForNurse') {
    res.status(400);
    throw new Error('This encounter is not awaiting triage');
  }

  const { bloodPressure, pulse, temperature, respiratoryRate, weight } = req.body;
  const vitals = {
    bloodPressure,
    pulse,
    temperature,
    respiratoryRate,
    weight,
    isHighRisk: isHighRisk({ bloodPressure, pulse, temperature }),
    recordedBy: req.user._id,
    recordedAt: new Date(),
  };

  encounter.vitals = vitals;
  encounter.triageFee = 300;
  encounter.status = 'WaitingForDoctor';
  await encounter.save();

  await logAction(req.user._id, 'RECORD_VITALS', { encounter: encounter._id, highRisk: vitals.isHighRisk }, req);
  res.json(encounter);
});

// @desc  View/log medicine administration schedules for admitted patients,
//        ticking off doses as "Administered" based on doctor orders.
// @route PUT /api/nurse/encounters/:id/medication-administration
const logMedicationAdministration = asyncHandler(async (req, res) => {
  const { medicine, scheduledTime } = req.body;
  const encounter = await Encounter.findById(req.params.id);
  if (!encounter) {
    res.status(404);
    throw new Error('Encounter not found');
  }

  encounter.medicationAdministrations.push({
    medicine,
    scheduledTime,
    administered: true,
    administeredBy: req.user._id,
    administeredAt: new Date(),
  });
  await encounter.save();

  await logAction(req.user._id, 'LOG_MEDICATION_ADMIN', { encounter: encounter._id, medicine }, req);
  res.json(encounter);
});

module.exports = { getWaitingQueue, recordVitals, logMedicationAdministration };
