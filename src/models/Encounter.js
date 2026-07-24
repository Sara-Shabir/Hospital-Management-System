const mongoose = require('mongoose');

const vitalsSchema = new mongoose.Schema(
  {
    bloodPressure: String,
    pulse: Number,
    temperature: Number,
    respiratoryRate: Number,
    weight: Number,
    isHighRisk: { type: Boolean, default: false },
    recordedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    recordedAt: Date,
  },
  { _id: false }
);

const clinicalNoteSchema = new mongoose.Schema(
  {
    notes: { type: String, required: true },
    writtenBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    createdAt: { type: Date, default: Date.now },
  },
  { _id: false }
);

const medicationAdministrationSchema = new mongoose.Schema(
  {
    medicine: String,
    scheduledTime: Date,
    administered: { type: Boolean, default: false },
    administeredBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    administeredAt: Date,
  },
  { _id: false }
);

const encounterSchema = new mongoose.Schema(
  {
    patient: { type: mongoose.Schema.Types.ObjectId, ref: 'Patient', required: true },
    tokenNumber: { type: String, required: true, unique: true },
    status: {
      type: String,
      enum: [
        'WaitingForNurse',
        'WaitingForDoctor',
        'InConsultation',
        'AwaitingLab',
        'AwaitingPharmacy',
        'AwaitingBilling',
        'Discharged',
      ],
      default: 'WaitingForNurse',
    },
    checkedInBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    registrationFee: { type: Number, default: 500 },

    vitals: vitalsSchema,
    triageFee: { type: Number },

    assignedDoctor: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    clinicalNotes: [clinicalNoteSchema],
    consultationFee: { type: Number },
    medicationAdministrations: [medicationAdministrationSchema],

    dischargedAt: Date,
  },
  { timestamps: true }
);

module.exports = mongoose.model('Encounter', encounterSchema);
