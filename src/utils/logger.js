const SystemLog = require('../models/SystemLog');

// Records an auditable system event. Used for auth events (login, logout,
// idle auto-logout) and for the System Administrator's log-monitoring feature.
const logAction = async (userId, action, details = {}, req = null) => {
  try {
    await SystemLog.create({
      user: userId,
      action,
      details,
      ip: req ? req.ip : undefined,
    });
  } catch (err) {
    console.error(`Failed to write system log for action "${action}": ${err.message}`);
  }
};

module.exports = { logAction };
