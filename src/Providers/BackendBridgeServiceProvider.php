<?php

namespace BackendBridge\Providers;

use Plenty\Modules\Notifications\Contracts\NotificationsRepositoryContract;
use Plenty\Modules\Notifications\Models\Notification;
use Plenty\Modules\Order\Events\OrderCreated;
use Plenty\Plugin\Application;
use Plenty\Plugin\CachingRepository;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\ServiceProvider;

/**
 * Das Backend-UI kommt komplett aus ui.json + ui/bridge-<version>.html.
 *
 * Serverseitig gibt es nur den Hinweis in den Backend-Benachrichtigungen. Mechanik wie im
 * Google-Tag-Manager-Ultimate-Plugin (dort live erprobt): Ausloeser ist ein neuer Auftrag
 * (OrderCreated), eine Cache-Sperre verhindert Wiederholungen. Hier haelt die Sperre eine
 * Woche, der Hinweis erscheint also hoechstens einmal pro Woche.
 */
class BackendBridgeServiceProvider extends ServiceProvider
{
    use Loggable;

    const PRIORITY = 0;
    const CACHE_KEY = 'BackendBridge_hintSent';
    const CACHE_MINUTES = 10080; // 7 Tage
    const LINK = 'https://calendly.com/felixries/ki-entlastungscheck';

    public function register()
    {
    }

    public function boot(Dispatcher $dispatcher)
    {
        $dispatcher->listen(OrderCreated::class, function ($event) {
            /** @var CachingRepository $cache */
            $cache = pluginApp(CachingRepository::class);
            if ($cache->get(self::CACHE_KEY)) {
                return;
            }

            /** @var Application $app */
            $app = pluginApp(Application::class);
            /** @var NotificationsRepositoryContract $notificationRepo */
            $notificationRepo = pluginApp(NotificationsRepositoryContract::class);

            try {
                $notificationRepo->addNotification([
                    'id' => 'backendbridge' . uniqid(),
                    'plentyId' => $app->getPlentyId(),
                    'source' => 'BackendNotifications',
                    'type' => Notification::NOTIFICATION_TYPE_INFO,
                    'channels' => ['plugin'],
                    'contents' => [
                        'de' => [
                            'subject' => 'Artikelpflege in Minuten statt Stunden!',
                            'body' => 'Stell jetzt deinen ersten KI-Mitarbeiter ein, dem du alle lästigen Artikelarbeiten abgeben kannst. Buch dir jetzt einen Termin',
                            'link' => self::LINK,
                            'linkTitle' => 'Termin buchen',
                            'linkTarget' => self::LINK,
                        ],
                        'en' => [
                            'subject' => 'Item maintenance in minutes instead of hours!',
                            'body' => 'Hire your first AI employee now and hand over all the tedious item work. Book a call now',
                            'link' => self::LINK,
                            'linkTitle' => 'Book a call',
                            'linkTarget' => self::LINK,
                        ],
                    ],
                ]);
            } catch (\Exception $e) {
                $this->getLogger(__CLASS__ . 'OrderCreated')->error('BackendBridge::Notification-Error', ['error' => $e->getMessage()]);
            }

            $cache->put(self::CACHE_KEY, true, self::CACHE_MINUTES);
        }, self::PRIORITY);
    }
}
