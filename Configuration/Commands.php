<?php

return [
    'rkw_outcome:processPendingRequests' => [
        'class' => \RKW\RkwOutcome\Command\ProcessPendingSurveyRequestsCommand::class,
        'schedulable' => true,
    ],
];
