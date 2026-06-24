import rateLimit from 'express-rate-limit';

export const authRateLimiter = rateLimit({
    windowMs: 1 * 60 * 1000, // 1 minute
    max: 5, // Limit each IP to 5 login requests per window
    message: {
        status: 429,
        message: "Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit."
    },
    standardHeaders: true, 
    legacyHeaders: false,
});
