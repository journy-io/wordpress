<?php

use Elementor\Controls_Manager;
use JournyIO\SDK\Client;
use ElementorPro\Modules\Forms\Classes\Action_Base;

final class CustomAction extends Action_Base
{
    public function get_name()
    {
        return "journy.io";
    }

    public function get_label()
    {
        return __('journy.io', 'journy-io');
    }

    public function run($record, $ajax_handler)
    {
        $settings = $record->get('form_settings');

        if (empty($settings['journy_email_field'])) {
            return;
        }

        $raw_fields = $record->get('fields');

        $fields = [];
        foreach ($raw_fields as $id => $field) {
            $fields[$id] = $field['value'];
        }

        if (empty($fields[$settings['journy_email_field']])) {
            return;
        }

        $apiKey = get_option('jio_api_key');
        $client = Client::withDefaults($apiKey);

        if (isset($fields[$settings['journy_email_field']])) {
            $client->upsertUser([
                "email" => $fields[$settings['journy_email_field']],
                "properties" => $fields,
            ]);

            if (isset($_COOKIE["__journey"])) {
                $client->link([
                    "deviceId" => $_COOKIE["__journey"],
                    "email" => $fields[$settings['journy_email_field']],
                ]);
            }
        }
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
                'label' => __('Email Field ID', 'text-domain'),
                'type' => Controls_Manager::TEXT,
            ]
        );

        $widget->end_controls_section();
    }

    public function on_export($element)
    {
        unset(
            $element['journy_email_field']
        );
    }
}
