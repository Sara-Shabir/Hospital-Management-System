const mongoose = require('mongoose');

const patientSchema = new mongoose.Schema(
  {
    name: { type: String, required: true, trim: true },
    age: { type: Number, required: true },
    gender: { type: String, enum: ['Male', 'Female', 'Other'], required: true },
    cnic: { type: String, trim: true, index: true },
    phone: { type: String, trim: true, index: true },
    emergencyContact: { type: String, trim: true },
    allergies: [{ type: String }],
    chronicConditions: [{ type: String }],
    // Linked login account, only present if the patient uses the Patient Portal
    userAccount: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    registeredBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
  },
  { timestamps: true }
);

patientSchema.index({ name: 'text' });

module.exports = mongoose.model('Patient', patientSchema);
