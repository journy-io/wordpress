<?php

use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Classes\Action_Base;
use JournyIO\SDK\Client;
use JournyIO\SDK\Event;
use JournyIO\SDK\UserIdentified;

final class CustomAction extends Action_Base {
	public function get_name() {
		return "journy.io";
	}

	public function get_label() {
		return __( 'journy.io', 'journy-io' );
	}

	private function snake( $value, $delimiter = '_' ) {
		if ( ! ctype_lower( $value ) ) {
			$value = preg_replace( '/\s+/u', '', ucwords( $value ) );

			$value = strtolower( preg_replace( '/(.)(?=[A-Z])/u', '$1' . $delimiter, $value ) );
		}

		return $value;
	}

	public function run( $record, $ajax_handler ) {
		$settings         = $record->get( 'form_settings' );
		$emailSetting     = "email";
		$firstNameSetting = "first_name";
		$lastNameSetting  = "last_name";
		$fullNameSetting  = "full_name";

		if ( isset( $settings['journy_email_field'] ) ) {
			$emailSetting = $settings['journy_email_field'];
		}

		if ( isset( $settings['journy_first_name'] ) ) {
			$firstNameSetting = $settings['journy_first_name'];
		}

		if ( isset( $settings['journy_last_name'] ) ) {
			$lastNameSetting = $settings['journy_last_name'];
		}

		if ( isset( $settings['journy_full_name'] ) ) {
			$lastNameSetting = $settings['journy_full_name'];
		}

		$raw_fields = $record->get( 'fields' );

		$fields = [];
		$email  = "";
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = $field['value'];

			if ( $field['type'] === 'email' ) {
				$email = $field['value'];
			}
		}

		$apiKey = get_option( 'jio_api_key' );
		$client = Client::withDefaults( $apiKey );

		if ( empty( $email ) && empty( $fields[ $emailSetting ] ) ) {
			return;
		}

		if ( isset( $fields[ $emailSetting ] ) ) {
			$email = $fields[ $emailSetting ];
		}

		$properties = [];

		if ( isset( $fields[ $firstNameSetting ] ) ) {
			$properties["first_name"] = $fields[ $firstNameSetting ];
		}

		if ( isset( $fields[ $lastNameSetting ] ) ) {
			$properties["last_name"] = $fields[ $lastNameSetting ];
		}

		if ( isset( $fields[ $fullNameSetting ] ) ) {
			$properties["full_name"] = $fields[ $fullNameSetting ];
		}

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
		foreach ( $raw_fields as $id => $field ) {
			if ( $id === $emailSetting ) {
				continue;
			}

			if (
				$id === $firstNameSetting
				|| $id === $lastNameSetting
				|| $id === $fullNameSetting
			) {
				continue;
			}

			$metadata[ $this->snake( $field['title'] ) ] = $field['value'];
		}

		$client->addEvent(
			Event::forUser(
				'form' . $settings["id"] . '-submit',
				UserIdentified::byEmail( $email )
			)->withMetadata( $metadata )
		);

	}

    public function register_settings_section($widget)
    {
        $widget->start_controls_section(
            'section_journy',
            [
                'label' => __('journy.io', 'journy-io'),
                'condition' => [
                    'submit_actions' => $this->get_name()
                ]
            ]
        );

        $widget->add_control(
	        'journy_email_field',
	        [
		        'label'   => __( 'Email Field ID', 'text-domain' ),
		        'type'    => Controls_Manager::TEXT,
		        'default' => 'email'
	        ]
        );

        $widget->add_control(
	        'journy_first_name',
	        [
		        'label'   => __( 'First Name Field ID', 'text-domain' ),
		        'type'    => Controls_Manager::TEXT,
		        'default' => 'first_name'
	        ]
        );

	    $widget->add_control(
		    'journy_last_name',
		    [
			    'label'   => __( 'Last Name Field ID', 'text-domain' ),
			    'type'    => Controls_Manager::TEXT,
			    'default' => 'last_name'
		    ]
	    );

	    $widget->add_control(
		    'journy_full_name',
		    [
			    'label'   => __( 'Full Name Field ID', 'text-domain' ),
			    'type'    => Controls_Manager::TEXT,
			    'default' => 'full_name'
		    ]
	    );

	    $widget->end_controls_section();
    }

    public function on_export($element)
    {
        unset(
            $element['journy_email_field'],
            $element['journy_first_name'],
            $element['journy_last_name']
        );
    }
}
