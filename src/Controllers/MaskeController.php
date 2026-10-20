<?php

namespace BackendBridge\Controllers;

use Plenty\Modules\Plugin\Storage\Contracts\StorageRepositoryContract;
use Plenty\Modules\User\Contracts\UserRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;

/**
 * Speichert und liefert den Code der Maske.
 * Lesen: jeder angemeldete Backend-User. Speichern: nur Administratoren.
 */
class MaskeController extends Controller
{
    const PLUGIN = 'BackendBridge';
    const KEY = 'maske/aktuell.js';
    const MAX_BYTES = 3145728; // 3 MB

    public function show(Response $response)
    {
        /** @var StorageRepositoryContract $storage */
        $storage = pluginApp(StorageRepositoryContract::class);
        try {
            if (!$storage->doesObjectExist(self::PLUGIN, self::KEY, false)) {
                return $response->json(['code' => null]);
            }
            $obj = $storage->getObject(self::PLUGIN, self::KEY, false);
            return $response->json(['code' => (string) $obj->body]);
        } catch (\Exception $e) {
            return $response->json(['fehler' => 'Lesen fehlgeschlagen: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request, Response $response)
    {
        if (!$this->istAdmin()) {
            return $response->json(['fehler' => 'Nur Administratoren dürfen eine Maske veröffentlichen.'], 403);
        }
        $code = (string) $request->get('code', '');
        if (strlen(trim($code)) < 20) {
            return $response->json(['fehler' => 'Kein Code übergeben.'], 422);
        }
        if (strlen($code) > self::MAX_BYTES) {
            return $response->json(['fehler' => 'Der Code ist größer als 3 MB.'], 413);
        }
        /** @var StorageRepositoryContract $storage */
        $storage = pluginApp(StorageRepositoryContract::class);
        try {
            $storage->uploadObject(self::PLUGIN, self::KEY, $code, false);
        } catch (\Exception $e) {
            return $response->json(['fehler' => 'Speichern fehlgeschlagen: ' . $e->getMessage()], 500);
        }
        return $response->json(['ok' => true, 'bytes' => strlen($code)]);
    }

    private function aktuellerUser()
    {
        try {
            /** @var UserRepositoryContract $repo */
            $repo = pluginApp(UserRepositoryContract::class);
            $user = $repo->getCurrentUser();
            return $user ? $user->toArray() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ustatus 1 = Administrator
    private function istAdmin()
    {
        $user = $this->aktuellerUser();
        if (!$user || !isset($user['ustatus'])) {
            return false;
        }
        return (int) $user['ustatus'] === 1;
    }
}
