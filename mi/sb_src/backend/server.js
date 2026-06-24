import express from 'express';
import helmet from 'helmet';
import cors from 'cors';
import dotenv from 'dotenv';
import authRoutes from './routes/auth.js';
import postRoutes from './routes/post.js';
import userRoutes from './routes/users.js';

dotenv.config({ path: '../.env' });

const app = express();
const PORT = process.env.PORT || 5000;

// Security Middlewares
app.use(helmet());
app.use(cors({
    origin: ['http://localhost:5173', 'https://mi.radenrahmatbalongbendo.sch.id'],
    credentials: true
}));

// Request Parsing
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/post', postRoutes);
app.use('/api/users', userRoutes);

// Health Check
app.get('/', (req, res) => {
    res.send(`
        <div style="font-family: sans-serif; text-align: center; padding: 50px; background: #0f172a; color: white; min-height: 100vh;">
            <h1 style="color: #10b981;">StoryBlast API is Running</h1>
            <p>MI Raden Rahmat Balongbendo Social Media Automation</p>
            <div style="margin-top: 20px; font-size: 12px; color: #64748b;">Version 1.0.0</div>
        </div>
    `);
});

app.get('/health', (req, res) => {
    res.json({ status: 'OK', message: 'StoryBlast API is running' });
});

app.listen(PORT, () => {
    console.log(`🚀 StoryBlast Backend running on http://localhost:${PORT}`);
});
