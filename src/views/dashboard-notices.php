<div class="notice notice-success is-dismissible <?php echo $this->name; ?>-notice-welcome">
    <p>
        <?php printf('Thank you for installing the journy.io plugin'); ?>
        <a href="<?php echo $setting_page; ?>" title="Click here to configure the plugin.">
    </p>
</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $(document).on('click', '.<?php echo $this->name; ?>-notice-welcome button.notice-dismiss', function (event) {
            event.preventDefault();
            $.post(ajaxurl, {
                action: '<?php echo $this->name . '_dismiss_dashboard_notices'; ?>',
                nonce: '<?php echo wp_create_nonce($this->name . '-nonce'); ?>'
            });
            $('.<?php echo $this->name; ?>-notice-welcome').remove();
        });
    });
</script>
