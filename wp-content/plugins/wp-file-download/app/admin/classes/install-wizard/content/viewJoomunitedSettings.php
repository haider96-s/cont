<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');
$wizard = new WpfdInstallWizard();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- View request, no action
$step      = isset($_GET['step']) ? sanitize_key($_GET['step']) : '';
$next_link = $wizard->getNextLink($step);
$ju_update_link = JU_BASE . 'index.php?option=com_juupdater&view=login
                &tmpl=component&site=' . admin_url() . '&TB_iframe=true&width=600&height=550';
$token = get_option('ju_user_token');
?>
<style>
    .joomunited-login-wrapper {
        background: #fff;
        padding: 25px;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ju-button {
        padding: 10px 25px;
        border-radius: 100px;
        font-weight: bolder;
        display: flex;
        align-items: center;
        justify-content: center;
        letter-spacing: 2px;
        font-size: 1rem;
        cursor: pointer;
        text-align: center;
        transition: all ease 0.5s;
        text-decoration: none;
    }
    .orange-outline-button {
        border: 1px solid #ff8726;
        color: #ff8726;
        background: #fff;
    }
    .ju-button:hover {
        -webkit-box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
        -moz-box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
        -ms-box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
        box-shadow: 0 2px 5px 0 rgba(0, 0, 0, 0.16), 0 2px 10px 0 rgba(0, 0, 0, 0.12);
    }
    .wizard-content .description.center {
        float: unset;
        text-align: center;
    }
</style>
<form method="post" id="quick-config-form">
    <?php wp_nonce_field('wpfd-setup-wizard', 'wizard_nonce'); ?>
    <input type="hidden" name="wpfd_save_step" value="1"/>
    <div class="wizard-header">
        <div class="title h1 font-size-35"><?php esc_html_e('JoomUnited login & file previewer', 'wpfd'); ?></div>
        <p class="description"><?php echo sprintf(esc_html__('The account login is very useful, you\'ll be able to update automatically the plugin and use our %s', 'wpfd'), '<a href="https://www.joomunited.com/wordpress-products/wp-file-download/wordpress-file-manager-document-preview">powerful file previewer</a>'); ?></p>
    </div>
    <div class="wizard-content">
        <div class="joomunited-login-wrapper">
            <strong>Joomunited Previewer server</strong>
            <?php if (empty($token)) : ?>
            <a href="<?php echo esc_url_raw($ju_update_link); ?>" class="thickbox ju-button orange-outline-button" type="button"><?php esc_html_e('LOGIN NOW >>>', 'wpfd'); ?></a>
            <?php else : ?>
                <button class="ju-btn-disconnect ju-button orange-outline-button" type="button"><?php esc_html_e('Disconnect', 'wpfd'); ?></button>
            <?php endif; ?>
        </div>
        <div class="description center">
            <?php esc_html_e('Click on the login button and use the same credentials as on the JoomUnited website', 'wpfd'); ?>
        </div>
    </div>

    <div class="wizard-footer">
        <div class="wpfd_row_full">
            <input type="submit" value="<?php esc_html_e('Continue', 'wpfd'); ?>" class="m-tb-20" name="wpfd_save_step"/>
        </div>
    </div>
</form>
<script>
  (function ($) {
    var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
    var eventer = window[eventMethod];
    var messageEvent = eventMethod === "attachEvent" ? "onmessage" : "message";

    // Listen to message from child window
    eventer(messageEvent, function (e) {

      var res = e.data;
      if (typeof res !== "undefined" && typeof res.type !== "undefined" && res.type === "joomunited_login") {
        $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
            'action': 'ju_add_token',
            'token': res.token,
            'ju_updater_nonce': updaterparams.ju_updater_nonce
          },
          success: function () {
            location.reload();
          }
        });
      }
    }, false);
    $(document).on('click', '.ju-btn-disconnect', function () {
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          'action': 'ju_logout'
        },
        success: function () {
          location.reload();
        }
      });
    });
  })(jQuery);
</script>