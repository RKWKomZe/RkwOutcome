<?php

return [
    'rkw_outcome:processPendingRequests' => [
        'class' => \RKW\RkwOutcome\Command\ProcessPendingSurveyRequestsCommand::class,
        'schedulable' => true,
    ],
    'rkw_outcome:createMissingRequests' => [
        'class' => \RKW\RkwOutcome\Command\CreateMissingSurveyRequestsCommand::class,
        'schedulable' => true,
    ],
];
