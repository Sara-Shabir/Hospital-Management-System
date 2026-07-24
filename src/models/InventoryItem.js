const mongoose = require('mongoose');

const inventoryItemSchema = new mongoose.Schema(
  {
    name: { type: String, required: true, trim: true },
    batchNumber: { type: String, required: true },
    quantity: { type: Number, required: true, default: 0 },
    unitPrice: { type: Number, required: true },
    manufactureDate: Date,
    expiryDate: Date,
    lowStockThreshold: { type: Number, default: 20 },
  },
  { timestamps: true }
);

inventoryItemSchema.virtual('isLowStock').get(function isLowStock() {
  return this.quantity <= this.lowStockThreshold;
});

inventoryItemSchema.set('toJSON', { virtuals: true });

module.exports = mongoose.model('InventoryItem', inventoryItemSchema);
