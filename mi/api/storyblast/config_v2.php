<?php
// StoryBlast Backend Configuration V2
// Compiled from original config.php - Warning-free

// Admin Credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'RaRa@2026');
define('JWT_SECRET', 'mirara_storyblast_secret_2026');

// Meta / Facebook Page API
define('FB_PAGE_ACCESS_TOKEN', 'EAAcrrDZBjwRkBQ4rgXbRPZBQrZCQdwEWjpZCe0ZBDkvvzSwyBTx5QZBF9lXZB4ZAftx2EzhwMGvRS2qHaZAAbmWvorqwZAa4aAZC8ooCUk27fUZAlHxNgfOZCJbzOSKrPDYjCjXktDB3rL2R8CfkcfT1TtM29eGr0SjZAsxZCBpwkhZCFHOHzbZCZAf0aKTqMDyHqSGdDJ6z9YZC6J7cFBy');
define('FB_PAGE_ID', '993015473896539');
define('FB_API_VERSION', 'v20.0');

// Instagram API (Uses the same token for Business Account)
define('IG_USER_ID', '17841449815585030');
define('IG_ACCESS_TOKEN', 'EAAcrrDZBjwRkBQ4rgXbRPZBQrZCQdwEWjpZCe0ZBDkvvzSwyBTx5QZBF9lXZB4ZAftx2EzhwMGvRS2qHaZAAbmWvorqwZAa4aAZC8ooCUk27fUZAlHxNgfOZCJbzOSKrPDYjCjXktDB3rL2R8CfkcfT1TtM29eGr0SjZAsxZCBpwkhZCFHOHzbZCZAf0aKTqMDyHqSGdDJ6z9YZC6J7cFBy');

// WhatsApp API (Cloud API)
define('WA_PHONE_NUMBER_ID', '982399171632074');
define('WA_ACCESS_TOKEN', 'EAAcrrDZBjwRkBQ4rgXbRPZBQrZCQdwEWjpZCe0ZBDkvvzSwyBTx5QZBF9lXZB4ZAftx2EzhwMGvRS2qHaZAAbmWvorqwZAa4aAZC8ooCUk27fUZAlHxNgfOZCJbzOSKrPDYjCjXktDB3rL2R8CfkcfT1TtM29eGr0SjZAsxZCBpwkhZCFHOHzbZCZAf0aKTqMDyHqSGdDJ6z9YZC6J7cFBy');
define('WA_TARGET_NUMBERS', '6282221571005');

// File Paths (Absolute)
define('UPLOAD_DIR', '/mnt/webdata/htdocs/mi/api/storyblast/uploads/');
define('LOG_FILE', '/mnt/webdata/htdocs/mi/api/storyblast/logs/post_log.json');

// DB Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'mi_storyblast');
define('DB_USER', 'sb_admin');
define('DB_PASS', 'RaRa@2026');

// Error reporting
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);