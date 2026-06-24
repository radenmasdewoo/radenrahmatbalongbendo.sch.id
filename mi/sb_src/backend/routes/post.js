import express from 'express';
import multer from 'multer';
import path from 'path';
import { verifyToken } from '../middleware/auth.js';
import db from '../config/db.js';
import { postToFacebook, postToInstagram, postToWhatsApp } from '../services/metaApi.js';

const router = express.Router();

// Multer setup for media uploads
const storage = multer.diskStorage({
    destination: (req, file, cb) => {
        cb(null, 'uploads/');
    },
    filename: (req, file, cb) => {
        cb(null, Date.now() + path.extname(file.originalname));
    }
});

const upload = multer({ 
    storage,
    limits: { fileSize: 50 * 1024 * 1024 } // 50MB limit
});

router.post('/story', verifyToken, upload.single('media'), async (req, res) => {
    const { caption, platforms } = req.body; // platforms should be JSON string or array
    const userId = req.user.id;
    const mediaPath = req.file ? req.file.path : null;

    if (!caption) {
        return res.status(400).json({ message: "Caption wajib diisi." });
    }

    const selectedPlatforms = JSON.parse(platforms || '[]');
    const results = [];

    for (const platform of selectedPlatforms) {
        let result;
        if (platform === 'facebook') result = await postToFacebook(caption, mediaPath);
        if (platform === 'instagram') result = await postToInstagram(caption, mediaPath);
        if (platform === 'whatsapp') result = await postToWhatsApp(caption, mediaPath);

        if (result) {
            results.push(result);
            // Log to DB
            try {
                await db.execute(
                    'INSERT INTO post_logs (user_id, platform, caption, media_url, status, meta_post_id, error_message) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [userId, platform, caption, mediaPath, result.status, result.id || null, result.error || null]
                );
            } catch (dbError) {
                console.error('DB Log Error:', dbError);
            }
        }
    }

    res.json({
        message: "Proses posting selesai",
        results
    });
});

export default router;
