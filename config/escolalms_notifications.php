<?php

return [
    'except_events' => [
        'EscolaLms\Auth\Events\Login',
        'EscolaLms\Auth\Events\Logout',
        'EscolaLms\Courses\Events\TopicFinished',
    ]
];
