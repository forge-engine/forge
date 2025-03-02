<?php

use Forge\Core\Contracts\Modules\LoggerInterface;
use Forge\Core\Helpers\App;

return [
    'queue_driver' => App::env('FORGE_EVENT_QUEUE_DRIVER', 'sync'),
    'max_retries' => 3,
    'listeners' => [
        'user.created' => [
            function (\Forge\Core\Contracts\Modules\ForgeEventInterface $event) {
                $userDAta = $event->getPayload();
                /** @var LoggerInterface $logger */
                $logger = App::getContainer()->get(\Forge\Core\Contracts\Modules\LoggerInterface::class);
                $logger->log("User created" . json_encode($userDAta));
            }
        ]
    ]
];