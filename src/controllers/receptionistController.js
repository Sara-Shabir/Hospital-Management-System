const asyncHandler = require('express-async-handler');
const Patient = require('../models/Patient');
const Appointment = require('../models/Appointment');
const Encounter = require('../models/Encounter');
const User = require('../models/User');
const { logAction } = require('../utils/logger');

// @desc  Search for an existing patient by CNIC, phone, or Patient ID.
// @route GET /api/receptionist/patients/search?q=
const searchPatients = asyncHandler(async (req, res) => {
  const { q } = req.query;
  if (!q) {
    res.status(400);
    throw new Error('Query parameter "q" is required');
  }

  const isObjectId = /^[a-f\d]{24}$/i.test(q);
  const patients = await Patient.find({
    $or: [
      ...(isObjectId ? [{ _id: q }] : []),
      { cnic: q },
      { phone: q },
      { name: { $regex: q, $options: 'i' } },
    ],
  });
  res.json(patients);
});

// @desc  Register a new patient profile on first visit.
// @route POST /api/receptionist/patients
const registerPatient = asyncHandler(async (req, res) => {
  const { name, age, gender, cnic, phone, emergencyContact } = req.body;
  const patient = await Patient.create({
    name,
    age,
    gender,
    cnic,
    phone,
    emergencyContact,
    registeredBy: req.user._id,
  });
  await logAction(req.user._id, 'REGISTER_PATIENT', { patient: patient._id }, req);
  res.status(201).json(patient);
});

// @desc  View doctor availability (booked appointment slots) for scheduling.
// @route GET /api/receptionist/doctors/:doctorId/availability
const getDoctorAvailability = asyncHandler(async (req, res) => {
  const doctor = await User.findOne({ _id: req.params.doctorId, role: 'Doctor' });
  if (!doctor) {
    res.status(404);
    throw new Error('Doctor not found');
  }
  const appointments = await Appointment.find({
    doctor: doctor._id,
    status: { $in: ['Booked', 'Rescheduled'] },
  }).sort({ scheduledAt: 1 });
  res.json(appointments);
});

// @desc  Book, reschedule, or cancel a consultation slot.
// @route POST /api/receptionist/appointments
const bookAppointment = asyncHandler(async (req, res) => {
  const { patientId, doctorId, scheduledAt } = req.body;
  const appointment = await Appointment.create({
    patient: patientId,
    doctor: doctorId,
    scheduledAt,
    bookedBy: req.user._id,
  });
  await logAction(req.user._id, 'BOOK_APPOINTMENT', { appointment: appointment._id }, req);
  res.status(201).json(appointment);
});

// @route PUT /api/receptionist/appointments/:id
const updateAppointment = asyncHandler(async (req, res) => {
  const { status, scheduledAt } = req.body;
  const appointment = await Appointment.findById(req.params.id);
  if (!appointment) {
    res.status(404);
    throw new Error('Appointment not found');
  }
  if (status) appointment.status = status;
  if (scheduledAt) appointment.scheduledAt = scheduledAt;
  await appointment.save();
  res.json(appointment);
});

// @desc  Check in a patient on arrival: generates an electronic token and
//        immediately inserts the patient into the Nurse's waiting queue.
// @route POST /api/receptionist/checkin
const checkInPatient = asyncHandler(async (req, res) => {
  const { patientId } = req.body;
  const patient = await Patient.findById(patientId);
  if (!patient) {
    res.status(404);
    throw new Error('Patient not found');
  }

  const tokenNumber = `T-${Date.now().toString().slice(-6)}`;
  const encounter = await Encounter.create({
    patient: patient._id,
    tokenNumber,
    checkedInBy: req.user._id,
    status: 'WaitingForNurse',
  });

  await logAction(req.user._id, 'CHECK_IN', { encounter: encounter._id, tokenNumber }, req);
  res.status(201).json(encounter);
});

module.exports = {
  searchPatients,
  registerPatient,
  getDoctorAvailability,
  bookAppointment,
  updateAppointment,
  checkInPatient,
};
