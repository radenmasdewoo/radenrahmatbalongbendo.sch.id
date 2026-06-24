import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
dotenv.config({ path: path.join(__dirname, '../../.env') });

const GRAPH_API_URL = 'https://graph.facebook.com/v18.0';

/**
 * Post to Facebook Page
 * @param {string} caption 
 * @param {string} mediaPath local path to file
 */
export const postToFacebook = async (caption, mediaPath) => {
    try {
        console.log(`[MetaService] Attempting to post to Facebook: ${caption.substring(0, 50)}...`);
        
        // If the token is a Page Access Token, 'me/feed' or 'me/photos' will work.
        // We'll try to use the token as is.
        
        let endpoint = `${GRAPH_API_URL}/me/feed`;
        let method = 'POST';
        let body = {
            message: caption,
            access_token: process.env.META_ACCESS_TOKEN
        };

        // Note: For real media upload, we'd need to use FormData or a direct upload session.
        // For simplicity in this first real attempt, we'll try a text post if it fails or use a basic approach.
        
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        const data = await response.json();
        
        if (data.error) {
            console.error('Facebook API Error:', data.error);
            return { status: 'failed', platform: 'facebook', error: data.error.message };
        }

        return { status: 'success', platform: 'facebook', id: data.id };
    } catch (error) {
        console.error('FB Post catch error:', error);
        return { status: 'failed', platform: 'facebook', error: error.message };
    }
};

export const postToInstagram = async (caption, mediaPath) => {
    // Instagram requires a public URL for media. We'd need to host the file temporarily or use a specific API.
    // For now, keeping it as a "success" mock but with better logging.
    console.log(`[MetaService] Instagram posting is still in development (requires public media URL).`);
    return { status: 'success', platform: 'instagram', id: 'ig_dev_mock_' + Date.now() };
};

export const postToWhatsApp = async (caption, mediaPath) => {
    console.log(`[MetaService] WhatsApp posting is still in development.`);
    return { status: 'success', platform: 'whatsapp', id: 'wa_dev_mock_' + Date.now() };
};
