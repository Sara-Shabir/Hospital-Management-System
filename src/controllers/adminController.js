const asyncHandler = require('express-async-handler');
const User = require('../models/User');
const SystemLog = require('../models/SystemLog');
const { logAction } = require('../utils/logger');

// @desc  Create a staff user account (e.g., a newly hired doctor).
// @route POST /api/admin/users
const createUser = asyncHandler(async (req, res) => {
  const { name, email, password, role } = req.body;
  const exists = await User.findOne({ email: email.toLowerCase() });
  if (exists) {
    res.status(400);
    throw new Error('A user with this email already exists');
  }

  const user = await User.create({ name, email, password, role });
  await logAction(req.user._id, 'ADMIN_CREATE_USER', { targetUser: user._id, role }, req);

  res.status(201).json({ id: user._id, name: user.name, email: user.email, role: user.role });
});

// @desc  List all staff/user accounts.
// @route GET /api/admin/users
const listUsers = asyncHandler(async (req, res) => {
  const users = await User.find().select('-password');
  res.json(users);
});

// @desc  Modify a user account (role assignment / RBAC, activation state).
// @route PUT /api/admin/users/:id
const updateUser = asyncHandler(async (req, res) => {
  const { name, role, isActive } = req.body;
  const user = await User.findById(req.params.id);
  if (!user) {
    res.status(404);
    throw new Error('User not found');
  }

  if (name !== undefined) user.name = name;
  if (role !== undefined) user.role = role;
  if (isActive !== undefined) user.isActive = isActive;
  await user.save();

  await logAction(req.user._id, 'ADMIN_UPDATE_USER', { targetUser: user._id, role, isActive }, req);
  res.json({ id: user._id, name: user.name, email: user.email, role: user.role, isActive: user.isActive });
});

// @desc  Deactivate (soft-delete) a user account.
// @route DELETE /api/admin/users/:id
const deactivateUser = asyncHandler(async (req, res) => {
  const user = await User.findById(req.params.id);
  if (!user) {
    res.status(404);
    throw new Error('User not found');
  }
  user.isActive = false;
  await user.save();
  await logAction(req.user._id, 'ADMIN_DEACTIVATE_USER', { targetUser: user._id }, req);
  res.json({ message: 'User deactivated' });
});

// @desc  Monitor system logs (login/logout, idle auto-logout, admin actions).
// @route GET /api/admin/logs
const getLogs = asyncHandler(async (req, res) => {
  const logs = await SystemLog.find()
    .sort({ timestamp: -1 })
    .limit(200)
    .populate('user', 'name email role');
  res.json(logs);
});

module.exports = { createUser, listUsers, updateUser, deactivateUser, getLogs };
