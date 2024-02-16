<?php

namespace Core\Manager;

use Core\Commands\CommandPrefix;

use function Core\env;

/**
 * To manage Prefixes using JSON, in case if you don't want to use an actual DB
 *
 * The Schema that is needed
 * {
 * "guild_id_1": "prefix1",
 * "guild_id_2": "prefix2",
 * }
 */
class JsonPrefixManager implements CommandPrefix
{
    /** @var array */
    private $cache = [];

    /** @var string */
    private $jsonFileName;

    /** @var string */
    private $defaultPrefix;

    /**
     * JsonPrefixManager constructor.
     */
    public function __construct(string $jsonFileName)
    {
        $this->jsonFileName = $jsonFileName;
        $this->defaultPrefix = env()->DEFAULT_PREFIX;
        // Load all prefixes into the cache during instantiation
        $this->loadAllPrefixes();
    }

    /**
     * Loads all prefixes from the JSON file into the cache.
     */
    private function loadAllPrefixes(): void
    {
        $jsonData = file_get_contents($this->jsonFileName);

        $data = json_decode($jsonData, true);

        // Cache the prefixes
        if (is_array($data)) {
            $this->cache = $data;
        }
    }

    /**
     * Saves all prefixes from the cache to the JSON file.
     */
    private function saveAllPrefixes(): void
    {
        $jsonData = json_encode($this->cache, JSON_PRETTY_PRINT);

        file_put_contents($this->jsonFileName, $jsonData);
    }

    /**
     * Gets the prefix for a given guild.
     */
    public function getPrefix(string $guildId): string
    {
        if (isset($this->cache[$guildId])) {
            return $this->cache[$guildId];
        }

        return $this->defaultPrefix;
    }

    /**
     * Sets the prefix for a given guild.
     */
    public function setPrefix(string $guildId, string $prefix): bool
    {
        // Update prefix in the cache
        $this->cache[$guildId] = $prefix;

        // Save the updated cache to the JSON file
        $this->saveAllPrefixes();

        return true;
    }
}
