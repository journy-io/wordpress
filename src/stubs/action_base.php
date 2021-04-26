<?php
namespace ElementorPro\Modules\Forms\Classes;

use Elementor\Widget_Base;

// phpcs:ignore
abstract class Action_Base
{

	// phpcs:ignore
    abstract public function get_name();

	// phpcs:ignore
    abstract public function get_label();

    /**
     * @param Form_Record  $record
     * @param Ajax_Handler $ajax_handler
     */
	// phpcs:ignore
    abstract public function run($record, $ajax_handler);

    /**
     * @param Widget_Base $widget
     */
	// phpcs:ignore
    abstract public function register_settings_section($widget);

    /**
     * @param array $element
     */
	// phpcs:ignore
    abstract public function on_export($element);
}
