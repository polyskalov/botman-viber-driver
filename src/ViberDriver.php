<?php

namespace TheArdent\Drivers\Viber;

use JsonSerializable;
use BotMan\BotMan\Interfaces\DriverEventInterface;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ParameterBag;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\File;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Attachments\Location;
use BotMan\BotMan\Messages\Attachments\Video;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use TheArdent\Drivers\Viber\Events\MessageDelivered;
use TheArdent\Drivers\Viber\Events\MessageFailed;
use TheArdent\Drivers\Viber\Events\MessageSeen;
use TheArdent\Drivers\Viber\Events\MessageStarted;
use TheArdent\Drivers\Viber\Events\UserSubscribed;
use TheArdent\Drivers\Viber\Events\UserUnsubscribed;
use TheArdent\Drivers\Viber\Events\Webhook;
use TheArdent\Drivers\Viber\Exceptions\ViberException;
use TheArdent\Drivers\Viber\Extensions\AccountInfo;
use TheArdent\Drivers\Viber\Extensions\FileTemplate;
use TheArdent\Drivers\Viber\Extensions\KeyboardTemplate;
use TheArdent\Drivers\Viber\Extensions\LocationTemplate;
use TheArdent\Drivers\Viber\Extensions\PictureTemplate;
use TheArdent\Drivers\Viber\Extensions\User;
use TheArdent\Drivers\Viber\Extensions\VideoTemplate;

class ViberDriver extends HttpDriver
{
    public const DRIVER_NAME = 'Viber';

    public const API_ENDPOINT = 'https://chatapi.viber.com/pa/';

    /** @var string */
    protected $signature;

    /** @var  DriverEventInterface */
    protected $driverEvent;

    /** @var string|null */
    private $botId;

    /** @var  array|object */
    private $bot;

