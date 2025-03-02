<?php

declare(strict_types=1);

use Predis\Client;
use RuntimeException;

class RedisDistributedLock
{
    private Client $redis;
    private string $lockKey;
    private string $lockValue;
    private int $ttl;

    public function __construct(Client $redis, string $lockKey, string $lockValue, int $ttl = 10)
    {
        $this->redis = $redis;
        $this->lockKey = $lockKey;
        $this->lockValue = $lockValue;
        $this->ttl = $ttl;
    }

    /**
     * Attempts to acquire the lock.
     */
    public function acquire(): bool
    {
        $acquired = $this->redis->set($this->lockKey, $this->lockValue, 'NX', 'EX', $this->ttl);
        return $acquired !== null;
    }

    /**
     * Releases the lock only if the current process owns it.
     */
    public function release(): bool
    {
        $luaScript = <<<'LUA'
        if redis.call("GET", KEYS[1]) == ARGV[1] then
            return redis.call("DEL", KEYS[1])
        else
            return 0
        end
        LUA;

        $result = $this->redis->eval($luaScript, 1, $this->lockKey, $this->lockValue);
        return (bool) $result;
    }

    /**
     * Checks if the lock is still held by this process.
     */
    public function isHeld(): bool
    {
        return $this->redis->get($this->lockKey) === $this->lockValue;
    }
}
