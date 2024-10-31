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

if (!class_exists('Octopush_Sms_News')) :

    /**
     * WC_Admin_Settings_General
     */
    class Octopush_Sms_News
    {

        /**
         * Constructor.
         */
        public function output()
        {
            $xml = Octopush_Sms_API::get_instance()->get_news();
            error_log("XMLCOCOT" . print_r($xml, true));
            if (!key_exists('error_code', (array)$xml) || $xml->error_code == '000') {
                //display information message if the language receive is different of your language
                if (!isset($xml->news)) {
                    echo '<div id="message" class="updated fade"><p><strong>' . __('No news available.', 'octopush-sms') . '</strong></p></div>';
                    return;
                }
                if (substr(get_bloginfo('language'), 0, 2) != $xml->news->new[0]->lang) {
                    echo '<div id="message" class="updated fade"><p><strong>' . __('News in your language are not available.', 'octopush-sms') . '</strong></p></div>';
                }
                //display each news
                echo '<ul>';
                foreach ($xml->news->new as $new) {
                    ?>
                    <li class="rss-widget">
                        <a class="rsswidget" href="http://octopush.com">
                            <?php echo str_replace(']]>', '', str_replace('<![CDATA[', '', $new->title)); ?>
                        </a>
                        &nbsp;<span
                                class="rss-date"><?php echo $new->date . ' ' . __('author', 'octopush-sms') . ': ' . $new->author; ?></span>
                        <div class="rssSummary">
                            <?php echo str_replace(']]>', '', str_replace('<![CDATA[', '', $new->text)); ?>
                        </div>
                    </li>
                    <?php
                }
                echo '</ul>';
            } else {
                echo Octopush_Sms_Admin::get_instance()->get_error_SMS($xml->error_code);
            }
        }

    }

endif;

return new Octopush_Sms_News();

