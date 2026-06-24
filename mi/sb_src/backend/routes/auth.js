import express from 'express';
import bcrypt from 'bcrypt';
import jwt from 'jsonwebtoken';
import db from '../config/db.js';
import { verifyToken } from '../middleware/auth.js';
import { authRateLimiter } from '../middleware/rateLimit.js';

const router = express.Router();

// Login endpoint
router.post('/login', authRateLimiter, async (req, res) => {
    const { username, password } = req.body;

    if (!username || !password) {
        return res.status(400).json({ message: "Username dan password wajib diisi." });
    }

    try {
        const [rows] = await db.execute(
            'SELECT * FROM users WHERE username = ? AND is_active = 1',
            [username]
        );

        if (rows.length === 0) {
            return res.status(401).json({ message: "Username atau password salah." });
        }

        const user = rows[0];
        const isMatch = await bcrypt.compare(password, user.password_hash);

        if (!isMatch) {
            return res.status(401).json({ message: "Username atau password salah." });
        }

        // Update last login
        await db.execute('UPDATE users SET last_login = NOW() WHERE id = ?', [user.id]);

        const token = jwt.sign(
            { id: user.id, username: user.username, role: user.role },
            process.env.JWT_SECRET,
            { expiresIn: '8h' }
        );

        res.json({
            message: "Login berhasil",
            token,
            user: {
                id: user.id,
                username: user.username,
                role: user.role
            }
        });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: "Terjadi kesalahan pada server." });
    }
});

// Change Password endpoint
router.post('/change-password', verifyToken, async (req, res) => {
    const { oldPassword, newPassword } = req.body;
    const userId = req.user.id;

    if (!oldPassword || !newPassword) {
        return res.status(400).json({ message: "Password lama dan baru wajib diisi." });
    }

    try {
        const [rows] = await db.execute('SELECT password_hash FROM users WHERE id = ?', [userId]);
        
        if (rows.length === 0) {
            return res.status(404).json({ message: "User tidak ditemukan." });
        }

        const isMatch = await bcrypt.compare(oldPassword, rows[0].password_hash);
        if (!isMatch) {
            return res.status(401).json({ message: "Password lama tidak sesuai." });
        }

        const salt = await bcrypt.genSalt(12);
        const newPasswordHash = await bcrypt.hash(newPassword, salt);

        await db.execute('UPDATE users SET password_hash = ? WHERE id = ?', [newPasswordHash, userId]);

        res.json({ message: "Password berhasil diperbarui." });
    } catch (error) {
        console.error(error);
        res.status(500).json({ message: "Gagal memperbarui password." });
    }
});

export default router;
