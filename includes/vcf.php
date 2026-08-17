<?php

function vcf_escape($s) {
    $s = str_replace(array("\r\n", "\r"), "\n", (string)$s);
    $s = str_replace('\\', '\\\\', $s);
    $s = str_replace("\n", '\\n', $s);
    $s = str_replace(';', '\\;', $s);
    $s = str_replace(',', '\\,', $s);
    return $s;
}

function vcf_fold($line) {
    if (strlen($line) <= 75) return $line;
    $out = '';
    $rest = $line;
    while (strlen($rest) > 75) {
        $chunk = function_exists('mb_strcut') ? mb_strcut($rest, 0, 75) : substr($rest, 0, 75);
        $out .= $chunk . "\r\n ";
        $rest = substr($rest, strlen($chunk));
    }
    return $out . $rest;
}

function build_vcard($card) {
    $c = array_merge(card_defaults(), $card);
    $nameParts = preg_split('/\s+/', trim((string)$c['full_name']));
    $given = isset($nameParts[0]) ? $nameParts[0] : '';
    $family = count($nameParts) > 1 ? $nameParts[count($nameParts) - 1] : '';
    $middle = count($nameParts) > 2 ? implode(' ', array_slice($nameParts, 1, -1)) : '';
    $lines = array();
    $lines[] = 'BEGIN:VCARD';
    $lines[] = 'VERSION:3.0';
    $lines[] = 'N;CHARSET=UTF-8:' . vcf_escape($family) . ';' . vcf_escape($given) . ';' . vcf_escape($middle) . ';;';
    $lines[] = 'FN;CHARSET=UTF-8:' . vcf_escape($c['full_name']);
    if ($c['job_title'] !== '') $lines[] = 'TITLE;CHARSET=UTF-8:' . vcf_escape($c['job_title']);
    if ($c['company'] !== '') $lines[] = 'ORG;CHARSET=UTF-8:' . vcf_escape($c['company']);
    if ($c['phone'] !== '') $lines[] = 'TEL;TYPE=CELL,VOICE:' . vcf_escape($c['phone']);
    if ($c['phone2'] !== '') $lines[] = 'TEL;TYPE=CELL,VOICE:' . vcf_escape($c['phone2']);
    if ($c['email'] !== '') $lines[] = 'EMAIL;TYPE=INTERNET:' . vcf_escape($c['email']);
    if ($c['website'] !== '') $lines[] = 'URL;TYPE=WORK:' . vcf_escape(normalize_url($c['website']));
    if ($c['address'] !== '') $lines[] = 'ADR;TYPE=HOME;CHARSET=UTF-8:;;' . vcf_escape($c['address']) . ';;;;';
    if ($c['bio'] !== '') $lines[] = 'NOTE;CHARSET=UTF-8:' . vcf_escape($c['bio']);
    $socials = card_socials($card);
    foreach ($socials as $k => $v) {
        if ($v === '' || $v === null) continue;
        $u = social_detect($k, $v);
        if ($u !== '') $lines[] = 'URL;TYPE=' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $k)) . ':' . vcf_escape($u);
    }
    $lines[] = 'END:VCARD';
    foreach ($lines as $i => $ln) $lines[$i] = vcf_fold($ln);
    return implode("\r\n", $lines) . "\r\n";
}

function send_vcf($card) {
    $base = preg_replace('/[^a-zA-Z0-9]+/', '-', mb_substr((string)$card['full_name'], 0, 30));
    $base = trim($base, '-');
    if ($base === '') $base = 'contact';
    // RFC 5987 encoding for safe filename in Content-Disposition
    $encoded = rawurlencode($base);
    header('Content-Type: text/x-vcard; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $encoded . '.vcf"; filename*=UTF-8\'\'' . $encoded . '.vcf');
    echo build_vcard($card);
    exit;
}
