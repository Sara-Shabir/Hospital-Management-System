const mongoose = require('mongoose');

const labTestOrderSchema = new mongoose.Schema(
  {
    encounter: { type: mongoose.Schema.Types.ObjectId, ref: 'Encounter', required: true },
    patient: { type: mongoose.Schema.Types.ObjectId, ref: 'Patient', required: true },
    orderedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User', required: true },
    testName: { type: String, required: true },
    priority: { type: String, enum: ['Routine', 'STAT'], default: 'Routine' },
    status: {
      type: String,
      enum: ['Pending', 'SampleCollected', 'ResultEntered', 'Published'],
      default: 'Pending',
    },
    sampleCollectedAt: Date,
    resultText: String,
    attachments: [{ fileName: String, url: String }],
    cost: { type: Number, default: 1000 },
    processedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    publishedAt: Date,
  },
  { timestamps: true }
);

module.exports = mongoose.model('LabTestOrder', labTestOrderSchema);
