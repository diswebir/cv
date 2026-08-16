<?php

function front_page() {
    if (is_logged_in()) redirect('panel');
    $stats = array('cards' => 0, 'users' => 0, 'visits' => 0);
    try {
        $stats['cards'] = count_cards();
        $stats['users'] = count_users();
        $stats['visits'] = total_visits();
    } catch (Exception $e) {
        // DB not ready yet
    }
    render_public('کارت ویزیت مجازی | بسازید، اسکن کنید، به‌اشتراک بگذارید', 'landing.php', array(
        'stats' => $stats,
    ));
}
