<?php

namespace Config;

/**
 * Optimization Configuration.
 *
 * Production optimization flags for the 1K concurrent-user profile.
 */
class Optimize
{
    public bool $configCacheEnabled = true;
    public bool $locatorCacheEnabled = true;
}
