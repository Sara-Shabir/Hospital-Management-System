const asyncHandler = require('express-async-handler');
const Encounter = require('../models/Encounter');
const LabTestOrder = require('../models/LabTestOrder');
const Prescription = require('../models/Prescription');
const Patient = require('../models/Patient');
const { logAction } = require('../utils/logger');

// @desc  View the live, dynamically updating queue of patients who have
//        completed triage with the Nurse.
// @route GET /api/doctor/queue
const getConsultationQueue = asyncHandler(async (req, res) => {
  const queue = await Encounter.find({ status: 'WaitingForDoctor' })
    .populate('patient')
    .sort({ 'vitals.isHighRisk': -1, createdAt: 1 });
  res.json(queue);
});

// @desc  Open the complete historical Patient Record (demographics, past
//        diagnoses, allergies, medication history) plus fresh Nurse vitals.
// @route GET /api/doctor/encounters/:id
const getPatientRecord = asyncHandler(async (req, res) => {
  const encounter = await Encounter.findById(req.params.id).populate('patient');
  if (!encounter) {
    res.status(404);
    throw new Error('Encounter not found');
  }

  const [labHistory, prescriptionHistory] = await Promise.all([
    LabTestOrder.find({ patient: encounter.patient._id }).sort({ createdAt: -1 }),
    Prescription.find({ patient: encounter.patient._id }).sort({ createdAt: -1 }),
  ]);

  encounter.status = 'InConsultation';
  encounter.assignedDoctor = req.user._id;
  await encounter.save();

  res.json({ encounter, labHistory, prescriptionHistory });
});

// @desc  Write and save digital clinical case notes.
// @route POST /api/doctor/encounters/:id/notes
const addClinicalNote = asyncHandler(async (req, res) => {
  const encounter = await Encounter.findById(req.params.id);
  if (!encounter) {
    res.status(404);
    throw new Error('Encounter not found');
  }
  encounter.clinicalNotes.push({ notes: req.body.notes, writtenBy: req.user._id });
  encounter.consultationFee = encounter.consultationFee || 1500;
  await encounter.save();
  res.status(201).json(encounter);
});

// @desc  Order a lab test/radiology imaging; instantly appears on the Lab
//        Technician's worklist.
// @route POST /api/doctor/encounters/:id/lab-orders
const orderLabTest = asyncHandler(async (req, res) => {
  const encounter = await Encounter.findById(req.params.id);
  if (!encounter) {
    res.status(404);
    throw new Error('Encounter not found');
  }

  const order = await LabTestOrder.create({
    encounter: encounter._id,
    patient: encounter.patient,
    orderedBy: req.user._id,
    testName: req.body.testName,
    priority: req.body.priority || 'Routine',
  });

  encounter.status = 'AwaitingLab';
  await encounter.save();

  await logAction(req.user._id, 'ORDER_LAB_TEST', { order: order._id }, req);
  res.status(201).json(order);
});

// @desc  Search the drug database and prescribe medication. Alerts if a drug
//        conflicts with the patient's logged allergies.
// @route POST /api/doctor/encounters/:id/prescriptions
const prescribeMedication = asyncHandler(async (req, res) => {
  const encounter = await Encounter.findById(req.params.id).populate('patient');
  if (!encounter) {
    res.status(404);
    throw new Error('Encounter not found');
  }

  const { medicines } = req.body; // [{ name, dosage, quantity, instructions }]
  const allergyConflicts = medicines.filter((m) =>
    (encounter.patient.allergies || []).some((a) => a.toLowerCase() === m.name.toLowerCase())
  );

  const prescription = await Prescription.create({
    encounter: encounter._id,
    patient: encounter.patient._id,
    doctor: req.user._id,
    medicines,
  });

  encounter.status = 'AwaitingPharmacy';
  await encounter.save();

  await logAction(req.user._id, 'PRESCRIBE_MEDICATION', { prescription: prescription._id }, req);

  res.status(201).json({
    prescription,
    allergyWarning: allergyConflicts.length
      ? `Warning: patient is allergic to ${allergyConflicts.map((m) => m.name).join(', ')}`
      : null,
  });
});

// @desc  Close the consultation session: auto-locks clinical notes and sends
//        the encounter forward to Pharmacist / Billing Accountant.
// @route PUT /api/doctor/encounters/:id/close
const closeConsultation = asyncHandler(async (req, res) => {
  const encounter = await Encounter.findById(req.params.id);
  if (!encounter) {
    res.status(404);
    throw new Error('Encounter not found');
  }

  const hasOpenPrescription = await Prescription.exists({ encounter: encounter._id, status: 'Pending' });
  encounter.status = hasOpenPrescription ? 'AwaitingPharmacy' : 'AwaitingBilling';
  await encounter.save();

  await logAction(req.user._id, 'CLOSE_CONSULTATION', { encounter: encounter._id }, req);
  res.json(encounter);
});

module.exports = {
  getConsultationQueue,
  getPatientRecord,
  addClinicalNote,
  orderLabTest,
  prescribeMedication,
  closeConsultation,
};
