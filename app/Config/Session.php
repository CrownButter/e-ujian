<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;
use CodeIgniter\Session\Handlers\RedisHandler;

class Session extends BaseConfig
{
    /**
     * Session driver is selected by SESSION_DRIVER and defaults to files so
     * existing deployments remain unchanged.
     *
     * Supported values: file, redis.
     */
    public string $driver = FileHandler::class;

    public string $cookieName = 'ci_session';

    public int $expiration = 7200;

    /**
     * File sessions use WRITEPATH/session. Redis sessions use REDIS_HOST and
     * REDIS_PORT through $savePath.
     */
    public string $savePath = WRITEPATH . 'session';

    public bool $matchIP = false;

    public int $timeToUpdate = 300;

    public bool $regenerateDestroy = false;

    public ?string $DBGroup = null;

    public int $lockRetryInterval = 100_000;

    public int $lockMaxRetries = 300;

    public function __construct()
    {
        parent::__construct();

        $sessionDriver = strtolower(trim((string) env('SESSION_DRIVER', 'file')));

        if ($sessionDriver === 'redis') {
            $this->driver = RedisHandler::class;
            $this->savePath = sprintf(
                'tcp://%s:%d?database=%d',
                env('REDIS_HOST', 'redis'),
                (int) env('REDIS_PORT', 6379),
                (int) env('REDIS_DB', 0)
            );
            return;
        }

        $this->driver = FileHandler::class;
        $this->savePath = WRITEPATH . 'session';
    }
}
