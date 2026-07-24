const jwt = require('jsonwebtoken');
const asyncHandler = require('express-async-handler');
const User = require('../models/User');
const { logAction } = require('../utils/logger');

const IDLE_TIMEOUT_MS = (Number(process.env.IDLE_TIMEOUT_MINUTES) || 10) * 60 * 1000;

// Verifies the JWT, loads the user, and enforces the idle-timeout security guard.
// If the user's last recorded activity is older than IDLE_TIMEOUT_MINUTES, the
// session is treated as expired even if the JWT itself has not expired yet.
const protect = asyncHandler(async (req, res, next) => {
  const header = req.headers.authorization;
  if (!header || !header.startsWith('Bearer ')) {
    res.status(401);
    throw new Error('Not authorized: no token provided');
  }

  const token = header.split(' ')[1];
  let decoded;
  try {
    decoded = jwt.verify(token, process.env.JWT_SECRET);
  } catch (err) {
    res.status(401);
    throw new Error('Not authorized: invalid or expired token');
  }

  const user = await User.findById(decoded.id);
  if (!user || !user.isActive) {
    res.status(401);
    throw new Error('Not authorized: account not found or deactivated');
  }

  const idleFor = Date.now() - new Date(user.lastActivityAt).getTime();
  if (idleFor > IDLE_TIMEOUT_MS) {
    await logAction(user._id, 'AUTO_LOGOUT_IDLE', { idleMinutes: Math.round(idleFor / 60000) }, req);
    res.status(401);
    throw new Error('Session expired due to inactivity. Please log in again.');
  }

  await user.touchActivity();
  req.user = user;
  next();
});

// Role-based access control: only the listed roles may proceed.
const authorize = (...roles) => (req, res, next) => {
  if (!req.user || !roles.includes(req.user.role)) {
    res.status(403);
    throw new Error(`Role '${req.user ? req.user.role : 'unknown'}' is not permitted to perform this action`);
  }
  next();
};

module.exports = { protect, authorize };
