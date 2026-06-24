import express from 'express';
import bcrypt from 'bcrypt';
import db from '../config/db.js';
import { verifyToken } from '../middleware/auth.js';

const router = express.Router();

// Middleware to check if user is superadmin
const isSuperAdmin = (req, res, next) => {
    if (req.user.role !== 'superadmin') {
        return res.status(403).json({ message: "Akses ditolak. Perlu role Superadmin." });
    }
    next();
};

// Get all users
router.get('/', verifyToken, isSuperAdmin, async (req, res) => {
    try {
        const [rows] = await db.execute('SELECT id, username, role, is_active, created_at, last_login FROM users');
        res.json(rows);
    } catch (error) {
        res.status(500).json({ message: "Gagal mengambil data user." });
    }
});

// Create new user
router.post('/', verifyToken, isSuperAdmin, async (req, res) => {
    const { username, password, role } = req.body;
    if (!username || !password || !role) {
        return res.status(400).json({ message: "Data tidak lengkap." });
    }

    try {
        const salt = await bcrypt.genSalt(12);
        const hash = await bcrypt.hash(password, salt);
        await db.execute(
            'INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)',
            [username, hash, role]
        );
        res.json({ message: "User berhasil dibuat." });
    } catch (error) {
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(400).json({ message: "Username sudah digunakan." });
        }
        res.status(500).json({ message: "Gagal membuat user." });
    }
});

// Toggle user status
router.patch('/:id/toggle', verifyToken, isSuperAdmin, async (req, res) => {
    try {
        await db.execute(
            'UPDATE users SET is_active = NOT is_active WHERE id = ?',
            [req.params.id]
        );
        res.json({ message: "Status user diperbarui." });
    } catch (error) {
        res.status(500).json({ message: "Gagal memperbarui status user." });
    }
});

export default router;
