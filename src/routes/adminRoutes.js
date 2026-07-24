const express = require('express');
const {
  createUser,
  listUsers,
  updateUser,
  deactivateUser,
  getLogs,
} = require('../controllers/adminController');
const { protect, authorize } = require('../middleware/authMiddleware');

const router = express.Router();

router.use(protect, authorize('Admin'));

router.route('/users').get(listUsers).post(createUser);
router.route('/users/:id').put(updateUser).delete(deactivateUser);
router.get('/logs', getLogs);

module.exports = router;
