<?php

use JournyIO\SDK\Client;

final class CF7CustomAction
{
    public function sendData($wpcf)
    {
        if (!get_option('jio_cf7_submit_option')) {
            return $wpcf;
        }

        $submission = WPCF7_Submission::get_instance();

        if (!$submission) {
            return $wpcf;
        }

        $posted_data = $submission->get_posted_data();

        if (empty ($posted_data)) {
            return $wpcf;
        }

        $apiKey = get_option('jio_api_key');
        $emailId = get_option('jio_cf7_email_id');
        $client = Client::withDefaults($apiKey);

        if (isset($posted_data[$emailId])) {
            $client->upsertUser([
                "email" => $posted_data[$emailId],
                "properties" => $posted_data,
            ]);

            if (isset($_COOKIE["__journey"])) {
                $client->link([
                    "deviceId" => $_COOKIE["__journey"],
                    "email" => $posted_data[$emailId],
                ]);
            }
        }
    }
}
