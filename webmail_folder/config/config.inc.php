<?php
$config = array();

// ── Database ──────────────────────────────────────────────
$config['db_dsnw'] = 'mysql://roundcube_user:Kmzwa88saa@localhost/roundcube_db';

// ── IMAP (Dovecot) ────────────────────────────────────────
$config['imap_host'] = 'localhost:143';
$config['imap_conn_options'] = array(
  'ssl' => array(
    'verify_peer'       => false,
    'verify_peer_name'  => false,
    'allow_self_signed' => true,
  ),
);
$config['imap_debug'] = true;

// ── SMTP (Postfix) ─────────────────────────────────────────
$config['smtp_host']      = 'tls://localhost:587';
$config['smtp_user']      = '%u';
$config['smtp_pass']      = '%p';
$config['smtp_auth_type'] = 'LOGIN';
$config['smtp_conn_options'] = array(
  'ssl' => array(
    'verify_peer'       => false,
    'verify_peer_name'  => false,
    'allow_self_signed' => true,
  ),
);
$config['smtp_log'] = true;

// ── Cloudflare Tunnel ─────────────────────────────────────
$config['use_https']   = true;
$config['force_https'] = false;
$config['ip_check']    = false;

// ── Session & Keamanan ────────────────────────────────────
$config['des_key']          = 'x7A9pqw2MnbVcxZl1KjhFgty';
$config['session_lifetime'] = 60;

// ── Tampilan ──────────────────────────────────────────────
$config['product_name'] = 'Webmail Raden Rahmat';
$config['plugins']      = array('archive', 'zipdownload', 'password');
$config['skin_logo']    = [
    '*:[favicon]' => 'skins/elastic/images/logo-header.webp',
];

// ── Multi Domain ──────────────────────────────────────────
$config['username_domain'] = 'radenrahmatbalongbendo.sch.id';
