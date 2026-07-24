const asyncHandler = require('express-async-handler');
const User = require('../models/User');
const generateToken = require('../utils/generateToken');
const { logAction } = require('../utils/logger');

// @desc  Log in with email/username + password. Cross-references credentials,
//        checks the user's role, and returns a session token (JWT) that the
//        client uses to dynamically redirect to the correct dashboard.
// @route POST /api/auth/login
// @access Public
const login = asyncHandler(async (req, res) => {
  const { email, password } = req.body;
  if (!email || !password) {
    res.status(400);
    throw new Error('Email and password are required');
  }

  const user = await User.findOne({ email: email.toLowerCase() }).select('+password');
  if (!user || !user.isActive || !(await user.comparePassword(password))) {
    res.status(401);
    throw new Error('Invalid credentials');
  }

  await user.touchActivity();
  await logAction(user._id, 'LOGIN', { role: user.role }, req);

  res.json({
    token: generateToken(user),
    user: {
      id: user._id,
      name: user.name,
      email: user.email,
      role: user.role, // client uses this for dynamic dashboard redirection
    },
  });
});

// @desc  Log out. Destroys the active session by instructing the client to
//        discard its token; the corresponding event is logged for auditing.
// @route POST /api/auth/logout
// @access Private
const logout = asyncHandler(async (req, res) => {
  await logAction(req.user._id, 'LOGOUT', {}, req);
  res.json({ message: 'Logged out successfully. Session destroyed.' });
});

// @desc  Get the currently authenticated user's profile.
// @route GET /api/auth/me
// @access Private
const getMe = asyncHandler(async (req, res) => {
  res.json({
    id: req.user._id,
    name: req.user.name,
    email: req.user.email,
    role: req.user.role,
  });
});

module.exports = { login, logout, getMe };
