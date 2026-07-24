const jwt = require('jsonwebtoken');

// Generates a signed session token (JWT) that tracks the user's identity and
// role, per the Generic Authentication Module's "Session Initialization" spec.
const generateToken = (user) =>
  jwt.sign({ id: user._id, role: user.role }, process.env.JWT_SECRET, {
    expiresIn: process.env.JWT_EXPIRES_IN || '15m',
  });

module.exports = generateToken;
