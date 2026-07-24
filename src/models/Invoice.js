const mongoose = require('mongoose');

const lineItemSchema = new mongoose.Schema(
  {
    description: { type: String, required: true },
    sourceRole: { type: String, required: true }, // Receptionist, Nurse, Doctor, LabTechnician, Pharmacist
    amount: { type: Number, required: true },
  },
  { _id: false }
);

const invoiceSchema = new mongoose.Schema(
  {
    encounter: { type: mongoose.Schema.Types.ObjectId, ref: 'Encounter', required: true, unique: true },
    patient: { type: mongoose.Schema.Types.ObjectId, ref: 'Patient', required: true },
    lineItems: [lineItemSchema],
    subtotal: { type: Number, required: true, default: 0 },
    discount: { type: Number, default: 0 },
    insuranceCovered: { type: Number, default: 0 },
    totalAmount: { type: Number, required: true, default: 0 },
    paymentMethod: { type: String, enum: ['Cash', 'Card', 'InsuranceClaim'] },
    status: { type: String, enum: ['Pending', 'Paid'], default: 'Pending' },
    processedBy: { type: mongoose.Schema.Types.ObjectId, ref: 'User' },
    paidAt: Date,
  },
  { timestamps: true }
);

module.exports = mongoose.model('Invoice', invoiceSchema);
