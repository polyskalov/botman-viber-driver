<?php

namespace TheArdent\Drivers\Viber\Console\Commands;

use Illuminate\Console\Command;

class ViberRegisterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'botman:viber:register';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set webhook for Viber API';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        if (!config('botman.viber.token')) {
            $this->warn('Please specify VIBER_TOKEN in your .env file first');
            return;
        }

        $callbackUrl = $this->ask(
            'What is the target url for the Viber bot?',
            url('/botman')
        );
        $api = 'https://chatapi.viber.com/pa/set_webhook';

        $postdata = json_encode(
            [
                "url" => $callbackUrl,
                "event_types" => [
                    "delivered",
                    "seen",
                    "failed",
                    "subscribed",
                    "unsubscribed",
                    "conversation_started"
                ],
                "send_name" => true,
                "send_photo" => true
            ]
        );

        $opts = [
            'http' =>
                [
                    'method' => 'POST',
                    'header' => [
                        'Content-type: application/x-www-form-urlencoded;',
                        'X-Viber-Auth-Token: ' . config('botman.viber.token'),
                    ],
                    'content' => $postdata
                ]
        ];

        $context = stream_context_create($opts);

        $result = json_decode(
            file_get_contents(
                $api,
                false,
                $context
            ),
            true
        );

        if (($result['status'] ?? null) === 0) {
            $this->alert('Your bot is now set up with Viber\'s webhook!');
        } else {
            $this->error(
                'Error'
                . ($result['status'] ? ' #' . $result['status'] : '')
                . ': '
                . ($result['status_message'] ?? '')
            );
        }
    }
}