const mongoose = require('mongoose');

const medicineLineSchema = new mongoose.Schema(
  {
    name: { type: String, required: true },
    dosage: { type: String, required: true },
    quantity: { type: Number, required: true },
    instructions: String,
  },
  { _id: false }
);

const prescriptionSchema = new mongoose.Schema(
  {
    encounter: { type: mongoose.Schema.Types.ObjectId, ref: 'Encounter', required: true },
    patient: { type: mongoose.Schema.Types.ObjectId, ref: 'Patient', required: true },
    doctor: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    medicines: [medicineLineSchema],
    status: { type: String, enum: ['Pending', 'Dispensed'], default: 'Pending' },
    dispensedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    dispensedAt: Date,
    totalCost: { type: Number, default: 0 },
  },
  { timestamps: true }
);

module.exports = mongoose.model('Prescription', prescriptionSchema);
