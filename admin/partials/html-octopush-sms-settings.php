<?php
/*
 * Copyright (C) 2014 octopush
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Template for settings page.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
//var_dump($_POST);
$yesimage = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAB4klEQVQ4y2P4//8/AyWYZA0ckxlYyDaAcy4Dt0SZ0FnBWP7tJBvAOZuBW7xC4MLe67v+x84L+8/ky7CFaAM4pgE1lwtc3Hl92/+o+UH/HWda/Q+c7Pmf0ZVxE1yRyFJWYZElbKbomnnnM3CLlPNd3H5ny/+oTYH/Reax/Refyv1fKFjgLrcHiwBYEftkBhGrfpO3LpPsf3LNZ3CEaZZcy8UjWS94cfvjzf8TjoT+V90u+l9hsfB/Vi+m22xuDJxgL4i2cop59ru82XV36/91t1b+N+3X/ya+mt1Gaaswm1yr+MVVj5b+z7qQ8N/8pMZ//Q1K/1l9mO+weTBwwAORPYh1SdqW2H+VF/P/L74/9//8+zP+azQrf1GtVXi04OnM/yW3M/973rD8b3vI8D9bOPNdjjSIzSixwOHPvsdgocrP+OvB/yc+6fg/7Wnf/6Wv5v+veV74P/K513+fa/b/uZM47grOZOLEmZCY/Bl3G2xU+Rn30v9/y/uK/y2fKv+nf434HwY0QCCH9672XhkOgimRJYRxr8N+sx/5/xL+V/zP/J/6IeK/WJHQXc9bVpxEJ2X2CJbd4af9fja+K/+vVKJwp+hbCgfJeYE3nmufcoP82Tn/J3CSnZnSf0Sy0iQ3omMAFlyLENN9tK8AAAAASUVORK5CYII=";
$noimage = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAABuVBMVEUAAACAAACmAAB4AAC3KTGRCw4AAAAAAwMHAAAJAAAAAADeCAIAAADEAgCnEBKkDQ+1EAm6Dgl6JSl0GRwVAADgBQMAAAAWAADlBQHkBwQAAADoAQAEEBgBBQyGAACIAACNAACQAACWAACZAACZMzeaQ0ibAACcAACeAAChAACiAAClFhemEBCmEg+mFhWmFximKCitTkuuAACvAACwQD6zAAC0AAC0Dg+1AAC2AgO5AAC6GBm6HB+7AAC7ERO8Cwu/AADAAADAEw/BAADBAQDCAADCFA7CFRfCHB/DAADDDQzEAADEFhfGAADGAwXHAADIFhrJAADKAADKAQHMGRjOMjPPAADPOTnQAADQBAbQMjfSAADSWG/UAADUMTTWLy3YAADZAADZDgvZQT/bAADcAADcRkneAADeEhPgHR/mVlrqBQbqBwrqDBDqERXrGRnsUlfub3PwAADwJSnzAAD2AAD3PDb4AAD5AAD5Tl75rMP6j5D8AAD8S13+s7r/Cg7/DQ//Fxr/HyP/ICP/KCn/QEv/Tlr/bmn/dIP/eo3/gJH/jJz/oqT/uLj/ydj/zt3/0OD/0d//4OmFVcHvAAAAHXRSTlMABAUKExoiKi81ODo9QWdpoq24w8nO1NXX2uHh9giyYs4AAADMSURBVBiVY2DAAvh4ITQ3P4TmmNTLBGb01HGCKC6VvonJIEbahH5VHgYGtqru1tqueAaGqM7Kto4YdgYGmbj67NzmwqymvJyGTFmQUrGMckfn4nxX79IEKYipIl6mahoamhaW4jB7hTyUlZSU3IXhDrG2k1dQlDO3gfEDXNSNvf2NtPx8wFxG+2AL38TQ8BRPs0gnZgYGluogq9giHUEB3ZII27ACVgYGt+jUCj1JBgZR/Zr0pBCQHocWA2kQLaHdGAgx1LAdQpeZAAkAxmgq2w7J8t0AAAAASUVORK5CYII=";
?>
<style>
    #yesImg, #noImg, .loader {
        vertical-align: middle;
        display: none;
    }

    .loader {
        border: 2px solid #f3f3f3;
        border-radius: 50%;
        border-top: 2px solid #acd7ec;
        width: 10px;
        height: 10px;
        -webkit-animation: spin 2s linear infinite; /* Safari */
        animation: spin 2s linear infinite;
    }

    /* Safari */
    @-webkit-keyframes spin {
        0% {
            -webkit-transform: rotate(0deg);
        }
        100% {
            -webkit-transform: rotate(360deg);
        }
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }</style>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

