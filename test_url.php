<?php
// Just a script to see what parse_str does
$url = 'tableFilters[created_at][created_from]=2026-04-05&tableFilters[created_at][created_until]=2026-05-04&tableFilters[payment_status][value]=not_paid';
parse_str($url, $output);
print_r($output);
