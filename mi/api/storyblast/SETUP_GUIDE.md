# StoryBlast Setup Guide

This guide explains how to obtain and configure the necessary credentials for the StoryBlast Meta API integration.

## 1. Facebook Page Access Token (Long-lived)
1. Go to the [Meta for Developers Explorer](https://developers.facebook.com/tools/explorer/).
2. Select your App and the Facebook Page.
3. Add the following permissions: `pages_manage_posts`, `pages_read_engagement`, `instagram_basic`, `instagram_content_publish`, `whatsapp_business_messaging`.
4. Click **Generate Token**.
5. To make it long-lived (valid for 60 days or permanent), use the `oauth/access_token` endpoint:
   `GET /oauth/access_token?grant_type=fb_exchange_token&client_id={app-id}&client_secret={app-secret}&fb_exchange_token={short-lived-token}`

## 2. Facebook Page ID
1. Go to your Facebook Page's **About** section or **Professional Dashboard**.
2. Or use Graph API: `GET /me?fields=id,name` (using the Page Access Token).

## 3. Instagram Business Account ID (IG_USER_ID)
1. Ensure your Instagram account is linked to your Facebook Page and is a **Business Account**.
2. Use Graph API: `GET /{page-id}?fields=instagram_business_account`
3. The `id` returned under `instagram_business_account` is your `IG_USER_ID`.

## 4. WhatsApp Phone Number ID (WA_PHONE_NUMBER_ID)
1. Go to the [Meta Business Suite](https://business.facebook.com/settings/whatsapp-business-accounts/).
2. Select your WhatsApp Business Account.
3. In the **WhatsApp Accounts** > **WhatsApp Manager** > **API Setup** section, find your **Phone Number ID**.

## 5. Deployment
- Upload the `api/storyblast/` folder to your shared hosting.
- Set directory permissions: `uploads/` and `logs/` must be writable by the web server (e.g., `chmod 775`).
- Access health check: `https://your-domain.com/mi/api/storyblast/health`.
- Test configuration: `https://your-domain.com/mi/api/storyblast/test_health.php`.
