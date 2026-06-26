<?php

// Hausa translations for USSD messages
// NOTE: These translations should be reviewed by a native Hausa speaker before production use.

return [
    'lang_title' => 'Zabi harshe:',
    'lang_en' => '1. Turanci',
    'lang_ha' => '2. Hausa',
    'lang_sw' => '3. Kiswahili',

    'welcome_title' => 'Barka da zuwa Wild life Support',
    'menu_report' => '1. Bayar da Rahoto',
    'menu_reports' => '2. Duba Rahotannina',
    'menu_balance' => '3. Duba Lada',
    'menu_airtime' => '4. Neman Airtime',

    'incident_title' => 'Zabi nau\'in rahoto:',
    'incident_poaching' => '1. Farauta ba bisa doka ba',
    'incident_snare' => '2. Tarko/Karko',
    'incident_injured' => '3. Dabba da ta ji rauni',
    'incident_back' => '0. Komawa',
    'incident_invalid' => 'Ba daidai ba.',

    'location_prompt' => 'Shigar da wuri (misali, "Kusa da Kogin Kaduna"):',
    'location_blank' => 'Don Allah a shigar da bayanin wuri:',

    'additional_poaching' => 'Karin bayani (misali, sunan dabba, adadin mafarauta, lambar mota): ',
    'additional_injured' => 'Karin bayani (misali, nau\'in dabba, irin rauni): ',
    'additional_blank' => 'Don Allah a bayar da karin bayani don taimakawa masu gadi:',

    'confirm_title' => 'Tabbatar da rahoto:',
    'confirm_type' => 'Nau\'i: :type',
    'confirm_location' => 'Wuri: :location',
    'confirm_additional' => 'Karin bayani: :info',
    'confirm_confirm' => '1. Tabbatar',
    'confirm_edit' => '2. Gyara',
    'confirm_cancel' => '0. Soke',

    'report_success' => "Na gode! An mika rahoto #:ref.\nAn sanar da masu gadi.\nZa a biya ku NGN :amount idan an tabbatar.",

    'report_history_title' => 'Rahotannin ku na baya-bayan nan:',
    'report_history_empty' => 'Babu rahotanni har yanzu. Ku buga *384# don bayar da rahoto.',
    'report_history_line' => '#:ref - :status - :location',

    'balance_title' => 'Ladanku:',
    'balance_verified' => 'Rahotannin da aka tabbatar: :count',
    'balance_total' => 'Jimillar lada: NGN :total',
    'balance_footer' => 'Na gode don taimakawa kare namun daji!',

    'airtime_prompt' => 'Shigar da PIN kashi 4 don neman NGN :amount airtime:',
    'airtime_sent' => 'An aika NGN :amount airtime zuwa :phone. Na gode da hidimar ku!',
    'airtime_failed' => 'An sami matsala. Tuntuɓi admin.',
    'airtime_failed_retry' => 'An sami matsala. A sake gwadawa daga baya.',
    'airtime_limit' => 'Kun riga kun nemi airtime cikin awanni 24 da suka gabata. A sake gwadawa daga baya.',

    'pin_invalid_format' => 'PIN mara inganci. Don Allah shigar da PIN kashi 4:',
    'pin_wrong' => 'PIN mara inganci. Saura :remaining ƙoƙari.',
    'pin_locked' => 'Yawan ƙoƙari da yawa. An kulle asusun na awa 1.',
    'pin_account_locked' => 'An kulle asusun. A sake gwadawa cikin :minutes mintuna.',

    'not_ranger' => 'Ba a samun wannan sabis a gare ku.',

    'invalid_option' => 'Ba daidai ba.',
    'session_invalid' => 'Zaman mara inganci. Don Allah a sake gwadawa.',
    'error_generic' => 'An sami kuskure. Don Allah a sake gwadawa.',

    'type_poaching' => 'Farauta ba bisa doka ba',
    'type_snare' => 'Tarko/Karko',
    'type_injured_animal' => 'Dabba mai rauni',
];
