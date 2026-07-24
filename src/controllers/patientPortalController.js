const asyncHandler = require('express-async-handler');
const Patient = require('../models/Patient');
const LabTestOrder = require('../models/LabTestOrder');
const Prescription = require('../models/Prescription');
const Invoice = require('../models/Invoice');
const Appointment = require('../models/Appointment');

// Resolves the Patient record linked to the logged-in portal user.
// All handlers below are strictly read-only, per the Patient role's spec.
const resolveLinkedPatient = async (req) => {
  const patient = await Patient.findOne({ userAccount: req.user._id });
  if (!patient) {
    const err = new Error('No patient record is linked to this account');
    err.statusCode = 404;
    throw err;
  }
  return patient;
};

// @desc  View/download digital lab and radiology reports the moment they're published.
// @route GET /api/patient-portal/lab-reports
const getMyLabReports = asyncHandler(async (req, res) => {
  const patient = await resolveLinkedPatient(req);
  const reports = await LabTestOrder.find({ patient: patient._id, status: 'Published' }).sort({
    publishedAt: -1,
  });
  res.json(reports);
});

// @desc  View active and past electronic prescriptions (read-only).
// @route GET /api/patient-portal/prescriptions
const getMyPrescriptions = asyncHandler(async (req, res) => {
  const patient = await resolveLinkedPatient(req);
  const prescriptions = await Prescription.find({ patient: patient._id }).sort({ createdAt: -1 });
  res.json(prescriptions);
});

// @desc  View past billing receipts and current outstanding balances.
// @route GET /api/patient-portal/billing
const getMyBilling = asyncHandler(async (req, res) => {
  const patient = await resolveLinkedPatient(req);
  const invoices = await Invoice.find({ patient: patient._id }).sort({ createdAt: -1 });
  res.json(invoices);
});

// @desc  Request/book a future appointment online, linking into the
//        Receptionist's master scheduling calendar.
// @route POST /api/patient-portal/appointments
const bookMyAppointment = asyncHandler(async (req, res) => {
  const patient = await resolveLinkedPatient(req);
  const { doctorId, scheduledAt } = req.body;
  const appointment = await Appointment.create({
    patient: patient._id,
    doctor: doctorId,
    scheduledAt,
    bookedBy: req.user._id,
    bookedViaPortal: true,
  });
  res.status(201).json(appointment);
});

// @route GET /api/patient-portal/appointments
const getMyAppointments = asyncHandler(async (req, res) => {
  const patient = await resolveLinkedPatient(req);
  const appointments = await Appointment.find({ patient: patient._id }).sort({ scheduledAt: -1 });
  res.json(appointments);
});

module.exports = {
  getMyLabReports,
  getMyPrescriptions,
  getMyBilling,
  bookMyAppointment,
  getMyAppointments,
};
