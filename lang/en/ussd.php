<?php

return [
    // Language selector
    'lang_title' => 'Select language:',
    'lang_en' => '1. English',
    'lang_ha' => '2. Hausa',
    'lang_sw' => '3. Kiswahili',

    // Welcome menu
    'welcome_title' => 'Welcome to Wild life Support',
    'menu_report' => '1. Report Incident',
    'menu_reports' => '2. Check My Reports',
    'menu_balance' => '3. Check Balance',
    'menu_airtime' => '4. Request Airtime',

    // Incident type menu
    'incident_title' => 'Select incident type:',
    'incident_poaching' => '1. Poaching',
    'incident_snare' => '2. Snare/Trap',
    'incident_injured' => '3. Injured Animal',
    'incident_back' => '0. Back',
    'incident_invalid' => 'Invalid option.',

    // Location prompt
    'location_prompt' => 'Enter location (e.g., "Near River Kaduna" or GPS coords):',
    'location_blank' => 'Please enter a location description:',

    // Additional info prompts
    'additional_poaching' => 'Additional info (e.g., animal name, number of poachers, vehicle plate no): ',
    'additional_injured' => 'Additional info (e.g., animal species, injury type): ',
    'additional_blank' => 'Please provide some additional details to help rangers:',

    // Confirmation
    'confirm_title' => 'Confirm report:',
    'confirm_type' => 'Type: :type',
    'confirm_location' => 'Location: :location',
    'confirm_additional' => 'Additional: :info',
    'confirm_confirm' => '1. Confirm',
    'confirm_edit' => '2. Edit',
    'confirm_cancel' => '0. Cancel',

    // Report submission
    'report_success' => "Thank you! Report #:ref submitted.\nRangers have been alerted.\nYou will receive NGN :amount if verified.",

    // Reporting history
    'report_history_title' => 'Your Recent Reports:',
    'report_history_empty' => 'You have no reports yet. Dial *384# to report an incident.',
    'report_history_line' => '#:ref - :status - :location',

    // Balance
    'balance_title' => 'Your Rewards:',
    'balance_verified' => 'Verified reports: :count',
    'balance_total' => 'Total earned: NGN :total',
    'balance_footer' => 'Thank you for helping protect wildlife!',

    // Airtime request
    'airtime_prompt' => 'Enter your 4-digit PIN to request NGN :amount airtime:',
    'airtime_sent' => 'NGN :amount airtime has been sent to :phone. Thank you for your service!',
    'airtime_failed' => 'Airtime request failed. Please contact admin.',
    'airtime_failed_retry' => 'Airtime request failed. Please try again later.',
    'airtime_limit' => 'You already requested airtime in the last 24 hours. Please try again later.',

    // PIN validation
    'pin_invalid_format' => 'Invalid PIN. Please enter a 4-digit PIN:',
    'pin_wrong' => 'Invalid PIN. :remaining attempt(s) remaining.',
    'pin_locked' => 'Too many wrong attempts. Account locked for 1 hour.',
    'pin_account_locked' => 'Account locked. Try again in :minutes minutes.',

    // Ranger check
    'not_ranger' => 'Service not available to you.',

    // Generic
    'invalid_option' => 'Invalid option.',
    'session_invalid' => 'Invalid session. Please try again.',
    'error_generic' => 'An error occurred. Please try again.',

    // Incident type labels (for confirmation summary)
    'type_poaching' => 'Poaching',
    'type_snare' => 'Snare/Trap',
    'type_injured_animal' => 'Injured Animal',
];
