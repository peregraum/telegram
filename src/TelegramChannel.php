<?php

namespace NotificationChannels\Telegram;

use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;

class TelegramChannel
{
    /**
     * @var Telegram
     */
    protected $telegram;

    private $notifiable;

    /**
     * Channel constructor.
     *
     * @param Telegram $telegram
     */
    public function __construct(Telegram $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Send the given notification.
     *
     * @param mixed        $notifiable
     * @param Notification $notification
     */
    public function send($notifiable, Notification $notification)
    {
        $this->notifiable = $notifiable;
        $message = $notification->toTelegram($notifiable);

        if (!is_array($message))
            $message = [$message];

        foreach ($message as $msg)
            $this->sendTelegramObject($msg);
    }

    private function sendTelegramObject($message)
    {
        if (is_string($message)) {
            $message = TelegramMessage::create($message);
        }

        if ($message->toNotGiven()) {
            if (!$to = $this->notifiable->routeNotificationFor('telegram')) {
                throw CouldNotSendNotification::chatIdNotProvided();
            }

            $message->to($to);
        }

        switch (get_class($message)) {
            case TelegramMessage::class:
                $this->sendMessage($message);

                break;

            case TelegramLocation::class:
                $this->sendLocation($message);

                break;

            case TelegramFile::class:
                $this->sendFile($message);

                break;
        }
    }

    private function sendMessage(TelegramMessage $message)
    {
        if(isset($message->payload['text']) && $message->payload['text'])
        {
            $params = $message->toArray();
            $this->telegram->sendMessage($params);
        }
    }

    private function sendLocation(TelegramLocation $message)
    {
        if (isset($message->payload['latitude']) && isset($message->payload['longitude'])) {
            $params = $message->toArray();
            $this->telegram->sendLocation($params);
        }
    }

    private function sendFile(TelegramFile $message)
    {
        if(isset($message->payload['file']))
        {
            $params = $message->toMultipart();
            $this->telegram->sendFile($params, $message->type, true);
        }
        else
        {
            $params = $message->toArray();
            $this->telegram->sendFile($params, $message->type);
        }
    }
}
