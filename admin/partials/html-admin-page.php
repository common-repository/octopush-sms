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
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="wrap woocommerce">
    <div class="icon32 icon32-woocommerce-status" id="icon-woocommerce">

    </div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <?php
        $tabs = array(
            'settings' => __('Settings', 'octopus-sms'),
            'messages' => __('Messages', 'octopus-sms'),
            'campaigns' => __('Campaigns', 'octopus-sms'),
            'history' => __('History', 'octopus-sms'),
            'news' => __('News', 'octopus-sms'),
        );

        //if new campaign, we change to the send tab
        if ($current_action=="octopush_sms_send_tab" && isset($_REQUEST['newCampaign']) && $_REQUEST['newCampaign']==1) {
            $current_tab="campaigns";
        }
        
        foreach ($tabs as $name => $label) {
            echo '<a href="' . admin_url('admin.php?page=octopush-sms&tab=' . $name) . '" class="nav-tab ';
            if ($current_tab == $name)
                echo 'nav-tab-active';
            echo '">' . __($label,'octopush-sms') . '</a>';
        }
        ?>
    </h2>
    <?php
    switch ($current_tab) {
        case "settings" :
            Octopush_Sms_Admin::octopush_sms_settings();
            break;
        case "messages" :
            Octopush_Sms_Admin::octopush_sms_messages();
            break;
        case "campaigns" :
            Octopush_Sms_Admin::octopush_sms_campaigns();
            break;
        case "history" :
            Octopush_Sms_Admin::octopush_sms_history();
            break;
        default :
            Octopush_Sms_Admin::octopush_sms_news();
            break;
    }
    ?>
</div>

