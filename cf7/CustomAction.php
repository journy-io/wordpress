<?php

use JournyIO\SDK\Client;
use JournyIO\SDK\Event;
use JournyIO\SDK\UserIdentified;

final class CF7CustomAction
{
    public function sendData($wpcf)
    {
	    if ( ! get_option( 'jio_cf7_submit_option' ) ) {
		    return $wpcf;
	    }

	    $formId     = $wpcf->id();
	    $submission = WPCF7_Submission::get_instance();

	    if ( ! $submission ) {
		    return $wpcf;
	    }

	    $posted_data = $submission->get_posted_data();

	    if ( empty( $posted_data ) ) {
		    return $wpcf;
	    }

	    $apiKey      = get_option( 'jio_api_key' );
	    $emailId     = get_option( 'jio_cf7_email_id' );
	    $firstNameId = get_option( 'jio_cf7_first_name_id' );
	    $lastNameId  = get_option( 'jio_cf7_last_name_id' );
	    $fullNameId  = get_option( 'jio_cf7_full_name_id' );
	    $client      = Client::withDefaults( $apiKey );
	    $email       = "";
	    $properties  = [];

	    foreach ( $posted_data as $id => $value ) {
		    if ( filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
			    $email = $value;
		    }
	    }

	    if ( isset( $posted_data[ $firstNameId ] ) ) {
		    $properties["first_name"] = $posted_data[ $firstNameId ];
	    }

	    if ( isset( $posted_data[ $lastNameId ] ) ) {
		    $properties["last_name"] = $posted_data[ $lastNameId ];
	    }

	    if ( isset( $posted_data[ $fullNameId ] ) ) {
		    $properties["full_name"] = $posted_data[ $fullNameId ];
	    }

	    if ( empty( $properties["first_name"] ) && isset( $posted_data["first_name"] ) ) {
		    $properties["first_name"] = $posted_data["first_name"];
	    }

	    if ( empty( $properties["last_name"] ) && isset( $posted_data["last_name"] ) ) {
		    $properties["last_name"] = $posted_data["last_name"];
	    }

	    if ( empty( $properties["full_name"] ) && isset( $posted_data["full_name"] ) ) {
		    $properties["full_name"] = $posted_data["full_name"];
	    }

	    if ( isset( $posted_data[ $emailId ] ) ) {
		    $email = $posted_data[ $emailId ];
	    }

	    if ( empty( $email ) && isset( $posted_data["email"] ) ) {
		    $email = $posted_data["email"];
	    }

	    if ( isset( $email ) ) {
		    $client->upsertUser( [
			    "email"      => $email,
			    "properties" => $properties,
		    ] );

		    if ( isset( $_COOKIE["__journey"] ) ) {
			    $client->link( [
				    "deviceId" => $_COOKIE["__journey"],
				    "email"    => $email,
			    ] );
		    }

            $metadata = [];

		    foreach ( $posted_data as $id => $value ) {
			    if (
				    $id === $emailId
			        || $id === $firstNameId
				    || $id === $lastNameId
				    || $id === $fullNameId
				    || $id === "first_name"
				    || $id === "last_name"
				    || $id === "full_name"
			    ) {
				    continue;
			    }

                $metadata[ $id ] = $value;
		    }

		    $client->addEvent(
			    Event::forUser(
				    'form' . $formId . '_submit',
				    UserIdentified::byEmail( $email )
			    )->withMetadata( $metadata )
		    );
	    }

	    return $wpcf;
    }
}
