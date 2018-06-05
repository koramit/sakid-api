<?php

return [
    'not_allowed' =>[
            'reply_code' => -1,
            'reply_text' => 'not allowed'
        ],

    'ok' => [
            'reply_code' => 0,
            'reply_text' => 'OK',
        ],

    'bad' => [
            'reply_code' => 1,
            'reply_text' => 'incomplete request',
        ],

    'duplicate_user' => [
            'reply_code' => 2,
            'reply_text' => 'duplicate user'
        ],

    'no_user' => [
            'reply_code' => 3,
            'reply_text' => 'user not found'
        ],

    'unverified' => [
            'reply_code' => 4,
            'reply_text' => 'user not verified'
        ],

    'not_done' => [
            'reply_code' => 5,
            'reply_text' => 'no implementation'
        ],

    'error' => [
            'reply_code' => 99,
            'reply_text' => 'Server error'
        ],

    'bad_or_no_user' => [
            'reply_code' => 6,
            'reply_text' => 'incomplete request or user not found'
        ],

    'unfollowed' => [
            'reply_code' => 7,
            'reply_text' => 'user not followed'
        ]
];
