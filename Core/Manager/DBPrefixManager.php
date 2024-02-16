<?php

namespace Core\Manager;

use Core\Commands\CommandPrefix;
use Core\Database\DatabaseInterface;

use function Core\env;

/**
 * To manage Prefixes using the database
 */
class DbPrefixManager implements CommandPrefix
{
    /** @var array */
    private $cache = [];

    /** @var DatabaseInterface */
    private $database;

    /** @var string */
    private $defaultPrefix;

    /**
     * DBPrefixManager constructor.
     */
    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
        $this->defaultPrefix = env()->DEFAULT_PREFIX;
        // Load all prefixes into the cache during instantiation
        $this->loadAllPrefixes();
    }

    /**
     * Fetches and caches all guild prefixes from the database.
     */
    private function loadAllPrefixes(): void
    {
        // Fetch all guilds' prefixes from the database
        $result = $this->database->query('SELECT id, prefix FROM guilds')->fetchAll();

        // Cache the prefixes
        foreach ($result as $row) {
            $this->cache[$row['id']] = $row['prefix'];
        }
    }

    /**
     * Gets the prefix for a given guild.
     */
    public function getPrefix(string $guildId): string
    {
        // Check if prefix is cached
        if (isset($this->cache[$guildId])) {
            return $this->cache[$guildId];
        }

        // If a custom prefix is not set, use the default prefix
        return $this->defaultPrefix;
    }

    /**
     * Sets the prefix for a given guild.
     */
    public function setPrefix(string $guildId, string $prefix): bool
    {
        // Update prefix in the cache
        $this->cache[$guildId] = $prefix;

        // Update prefix in the database
        $success = $this->database->query("UPDATE guilds SET prefix = '{$prefix}' WHERE id = '{$guildId}'");

        return (bool) $success;
    }
}
