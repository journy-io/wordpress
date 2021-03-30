<div class="wrap">
    <h2><?php echo $this->plugin->displayName; ?> &raquo; Settings</h2>

    <?php
    if (isset($this->message)) {
        ?>
        <div class="updated fade"><p><?php echo $this->message; ?></p></div>
        <?php
    }
    if (isset($this->errorMessage)) {
        ?>
        <div class="error fade"><p><?php echo $this->errorMessage; ?></p></div>
        <?php
    }
    ?>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <!-- Content -->
            <div id="post-body-content">
                <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                    <form action="options-general.php?page=<?php echo $this->plugin->name; ?>" method="post">
                        <div class="postbox">
                            <h3 class="hndle">Insert journy.io tracking information</h3>

                            <div class="inside">
                                <label for="jio_api_key"><strong>API Key</strong></label>
                                <input type="text" name="jio_api_key" id="jio_api_key" style="font-family:Courier New;"
                                       size="50"
                                       placeholder="Enter API Key" <?php echo 'value="' . esc_html($this->settings['jio_api_key']) . '"'; ?> />

                                <?php wp_nonce_field($this->plugin->name, $this->plugin->name . '_nonce'); ?>


                                <input name="submit" type="submit" class="button button-primary"
                                       value="Save Changes"/>
                            </div>
                        </div>
                        <!-- /postbox -->
                        <div class="postbox">
                            <h3 class="hndle">Event tracking</h3>

                            <div class="inside">
                                <p><strong>
                                        <?php
                                        if (!$this->IsCF7ed) {
                                            echo 'Contact Form 7 NOT detected';
                                        } else
                                        {
                                        echo 'Contact Form 7 detected: version ' . WPCF7_VERSION . ' installed';
                                        ?>
                                    </strong>
                                </p><label><input type="checkbox" id="jio_cf7_submit_option"
                                                  name="jio_cf7_submit_option"
                                                  value="<?php echo( '1' ); ?>" <?php if ( get_option( 'jio_cf7_submit_option' ) === '1' ) {
			                            echo 'checked';
		                            } ?> >Track form submission</label>
                                <br>
                                <label>Email Field ID</label>
                                <input type="text" name="jio_cf7_email_id" id="jio_cf7_email_id"
                                       style="font-family:Courier New;"
                                       size="50"
                                       placeholder="Email Field ID" <?php echo 'value="' . esc_html( $this->settings['jio_cf7_email_id'] ) . '"'; ?> >

                                <br>
                                <label>First Name Field ID</label>
                                <input type="text" name="jio_cf7_first_name_id" id="jio_cf7_first_name_id"
                                       style="font-family:Courier New;"
                                       size="50"
                                       placeholder="First Name Field ID" <?php echo 'value="' . esc_html( $this->settings['jio_cf7_first_name_id'] ) . '"'; ?> >

                                <br>
                                <label>Last Name Field ID</label>
                                <input type="text" name="jio_cf7_last_name_id" id="jio_cf7_last_name_id"
                                       style="font-family:Courier New;"
                                       size="50"
                                       placeholder="Last Name Field ID" <?php echo 'value="' . esc_html( $this->settings['jio_cf7_last_name_id'] ) . '"'; ?> >


                                <br>
                                <label>Full Name Field ID</label>
                                <input type="text" name="jio_cf7_full_name_id" id="jio_cf7_full_name_id"
                                       style="font-family:Courier New;"
                                       size="50"
                                       placeholder="Full Name Field ID" <?php echo 'value="' . esc_html( $this->settings['jio_cf7_full_name_id'] ) . '"'; ?> >

	                            <?php
	                            }
                                ?>
                                <p><strong>
                                        <?php global $elementor;
                                        if (!$this->IsElementor) {
                                            echo 'Elementor NOT detected';
                                        } else
                                        {
                                        echo 'Elementor detected: version ' . ELEMENTOR_VERSION . ' installed';
                                        ?>
                                    </strong>
                                    <?php
                                    }
                                    ?>
                                </p>
                                <p><strong>
                                        <?php global $elementor;
                                        if (!$this->IsElementorPro) {
                                            echo 'ElementorPro NOT detected';
                                        } else
                                        {
                                        echo 'ElementorPro detected: version ' . ELEMENTOR_PRO_VERSION . ' installed';
                                        ?>
                                    </strong>
                                    <?php
                                    }
                                    ?>
                                </p>

                                <?php wp_nonce_field($this->plugin->name, $this->plugin->name . '_nonce'); ?>

                                <input name="submit" type="submit" name="Submit" class="button button-primary"
                                       value="Save Changes"/>
                            </div>
                        </div>
                        <!-- /postbox -->
                    </form>
                </div>
                <!-- /normal-sortables -->
            </div>
            <!-- /post-body-content -->

            <!-- Sidebar -->
            <div id="postbox-container-1" class="postbox-container">
                <?php require_once($this->plugin->folder . '/views/sidebar.php'); ?>
            </div>
            <!-- /postbox-container -->
        </div>
    </div>
</div>
