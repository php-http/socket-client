<?php

namespace Http\Client\Socket\Tests;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class Semaphore
{
    private static $openConnections = 0;

    /**
     * Busy waiting for my turn to go.
     *
     * @return bool
     */
    public static function acquire()
    {
        $tries = 1;
        while (self::$openConnections > 0) {
            sleep($tries++);
            if ($tries > 5) {
                return false;
            }
        }
        self::$openConnections++;

        return true;
    }

    /**
     * Signal that you are done.
     */
    public static function release()
    {
        // Do no be too quick
        usleep(500000);
        self::$openConnections--;
    }
}
