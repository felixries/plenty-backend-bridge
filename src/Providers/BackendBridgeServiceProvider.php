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
 * Registriert die REST-Routen der Maske und sendet hoechstens einmal pro Woche einen Hinweis
 * in die Backend-Benachrichtigungen (ausgeloest durch einen neuen Auftrag).
 */
class BackendBridgeServiceProvider extends ServiceProvider
{
    use Loggable;

    const PRIORITY = 0;
    const CACHE_KEY = 'BackendBridge_hintSent';
    const CACHE_MINUTES = 10080; // 7 Tage
    const LINK = 'https://felixries.de/?utm_source=backendbridge&utm_medium=plugin&utm_campaign=benachrichtigung';

    public function register()
    {
        $this->getApplication()->register(BackendBridgeRouteServiceProvider::class);
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
                            'subject' => 'Mach die Artikelpflege zur Nebensache',
                            'body' => 'Stell dir vor, du müsstest kaum noch in UIs arbeiten, weil deine KI alle Fleißarbeiten übernimmt und direkt in deinem plentyONE-System arbeitet',
                            'link' => self::LINK,
                            'linkTitle' => 'Erzähl mir, wie das geht',
                            'linkTarget' => self::LINK,
                        ],
                        'en' => [
                            'subject' => 'Make item maintenance a side task',
                            'body' => 'Imagine barely having to work in UIs anymore, because your AI takes over all the busywork and works directly in your plentyONE system',
                            'link' => self::LINK,
                            'linkTitle' => 'Tell me how it works',
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
