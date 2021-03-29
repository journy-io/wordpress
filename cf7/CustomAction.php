<?php

use JournyIO\SDK\Client;

class CF7CustomAction
{
    public function sendData($wpcf)
    {
        if (!get_option('jio_cf7_submit_option')) {
            return $wpcf;
        }

        $submission = WPCF7_Submission:: get_instance();

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

        $client->upsertUser([
            "email" => $posted_data[$emailId],
            "properties" => $posted_data,
        ]);


        $client->link($_COOKIE["__journey"], null, $posted_data[$emailId]);
    }
}
