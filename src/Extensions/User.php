<?php

namespace TheArdent\Drivers\Viber\Extensions;

use BotMan\BotMan\Users\User as BotManUser;

class User extends BotManUser
{

    /**
     * Get URL of the user’s avatar
     */
    public function getAvatar(): ?string
    {
        $info = $this->getInfo();

        return $info['avatar'] ?? null;
    }

    /**
     * Get User’s country code
     * 2 letters country code - ISO ALPHA-2 Code
     * @return string|null
     */
    public function getCountry(): ?string
    {
        $info = $this->getInfo();

        return $info['country'] ?? null;
    }

    /**
     * User’s phone language
     * Will be returned according to the device language
     * ISO 639-1 format
     */
    public function getLanguage(): ?string
    {
        $info = $this->getInfo();

        return $info['language'] ?? null;
    }

    /**
     * The operating system type and version of the user’s primary device
     * @return string|null
     */
    public function getPrimaryDeviceOS(): ?string
    {
        $info = $this->getInfo();

        return $info['primary_device_os'] ?? null;
    }
}
