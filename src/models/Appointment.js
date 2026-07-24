const mongoose = require('mongoose');

const appointmentSchema = new mongoose.Schema(
  {
    patient: { type: mongoose.Schema.Types.ObjectId, ref: 'Patient', required: true },
    doctor: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    scheduledAt: { type: Date, required: true },
    status: {
      type: String,
      enum: ['Booked', 'Rescheduled', 'Cancelled', 'CheckedIn', 'Completed'],
      default: 'Booked',
    },
    bookedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    // true when booked by the patient themselves through the portal
    bookedViaPortal: { type: Boolean, default: false },
  },
  { timestamps: true }
);

module.exports = mongoose.model('Appointment', appointmentSchema);
