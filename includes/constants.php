<?php
/**
 * Rate limiting and other constants — centralized for easy tuning.
 */

return array(
    // Rate limits: 'bucket' => ['max' => X, 'window' => Y]
    'rate_limits' => array(
        'login_ip'       => array('max' => 10, 'window' => 900),   // 10 per 15 min per IP
        'login_email'    => array('max' => 5,  'window' => 900),   // 5 per 15 min per email
        'register_ip'    => array('max' => 5,  'window' => 3600),  // 5 per hour per IP
        'qr_ip'          => array('max' => 30, 'window' => 60),    // 30 per minute per IP
        'vcf_ip'         => array('max' => 60, 'window' => 60),    // 60 per minute per IP
    ),
    // Other constants
    'session_idle_timeout' => 7200,  // 2 hours
    'max_card_code_length' => 12,
    'min_card_code_length' => 4,
    'default_card_code_length' => 6,
    'max_qr_px' => 10,
    'max_qr_margin' => 10,
    'max_socials' => 15,
    'max_custom_fields' => 20,
);