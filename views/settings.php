<div class="wrap">
    <h2><?php echo $this->plugin->displayName; ?> &raquo; Settings</h2>

    <?php
    if ( isset( $this->message ) ) {
        ?>
        <div class="updated fade"><p><?php echo $this->message; ?></p></div>
        <?php
    }
    if ( isset( $this->errorMessage ) ) {
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
                            	<p>
		                    		<p><label for="jio_tracking_ID"><strong>Tracking ID</strong></label></p>
		                    		<input type="text" name="jio_tracking_ID" id="jio_tracking_ID" style="font-family:Courier New;" size="50" placeholder="Enter tracking ID" <?php echo 'value="'.$this->settings['jio_tracking_ID'].'"'; ?> > </input>
		                    	</p>
								<p>
		                    		<p><label for="jio_tracking_URL"><strong>Advanced Tracking URL (optional)</strong></label></p>
		                    		<input type="text" name="jio_tracking_URL" id="jio_tracking_URL" style="font-family:Courier New;" size="50" placeholder="Enter optional tracking URL" <?php echo 'value="'.$this->settings['jio_tracking_URL'].'"'; ?> > </input>
		                    	</p>

								<?php wp_nonce_field( $this->plugin->name, $this->plugin->name . '_nonce' ); ?>
                                <h1> </h1>
		                    	<p>
									<input name="submit" type="submit" name="Submit" class="button button-primary" value="Save Changes" />
								</p>
	                       </div>
	                   </div>
	                   <!-- /postbox -->
                        <div class="postbox">
                            <h3 class="hndle">Event tracking</h3>

                            <div class="inside">
                                <p> <strong>
                                    <?php global $woocommerce;
                                    if ( ! $this->IsWooCommerced ) { echo 'WooCommerce NOT detected';
                                    } else
                                    {  echo 'WooCommerce detected: version '.$woocommerce->version.' installed';
                                    ?>
                                    </strong>
                                    <p><label><input type="checkbox" id="jio_woo_addcart_option" name="jio_woo_addcart_option" value="<?php echo ( 'show' ); ?>" <?php if ( get_option( 'jio_woo_addcart_option', true ) === 'show') { echo 'checked'; } ?> >Track 'add to cart' event</label></p>
                                    <p><label><input type="checkbox" id="jio_woo_reviewcart_option" name="jio_woo_reviewcart_option" value="<?php echo ( 'show' ); ?>" <?php if ( get_option( 'jio_woo_reviewcart_option', true ) === 'show') { echo 'checked'; } ?> >Track 'review cart' event</label></p>
                                    <p><label><input type="checkbox" id="jio_woo_checkout_option" name="jio_woo_checkout_option" value="<?php echo ( 'show' ); ?>" <?php if ( get_option( 'jio_woo_checkout_option', true ) === 'show') { echo 'checked'; } ?> >Track 'checkout' event</label></p>
                                    <strong>
                                    <?php
                                    } 
                                    ?>
                                </strong>
                                </p>
                                <h1> </h1>
                                <p> <strong>
                                    <?php 
                                    if ( ! $this->IsCF7ed ) { echo 'Contact Form 7 NOT detected'; 
                                    } else
                                    {  echo 'Contact Form 7 detected: version '.WPCF7_VERSION.' installed';
                                    ?>
                                    </strong>
                                    <p><label><input type="checkbox" id="jio_cf7_submit_option" name="jio_cf7_submit_option" value="<?php echo ( 'show' ); ?>" <?php if ( get_option( 'jio_cf7_submit_option', true ) === 'show') { echo 'checked'; } ?> >Track form submission</label></p>
                                    <strong>
                                    <?php
                                    } 
                                    ?>
                                </strong>
                                </p>

                                <?php wp_nonce_field( $this->plugin->name, $this->plugin->name . '_nonce' ); ?>
                                <h1> </h1>
                                <p>
                                    <input name="submit" type="submit" name="Submit" class="button button-primary" value="Save Changes" />
                                </p>
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
    			<?php require_once( $this->plugin->folder . '/views/sidebar.php' ); ?>
    		</div>
    		<!-- /postbox-container -->
    	</div>
	</div>
</div>
