<?php
// StoryBlast Backend Configuration

// Admin Credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'RaRa@2026'); // Based on .env DB_PASS
define('JWT_SECRET', 'mirara_storyblast_secret_2026');

// Meta / Facebook Page API
define('FB_PAGE_ACCESS_TOKEN', 'EAAcrrDZBjwRkBQ90miwalRQYFiuEV27o4o10OPb38xeQa1SRnZBhfxl4ST90CWtzfbTWFPfFwZBkworIR6TsBwUt1KVS21hF68nPUjoZBOK7BY8t1LejucTu3asBD7LRp2ZAvRjuotmH1vlpZAUP80rZAKDdxknKYpRvPMYb98aqTlkAYjv5dWWI1AfI4qqZCLH76nL89UOH6BGzyeeHhSJVda6h34pFUgNAMYkfoK5ZCGbkZD');
define('FB_PAGE_ID', '993015473896539');
define('FB_API_VERSION', 'v20.0');

// Instagram API (Uses the same token for Business Account)
define('IG_USER_ID', '17841449815585030');
define('IG_ACCESS_TOKEN', 'EAAcrrDZBjwRkBQ90miwalRQYFiuEV27o4o10OPb38xeQa1SRnZBhfxl4ST90CWtzfbTWFPfFwZBkworIR6TsBwUt1KVS21hF68nPUjoZBOK7BY8t1LejucTu3asBD7LRp2ZAvRjuotmH1vlpZAUP80rZAKDdxknKYpRvPMYb98aqTlkAYjv5dWWI1AfI4qqZCLH76nL89UOH6BGzyeeHhSJVda6h34pFUgNAMYkfoK5ZCGbkZD');

// WhatsApp API (Cloud API)
define('WA_PHONE_NUMBER_ID', '993015473896539');
define('WA_ACCESS_TOKEN', 'EAAcrrDZBjwRkBQwKx0dvxZAVBZAcQPmKJsOTbyKX4NXF56wnWgy1eqSlvK66G7SROI2g3kE3ZCLoFoLQyH9oJWvqxQFNm7Y1ZBulz8Nn05O2gzCPWuhG2VB2VD3oy5fnmNUQIxMpIRfcDGKGnZBqRVVoAkdHwoldjV43Jh36jrcA630I36zimpOwOraeZBcCyPReCa80332xJUlFWcDREiNxb3aDrZBxz4zTbjphl63QTLGki34lZByoTgQUeWYAae5AmSZAMjEZADbyC0nl5msbB3lmyps');
define('WA_TARGET_NUMBERS', '6282221571005'); // Example, can be comma separated

// File Paths
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('LOG_FILE', __DIR__ . '/logs/post_log.json');

// Ensure directories exist (Disabled due to permission issues)
/*
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0755, true);
}
if (!is_dir(dirname(LOG_FILE))) {
    @mkdir(dirname(LOG_FILE), 0755, true);
}
*/