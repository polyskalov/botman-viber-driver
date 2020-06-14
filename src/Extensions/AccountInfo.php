<?php


namespace TheArdent\Drivers\Viber\Extensions;


use BotMan\BotMan\Messages\Attachments\Location;
use TheArdent\Drivers\Viber\Exceptions\ViberException;

/**
 * Bot information
 * @package TheArdent\Drivers\Viber\Extensions
 */
class AccountInfo
{
    /**
     * @var array bot data
     */
    protected $data;

    public function __construct(array $data)
    {
        if (!array_key_exists('id', $data)) {
            throw new ViberException('Initializing AccountInfo with empty user');
        }

        $this->data = $data;
    }

    /**
     * Unique numeric id of the account
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->data['id'] ?? null;
    }

    /**
     * Account name
     *
     * Max 75 characters
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->data['name'] ?? null;
    }

    /**
     * Unique URI of the Account
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->data['uri'] ?? null;
    }

    /**
     * Account icon URL
     *
     * JPEG, 720x720, size no more than 512 kb
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->data['icon'] ?? null;
    }

    /**
     * Conversation background URL
     *
     * JPEG, max 1920x1920, size no more than 512 kb
     * @return string|null
     */
    public function getBackground(): ?string
    {
        return $this->data['background'] ?? null;
    }

    /**
     * Account category
     * @return string|null
     */
    public function getCategory(): ?string
    {
        return $this->data['category'] ?? null;
    }

    /**
     * Account sub-category
     * @return string|null
     */
    public function getSubCategory(): ?string
    {
        return $this->data['subcategory'] ?? null;
    }

    /**
     * Account location (coordinates)
     *
     * Will be used for finding accounts near me, lat & lon coordinates
     * @return Location
     */
    public function getLocation(): Location
    {
        return new Location(
            $this->data['location']['lat'] ?? 0,
            $this->data['location']['long'] ?? 0,
            $this->data['location']
        );
    }

    /**
     * Account country
     * @return string|null 2 letters country code - ISO ALPHA-2 Code
     */
    public function getCountry(): ?string
    {
        return $this->data['country'] ?? null;
    }

    /**
     * Account registered webhook
     * @return string|null webhook URL
     */
    public function getWebhook(): ?string
    {
        return $this->data['webhook'] ?? null;
    }

    /**
     * Account registered events – as set by set_webhook request
     * @return string[] delivered, seen, failed and conversation_started
     */
    public function getEventTypes(): array
    {
        return $this->data['event_types'] ?? [];
    }

    /**
     * Number of subscribers
     * @return int
     */
    public function getSubscribersCount(): int
    {
        return $this->data['subscribers_count'] ?? 0;
    }

    /**
     * Members of the bot’s public chat
     * @deprecated
     */
    public function getMembers(): array
    {
        return $this->data['members'] ?? [];
    }
}