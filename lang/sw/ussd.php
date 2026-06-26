<?php

// Kiswahili translations for USSD messages
// NOTE: These translations should be reviewed by a native Kiswahili speaker before production use.

return [
    'lang_title' => 'Chagua lugha:',
    'lang_en' => '1. Kiingereza',
    'lang_ha' => '2. Kihausa',
    'lang_sw' => '3. Kiswahili',

    'welcome_title' => 'Karibu kwenye Wild life Support',
    'menu_report' => '1. Ripoti Tukio',
    'menu_reports' => '2. Angalia Ripoti Zangu',
    'menu_balance' => '3. Angalia Malipo',
    'menu_airtime' => '4. Omba Airtime',

    'incident_title' => 'Chagua aina ya tukio:',
    'incident_poaching' => '1. Ujangili',
    'incident_snare' => '2. Mtego',
    'incident_injured' => '3. Mnyama aliyejeruhiwa',
    'incident_back' => '0. Nyuma',
    'incident_invalid' => 'Si sahihi.',

    'location_prompt' => 'Ingiza eneo (mfano, "Karibu na Mto Kaduna"):',
    'location_blank' => 'Tafadhali ingiza maelezo ya eneo:',

    'additional_poaching' => 'Maelezo zaidi (mfano, jina la mnyama, idadi ya wawindaji, namba ya gari): ',
    'additional_injured' => 'Maelezo zaidi (mfano, aina ya mnyama, aina ya jeraha): ',
    'additional_blank' => 'Tafadhali toa maelezo zaidi kusaidia watunzaji:',

    'confirm_title' => 'Thibitisha ripoti:',
    'confirm_type' => 'Aina: :type',
    'confirm_location' => 'Eneo: :location',
    'confirm_additional' => 'Ziada: :info',
    'confirm_confirm' => '1. Thibitisha',
    'confirm_edit' => '2. Hariri',
    'confirm_cancel' => '0. Ghairi',

    'report_success' => "Asante! Ripoti #:ref imewasilishwa.\nWatu wamearifiwa.\nUtapokea NGN :amount ikithibitishwa.",

    'report_history_title' => 'Ripoti Zako za Hivi Karibuni:',
    'report_history_empty' => 'Hujaripoti bado. Piga *384# kuripoti tukio.',
    'report_history_line' => '#:ref - :status - :location',

    'balance_title' => 'Malipo Yako:',
    'balance_verified' => 'Ripoti zilizothibitishwa: :count',
    'balance_total' => 'Jumla ya malipo: NGN :total',
    'balance_footer' => 'Asante kwa kusaidia kulinda wanyamapori!',

    'airtime_prompt' => 'Ingiza PIN yenye tarakimu 4 kuomba NGN :amount airtime:',
    'airtime_sent' => 'NGN :amount airtime imetumwa kwa :phone. Asante kwa huduma yako!',
    'airtime_failed' => 'Ombi la airtime limefeli. Wasiliana na admin.',
    'airtime_failed_retry' => 'Ombi limefeli. Tafadhali jaribu tena baadaye.',
    'airtime_limit' => 'Tayari umeomba airtime ndani ya saa 24 zilizopita. Jaribu tena baadaye.',

    'pin_invalid_format' => 'PIN si sahihi. Tafadhali ingiza PIN yenye tarakimu 4:',
    'pin_wrong' => 'PIN si sahihi. Umechukua :remaining jaribio.',
    'pin_locked' => 'Majeribio mengi sana. Akaunti imefungwa kwa saa 1.',
    'pin_account_locked' => 'Akaunti imefungwa. Jaribu tena baada ya dakika :minutes.',

    'not_ranger' => 'Huduma haipatikani kwako.',

    'invalid_option' => 'Si sahihi.',
    'session_invalid' => 'Kipindi si sahihi. Tafadhali jaribu tena.',
    'error_generic' => 'Hitilafu imetokea. Tafadhali jaribu tena.',

    'type_poaching' => 'Ujangili',
    'type_snare' => 'Mtego',
    'type_injured_animal' => 'Mnyama aliyejeruhiwa',
];