    /**
     * @param  Request  $request
     */
    public function buildPayload(Request $request): void
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->content = $request->getContent();
        $this->event = Collection::make($this->payload->get('event'));
        $this->signature = $request->headers->get('X-Viber-Content-Signature', '');
        $this->config = Collection::make($this->config->get('viber'));
    }

    /**
     * @return array
     */
    protected function getHeaders(): array
    {
        return [
            'Accept:application/json',
            'Content-Type:application/json',
            'X-Viber-Auth-Token: ' . $this->config->get('token'),
        ];
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest(): bool
    {
        return $this->payload->get('message_token') !== null;
    }

    /**
     * @return bool|DriverEventInterface
     */
    public function hasMatchingEvent()
    {
        $event = $this->getEventFromEventData($this->payload->all());
        if ($event) {
            $this->driverEvent = $event;
            return $this->driverEvent;
        }
        return false;
    }

    /**
     * @param  array  $eventData
     *
     * @return bool|DriverEventInterface
     */
    public function getEventFromEventData(array $eventData)
    {
        switch ($this->event->first()) {
            case 'delivered':
                return new MessageDelivered($eventData);
                break;
            case 'failed':
                return new MessageFailed($eventData);
                break;
            case 'subscribed':
                return new UserSubscribed($eventData);
                break;
            case 'conversation_started':
                return new MessageStarted($eventData);
                break;
            case 'unsubscribed':
                return new UserUnsubscribed($eventData);
                break;
            case 'seen':
                return new MessageSeen($eventData);
                break;
            case 'webhook':
                return new Webhook($eventData);
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * @param  IncomingMessage  $message
     *
     * @return Answer
     */
    public function getConversationAnswer(IncomingMessage $message): Answer
    {
        $text = $message->getText();
        return Answer::create($text)->setMessage($message)
            ->setValue($text);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     * @throws ViberException
     */
    public function getMessages(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        $user = $this->payload->get('sender') ? $this->payload->get('sender')['id'] : ($this->payload->get(
                'user'
            )['id'] ?? null);
        if ($user === null) {
            return [];
        }
        if (isset($this->payload->get('message')['text'])) {
            $message = new IncomingMessage(
                $this->payload->get('message')['text'], $user, $this->getBotId(),
                $this->payload
            );
        } elseif ($this->payload->get('message') && $this->payload->get('message')['type'] === 'location') {
            $message = new IncomingMessage(Location::PATTERN, $user, $this->getBotId(), $this->payload);
            $message->setLocation(
                new Location(
                    $this->payload->get('message')['location']['lat'],
                    $this->payload->get('message')['location']['lon'],
                    $this->payload->get('message')['location']
                )
            );
        } else {
            $message = new IncomingMessage('', $user, $this->getBotId(), $this->payload);
        }

        return [$message];
    }

    /**
     * Convert a Question object
     *
     * @param  Question  $question
     *
     * @return array
     */
    protected function convertQuestion(Question $question): array
    {
        $actions = $question->getActions();
        if (count($actions) > 0) {
            $keyboard = new KeyboardTemplate($question->getText());
            foreach ($actions as $action) {
                $text = $action['text'];
                $actionType = $action['additional']['url'] ? 'open-url' : 'reply';
                $actionBody = $action['additional']['url'] ?? $action['value'] ?? $action['text'];
                $silent = isset($action['additional']['url']);
                $keyboard->addButton($text, $actionType, $actionBody, 'regular', null, 6, $silent);
            }
            return $keyboard->jsonSerialize();
        }

        return [
            'text' => $question->getText(),
            'type' => 'text',
        ];
    }

    public function requestContactKeyboard($buttonText): array
    {
        $keyboard = new KeyboardTemplate($buttonText);
        $keyboard->addButton($buttonText, 'share-phone', 'reply');

        return $keyboard->jsonSerialize();
    }

    /**
     * @param  string|Question|IncomingMessage  $message
     * @param  IncomingMessage  $matchingMessage
     * @param  array  $additionalParameters
     *
     * @return array
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = []): array
    {
        $parameters = array_merge_recursive(
            [
                'receiver' => $matchingMessage->getSender(),
            ],
            $additionalParameters
        );

        if ($message instanceof Question) {
            $parameters = array_merge_recursive($this->convertQuestion($message), $parameters);
        } elseif ($message instanceof OutgoingMessage) {
            $attachment = $message->getAttachment();
            if (!is_null($attachment)) {
                $attachmentType = strtolower(basename(str_replace('\\', '/', get_class($attachment))));
                if ($attachmentType === 'image' && $attachment instanceof Image) {
                    $template = new PictureTemplate($attachment->getUrl(), $attachment->getTitle());
                } elseif ($attachmentType === 'video' && $attachment instanceof Video) {
                    $template = new VideoTemplate($attachment->getUrl());
                } elseif (
                    ($attachmentType === 'audio' && $attachment instanceof Audio)
                    || ($attachmentType === 'file' && $attachment instanceof File)
                ) {
                    $ext = pathinfo($attachment->getUrl(), PATHINFO_EXTENSION);
                    $template = new FileTemplate(
                        $attachment->getUrl(),
                        uniqid('', true)
                        . ($ext ? '.' . $ext : '')
                    );
                } elseif ($attachmentType === 'location' && $attachment instanceof Location) {
                    $template = new LocationTemplate($attachment->getLatitude(), $attachment->getLongitude());
                }

                if (isset($template)) {
                    $parameters = array_merge($template->jsonSerialize(), $parameters);
                }
            } else {
                $parameters['text'] = $message->getText();
                $parameters['type'] = 'text';
            }
        } elseif ($message instanceof JsonSerializable) {
            $parameters = array_merge($message->jsonSerialize(), $parameters);
        } else {
            $parameters['text'] = $message->getText();
            $parameters['type'] = 'text';
        }

        return $parameters;
    }

    /**
     * @param  mixed  $payload
     *
     * @return Response
     */
    public function sendPayload($payload): Response
    {
        return $this->http->post(
            self::API_ENDPOINT . 'send_message',
            [],
            $payload,
            $this->getHeaders(),
            true
        );
    }

    /**
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->config->get('token') !== null;
    }

    /**
     * Retrieve User information.
     *
     * @param  IncomingMessage  $matchingMessage
     *
     * @return User
     */
    public function getUser(IncomingMessage $matchingMessage): User
    {
        $personId = $matchingMessage->getSender();
        /** @var ParameterBag $payload */
        $payload = $matchingMessage->getPayload();

        $user = null;

        $response = $this->sendRequest(
            self::API_ENDPOINT . 'get_user_details',
            ['id' => $personId],
            $matchingMessage
        );
        $responseData = json_decode($response->getContent(), true);

        if (($responseData['status'] ?? null) === 0 && ($responseData['user'] ?? null)) {
            $user = $responseData['user'];
        } else {
            $user = $payload->get('user');
        }

        $name = $user['name'] ?? '';
        $nameArray = explode(' ', trim($name), 2);

        return new User(
            $personId,
            $nameArray[0] ?? '',
            $nameArray[1] ?? '',
            $name,
            $user
        );
    }


    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param  string  $endpoint
     * @param  array  $parameters
     * @param  IncomingMessage  $matchingMessage
     *
     * @return Response
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage): Response
    {
        return $this->http->post(self::API_ENDPOINT . $endpoint, [], $parameters, $this->getHeaders());
    }

    /**
     * Fetch the account’s details as registered in Viber
     * The account admin will be able to edit most of these details from his Viber client.
     * @throws ViberException
     */
    public function getAccountInfo(): AccountInfo
    {
        $response = $this->http->post(
            self::API_ENDPOINT . 'get_account_info',
            [],
            [],
            $this->getHeaders()
        );
        $responseData = json_decode($response->getContent(), true);

        if ((int) $responseData['status'] !== 0) {
            throw new ViberException($responseData['status_message'], $responseData['status']);
        }
        return new AccountInfo($responseData);
    }

    /**
     * Returns the chatbot ID.
     *
     * @return string
     * @throws ViberException
     */
    private function getBotId(): string
    {
        if ($this->bot === null) {
            $this->bot = $this->getAccountInfo();
            $this->botId = $this->bot->getId();
        }

        return $this->botId;
    }
}
