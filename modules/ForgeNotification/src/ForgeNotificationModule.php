<?php

declare(strict_types=1);

namespace App\Modules\ForgeNotification;

use Forge\Core\Contracts\NotificationInterface;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Repository;
use App\Modules\ForgeNotification\Services\ForgeNotificationService;
use Forge\Core\DI\Attributes\Service;
use Forge\CLI\Traits\OutputHelper;

#[Service]
#[Module(
  name: 'ForgeNotification',
  version: '0.1.0',
  description: 'Multi-channel notification system with provider support, fluent API, and async queue integration',
  order: 99,
  author: 'Forge Team',
  license: 'MIT',
  type: 'communication',
  tags: ['communication', 'notification', 'email', 'sms', 'push']
)]
#[Compatibility(framework: '>=0.1.0', php: '>=8.3')]
#[Repository(type: 'git', url: 'https://github.com/forge-engine/modules')]
#[ConfigDefaults(defaults: [
  'forge_notification' => [
    'default_channel' => 'email',
    'queue' => [
      'enabled' => true,
      'queue_name' => 'notifications',
      'priority' => 'normal',
      'max_retries' => 3,
      'delay' => '0s',
    ],
    'channels' => [
      'email' => [
        'default_provider' => 'smtp',
        'providers' => [
          'smtp' => [
            'host' => 'localhost',
            'port' => 1025,
            'username' => '',
            'password' => '',
            'encryption' => 'none',
            'from_address' => 'noreply@forge.test',
            'from_name' => 'Forge Application',
          ],
          'sendgrid' => [
            'api_key' => '',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Forge Application',
          ],
          'mailgun' => [
            'domain' => '',
            'api_key' => '',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Forge Application',
          ],
        ],
      ],
      'sms' => [
        'default_provider' => 'twilio',
        'providers' => [
          'twilio' => [
            'account_sid' => '',
            'auth_token' => '',
            'from' => '',
          ],
          'vonage' => [
            'api_key' => '',
            'api_secret' => '',
            'from' => '',
          ],
        ],
      ],
      'push' => [
        'default_provider' => 'firebase',
        'providers' => [
          'firebase' => [
            'server_key' => '',
            'project_id' => '',
          ],
          'onesignal' => [
            'app_id' => '',
            'rest_api_key' => '',
          ],
        ],
      ],
    ],
  ],
])]
final class ForgeNotificationModule
{
  use OutputHelper;

  public function register(Container $container): void
  {
    $container->bind(NotificationInterface::class, ForgeNotificationService::class, true);
  }
}