<!-- jQuery library -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Latest compiled JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<div class="row">
    <div class="col-md-9">
        <div class="wrap woocommerce os_row">
            <form method="post" id="mainform" enctype="multipart/form-data">
                <h3><?php _e('Settings', 'octopush-sms'); ?></h3>
                <div>
                    <?php
                    $notice_text = "<p>";
                    $notice_text .= __('Octopush SMS extension for Woocomerce is a text-messaging service for sending
			notifications, alerts, reminders, confirmations and SMS marketing campaigns.
			', 'octopush-sms');
                    $notice_text .= '<b><a href="https://help.octopush.com" target="_blank"> Download to the documentation </a></b>';
                    $notice_text .= __(' here for more information.', 'octopush-sms');
                    $notice_text .= "</p><p>";
                    $notice_text .= __("To use this extension you'll need to create an Octopush account (click here to watch
			the tutorial).", 'octopush-sms');
                    $notice_text .= "</p><p>";
                    $notice_text .= 'Contact us to  <b><a href="mailto:support@octopush.com" target="_blank">support@octopush.com  </a></b> for any questions or suggestions regarding';
                    $notice_text .= "</p>";
                    echo $notice_text;
                    ?>
                </div>
                <div class="ows">
                    <?php
                    if (!$this->bAuth) {
                        ?>
                        <h2><?php _e('Create your Octopush account'); ?></h2>
                        <p><?php _e('Create your account by clicking on the following image and start sending SMS now to improve your sales !', 'octopush-sms'); ?></p>
                        <p class="center"><a href="http://www.octopush.com"><img
                                        src="<?php echo plugin_dir_url(__FILE__) ?>../img/octopush.png"
                                        style="margin-top: 15px" alt="Octopush"/></a></p>
                        <?php
                    } else {
                        echo '<h3>' . __('Octopush Balance', 'octopush-sms') . ' <span style="color: red">' . number_format($this->balance, 0, ',', ' ') . ' SMS</span></h3>';
                    }
                    ?>
                </div>
                <div class="ows">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="octopush_sms_email"><?php _e('Octopush Login', 'octopush-sms'); ?></label>
                            </th>
                            <td class="forminp forminp-email">
                                <input
                                        name="octopush_sms_email"
                                        id="octopush_sms_email"
                                        type="email"
                                        style="min-width:400px;"
                                        value="<?php echo get_option('octopush_sms_email'); ?>"
                                        class=""
                                /> <span
                                        class="description"><?php _e('You can find it on your Octopush', 'octopush-sms'); ?></span>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="octopush_sms_key"><?php _e('Octopush API Key', 'octopush-sms'); ?></label>
                            </th>
                            <td class="forminp forminp-phone">
                                <input
                                        name="octopush_sms_key"
                                        id="octopush_sms_key"
                                        type="text"
                                        style="min-width:400px;"
                                        maxlength="255"
                                        value="<?php echo get_option('octopush_sms_key'); ?>"
                                        class=""
                                /> <span class="description"></span></td>
                        </tr>

                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="octopush_sms_admin_phone"><?php _e('Admin mobile number', 'octopush-sms'); ?></label>
                            </th>
                            <td class="forminp forminp-phone">
                                <input
                                        name="octopush_sms_admin_phone"
                                        id="octopush_sms_admin_phone"
                                        type="text"
                                        maxlength="15"
                                        style="min-width:400px;"
                                        value="<?php echo get_option('octopush_sms_admin_phone'); ?>"
                                        class=""
                                /> Eg : +33612345678
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="octopush_sms_sender"><?php _e("Customize Sender's name", 'octopush-sms'); ?></label>
                            </th>
                            <td class="forminp forminp-text">
                                <input
                                        name="octopush_sms_sender"
                                        id="octopush_sms_sender_name"
                                        type="text"
                                        style="min-width:400px;"
                                        value="<?php echo get_option('octopush_sms_sender'); ?>"
                                        class=""
                                />
                                <span class="description"><?php _e('11 characters maximum', 'octopush-sms'); ?></span>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="octopush_sms_admin_alert"><?php _e('Send an alert when your account is under', 'octopush-sms'); ?></label>
                            </th>
                            <td class="forminp forminp-text">
                                <input
                                        name="octopush_sms_admin_alert"
                                        id="octopush_sms_alert_limit"
                                        type="text"
                                        maxlength="5"
                                        style="min-width:40px;"
                                        value="<?php echo get_option('octopush_sms_admin_alert'); ?>"
                                        class=""
                                /> <?php _e('SMS', 'octopush-sms'); ?>
                            </td>
                        </tr>
                        <tr valign="top" class="">
                            <th scope="row"
                                class="titledesc"><?php _e('Order status notification free', 'octopush-sms'); ?></th>
                            <td class="forminp forminp-checkbox">
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php _e('Order status notification free', 'octopush-sms'); ?></span>
                                    </legend>
                                    <input
                                            name="octopush_sms_freeoption"
                                            id="octopush_sms_free_notification_1"
                                            type="radio"
                                        <?php if ((int)get_option('octopush_sms_freeoption') == 1) echo 'checked'; ?>
                                            value="1"
                                            onClick="jQuery('#paying_options').hide();"
                                    />
                                    <label class="t" for="freeoption_1"> <?php _e('Yes', 'octopush-sms'); ?> </label>
                                    <input
                                            name="octopush_sms_freeoption"
                                            id="octopush_sms_free_notification_0"
                                            type="radio"
                                        <?php if ((int)get_option('octopush_sms_freeoption') == 0) echo 'checked'; ?>
                                            value="0"
                                            onClick="jQuery('#paying_options').show();"
                                    />
                                    <label class="t"
                                           for="freeoption_1"> <?php _e('No, customer has pay to the feature', 'octopush-sms'); ?> </label>
                                </fieldset>
                                <div id="paying_options" <?php if ((int)get_option('octopush_sms_freeoption') == 1) echo ' style="display:none" '; ?>>
                                    <fieldset>
                                        <span><?php _e('SMS notification product ID', 'octopush-sms'); ?></span>
                                        <input
                                                name="octopush_sms_option_id_product"
                                                id="octopush_sms_option_id_product"
                                                type="text"
                                                maxlength="5"
                                                style="min-width:40px;"
                                                value="<?php echo get_option('octopush_sms_option_id_product'); ?>"
                                                class=""
                                        />
                                    </fieldset>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <br/>
                        <input name="save" class="button-primary" type="submit" value="Save changes">
                        <button name="testcredentials" class="testcredentials button-primary">
					<span style="display:inline-block;">
						Test my credentials <span class="loader" id="loader">&nbsp</span>
					</span>
                            <img id="yesImg" src="<?php echo $yesimage; ?>" alt="Octopush"/>
                            <img id="noImg" src="<?php echo $noimage; ?>" alt="Octopush"/>
                        </button>
                        <button name="testsms" class="testsms button-primary">
					<span style="display:inline-block;">
					Send test SMS to my phone <span class="loader" id="loader">&nbsp</span>
					</span>
                            <img id="yesImg" src="<?php echo $yesimage; ?>" alt="Octopush"/>
                            <img id="noImg" src="<?php echo $noimage; ?>" alt="Octopush"/>
                        </button>

                    <div id="message"></div>


                </div>
            </form>
        </div>
    </div>
    <div class="col-md-3">
        <div class="warning wrap woocommerce os_row ows">
            <h3><?php _e('Informations', 'octopush-sms'); ?></h3>
            <fieldset>
                <p class="clear">
                    <?php _e('A Standard Message is divided into 160 characters long and it costs 1 SMS if all of its characters are included in the list. If one or more characters are not in the list, then the message is divided into 70 characters long parts and each part will cost 1 SMS.You can check how many SMS will be sent for your message in the Messages tab.', 'octopush-sms'); ?>
                </p>
                <p>
                    <br><b><?php _e('Before applying mentioned rule, these characters are automatically replaced', 'octopush-sms'); ?></b>
                    <br/>
                <div style="float: left; width: 130px"><?php _e('Original character', 'octopush-sms'); ?></div>
                : À Á Â Ã È Ê Ë Ì Í Î Ï Ð Ò Ó Ô Õ Ù Ú Û Ý Ÿ á â ã ê ë í î ï ð ó ô õ ú û µ ý ÿ ç Þ ° ¨ ^ « » | \
                <br style="clear: both"/>
                <div style="float: left; width: 130px"><?php _e('Replaced by', 'octopush-sms'); ?></div>
                : A A A A E E E I I I I D O O O O U U U Y Y a a a e e i i i o o o o u u u y y c y o - - " " I /
                </p>
                <p class="clear">
                    <br><b><?php _e('Authorized characters', 'octopush-sms'); ?></b>
                    <br/>0 1 2 3 4 5 6 7 8 9
                    <br/>a A b B c C d D e E f F g G h H i I j J k K l L m M n N o O p P q Q r R s S t T u U v V w W x X
                    y Y z Z
                    <br/>à À á Á â Â ã Ã ä Ä å Å æ Æ ç Ç è È ê Ê ë Ë é É ì Ì í Í î Î ï Ï ð Ð ñ Ñ ò Ò ó Ó ô Ô õ Õ ö Ö ø Ø
                    ù Ù ú Ú û Û ü Ü ÿ Ÿ ý Ý Þ ß
                    <br/>{ } ~ ¤ ¡ ¿ ! ? " # $ % & \' ^ * + - _ , . / : ; < = > § @ ( ) [ ]
                    <!--<br/>Γ Δ Θ Λ Ξ Π Σ Φ Ψ Ω € £ ¥-->
                    <br/><br/><?php _e('These characters count as 2 characters :', 'octopush-sms'); ?> { } € [ ] ~
                </p>
            </fieldset>
        </div>
    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function ($) {

        $('.testcredentials').click(function (event) {
            event.preventDefault();
            if (document.getElementById("octopush_sms_email").value == '') {
                alert("Octopush login should not be empty.");
                return false;
            } else if (document.getElementById("octopush_sms_key").value == '') {
                alert("Octopush API key should not be empty.");
                return false;
            }
            document.getElementById("loader").style.display = "inline-block";
            document.getElementById("yesImg").style.display = "none";
            document.getElementById("noImg").style.display = "none";
            var data = {
                action: 'test_connection',
                octopush_sms_email: document.getElementById("octopush_sms_email").value,
                octopush_sms_key: document.getElementById("octopush_sms_key").value
            };

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            $.post(ajaxurl, data, function (response) {
                if (response == "Success") {
                    document.getElementById("loader").style.display = "none";
                    document.getElementById("yesImg").style.display = "inline-block";
                    document.getElementById("noImg").style.display = "none";
                } else {
                    document.getElementById("loader").style.display = "none";
                    document.getElementById("yesImg").style.display = "none";
                    document.getElementById("noImg").style.display = "inline-block";
                    alert(response);
                    return false;
                }

            });
            return false;
        });


    });
</script>
<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('.testsms').click(function (event) {
            event.preventDefault();

            var data = {
                action: 'test_sms',
                octopush_sms_email: document.getElementById("octopush_sms_email").value,
                octopush_sms_key: document.getElementById("octopush_sms_key").value
            };
            $.post(ajaxurl, data, function (response) {
                alert(response);
            });
        });

    });
</script>