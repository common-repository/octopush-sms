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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */
if (!defined('ABSPATH'))
{
	exit; // Exit if accessed directly
}

if (!class_exists('Octopush_Sms_Messages')) :

	/**
	 * Octopush_Sms_Messages
	 * Tab to configure the sms send in fonction of the events.
	 */
	class Octopush_Sms_Messages extends WC_Settings_Page
	{

		/**
		 * Constructor.
		 */
		public function __construct()
		{

		}

		/**
		 * Display tab and save setting if post data is received
		 */
		public function getBody()
		{
			//save is date is send
			$this->_post_process();
			WC_Admin_Settings::show_messages();
			//$defaultLanguage = (int) $this->context->language->id;

			$admin_html = '';
			//display messages for each possible admin hook
			foreach (Octopush_Sms_Admin::get_instance()->admin_config as $hookId => $hookName)
			{
				$admin_html .= $this->_get_code($hookId, $hookName, true);
			}

			$customer_html = '';
			foreach (Octopush_Sms_Admin::get_instance()->customer_config as $hookId => $hookName)
			{
				if ($hookId != 'action_order_status_update')
				{
					if ($hookId == 'action_validate_order' || $hookId == 'action_admin_orders_tracking_number_update')
					{
						$customer_html .= $this->_get_code($hookId, $hookName, false, null, true);
					}
					else
					{
						$customer_html .= $this->_get_code($hookId, $hookName, false);
					}
				}
				else
				{
					//specific hook when status of a command change
					global $wp_post_statuses;
					foreach ($wp_post_statuses as $key => $value)
					{
						if (strstr($key, 'wc-'))
						{
							//echo "$key => ".$value->label;//print_r($value,true);
							$customer_html .= $this->_get_code($hookId . "_$key", $hookName . " ($value->label)", false, null, true);
						}
					}
				}
			}

			$html = '
			<div id="' . get_class($this) . '">
				<br /><b>' . __('Edit and activate the SMS you want to start sending.', 'octopush-sms') . '</b><br /><br />';
			$html .=
				__("You'll see a preview on the right side.", 'octopush-sms') . '<br /><br />
			<div class="clear"></div>
				<div class="wrap woocommerce">
					<form id="' . get_class($this) . '_form" method="post">
						<input type="hidden" name="action" value="' . get_class($this) . '"/>
						<h3>' . __('SMS that will be sent to Admin', 'octopush-sms') . '</h3>
						<div class="os_row">' .
							$admin_html . '
						</div>
						<br />
						<input name="save" class="button-primary" type="submit" value="' . __('Update', 'octopush-sms') . '" />
						<br /><br />
						<h3>' . __('SMS that will be sent to users', 'octopush-sms') . '</h3>
						<div class="os_row">' .
							$customer_html . '
						</div>
						<br />
						<input class="button-primary" type="submit" name="save2" value="' . __('Update', 'octopush-sms') . '" class="button" />
						<input class="button-primary" type="submit" name="resettxt" value="' . __('Reset all messages', 'octopush-sms') . '" class="button" />
					</form>
				</div>
			</div>';

			return $html;
		}

		/**
		 * Get html fragment for this hook
		 * @param type $hookId the id of the hook
		 * @param type $hookName the short description of the hook
		 * @param type $bAdmin
		 * @param type $comment
		 * @param type $bPaid
		 * @return string
		 */
		private function _get_code($hookId, $hookName, $bAdmin = false, $comment = null, $bPaid = false)
		{
			//$defaultLanguage = (int)$this->context->language->id;
			//key option name for isactive
			$keyActive	 = Octopush_Sms_Admin::get_instance()->_get_isactive_hook_key($hookId, $bAdmin);
			//'octopush_sms_isactive_' . $hookId . (($bAdmin) ? '_admin' : '');
			//To test with dummy values
			$values		 = Octopush_Sms_API::get_instance()->get_sms_values_for_test($hookId);

			$code = '
		<div class="ows">
			<table class="messages_data">
				<tr valign="top" class="sms">
				<th scope="row" class="titledesc text_td">
					<label for="octopush_sms_email">' . __($hookName, 'octopush-sms');

			//if option is not free and the customer pay for it
			if ($bPaid && (int) get_option('octopush_sms_freeoption') == 0)
			{
				$code .= '<br/><span style="font-weight: normal">' . __('Sent only if customer pays the option', 'octopush-sms') . '</span>';
			}
			$code .= '<br/><input ' . (get_option($keyActive) == 1 ? 'checked' : '') . ' type="checkbox" name="' . $keyActive . '" value="1"/> <span style="font-weight:normal">' . __('Active', 'octopush-sms') . '</span><br/>';
			$code.='</label>
				</th>
				<td class="forminp forminp-' . $hookId . ' data_td"><br/>';
			//$code .= '<input ' . (get_option($keyActive) == 1 ? 'checked' : '') . ' type="checkbox" name="' . $keyActive . '" value="1"/> ' . __('Active', 'octopush-sms') . '<br/>';

			$key		 = Octopush_Sms_Admin::get_instance()->_get_hook_key($hookId, $bAdmin);
			$txt		 = Octopush_Sms_API::get_instance()->replace_for_GSM7(get_option($key) ? get_option($key) : Octopush_Sms_API::get_instance()->get_sms_default_text($hookId, $bAdmin));
			//TODO test
			$txt_test	 = Octopush_Sms_API::get_instance()->replace_for_GSM7(str_replace(array_keys($values), array_values($values), $txt));
			$bGSM7		 = Octopush_Sms_API::get_instance()->is_GSM7($txt_test);

			$code .= '<textarea name="' . $key . '" rows="4" class="message_textarea">' . $txt
				. '</textarea>
								 <br/><span class="description">' .
				(!$bGSM7 ? '<img src="../img/admin/warning.gif"> ' . __('This message will be divided in 70 chars parts, because of non standard chars : ', 'octopush-sms') . ' ' . Octopush_Sms_API::get_instance()->not_GSM7($txt_test) : __('This message will be divided in 160 characters parts', 'octopush-sms')) .
				'</span>'
				. '<br/>';
			$code.= '<span class="description">' . __('Variables you can use : ', 'octopush-sms') . ' ' . implode(', ', array_keys($values)) . '</span>								
					 </td>
					 <td class="forminp forminp-' . $hookId . '-example" class="text_td">'.
				__('Preview', 'octopush-sms').'<br /><textarea class="check" readonly rows="4" class="message_textarea">' . $txt_test . '</textarea>';
			$code .= '</td>
			</tr>
			</table>
			</div>';
			//no mulitlangual supprot
			return $code;
		}

		/**
		 * Update option corresponding to the hook
		 * @param type $hook
		 * @param type $b_admin
		 */
		public function update_message_option($hook, $b_admin = false)
		{
			//if is active
			$hook_is_active = Octopush_Sms_Admin::get_instance()->_get_isactive_hook_key($hook, $b_admin);
			if (array_key_exists($hook_is_active, $_POST))
			{
				$value = wc_clean($_POST[$hook_is_active]);
				//save the option
				update_option($hook_is_active, (int) $value);
			}
			else
			{
				update_option($hook_is_active, 0);
			}
			//message text
			$hook_key = Octopush_Sms_Admin::get_instance()->_get_hook_key($hook, $b_admin);
			if (array_key_exists($hook_key, $_POST))
			{
				$value = stripslashes($_POST[$hook_key]);
				//save the option
				update_option($hook_key, Octopush_Sms_API::get_instance()->replace_for_GSM7(trim($value)));
			}
			//specific case of 'action_order_status_update'
			if ($hook == 'action_order_status_update')
			{
				global $wp_post_statuses;
				foreach ($wp_post_statuses as $key => $value)
				{
					if (strstr($key, 'wc-'))
					{
						$this->update_message_option($hook . "_$key", $b_admin);
					}
				}
			}
		}

		/**
		 * Save data
		 */
		private function _post_process()
		{
			//. __('Reset all messages', 'octopush-sms')
			//if update value
			if (array_key_exists('save', $_POST) || array_key_exists('save2', $_POST))
			{
				foreach (Octopush_Sms_Admin::get_instance()->admin_config as $hook => $hookName)
				{
					$this->update_message_option($hook, true);
				}
				foreach (Octopush_Sms_Admin::get_instance()->customer_config as $hook => $hookName)
				{
					$this->update_message_option($hook, false);
				}
			}

			//if reset value
			if (array_key_exists('resettxt', $_POST))
			{
				foreach (Octopush_Sms_Admin::get_instance()->admin_config as $hook => $hookName)
				{
					//message text
					$hook_key = Octopush_Sms_Admin::get_instance()->_get_hook_key($hook, true);
					update_option($hook_key, Octopush_Sms_API::get_instance()->replace_for_GSM7(Octopush_Sms_API::get_instance()->get_sms_default_text($hook, true)));
				}
				foreach (Octopush_Sms_Admin::get_instance()->customer_config as $hook => $hookName)
				{
					//message text
					if ($hook != 'action_order_status_update')
					{
						$hook_key = Octopush_Sms_Admin::get_instance()->_get_hook_key($hook, false);
						update_option($hook_key, Octopush_Sms_API::get_instance()->replace_for_GSM7(Octopush_Sms_API::get_instance()->get_sms_default_text($hook)));
					}
					else
					{
						//specific hook when status of a command change
						global $wp_post_statuses;
						foreach ($wp_post_statuses as $key => $value)
						{
							if (strstr($key, 'wc-'))
							{
								//echo "$key => ".$value->label;//print_r($value,true);
								$hook_status = $hook . "_$key";
								$hook_key	 = Octopush_Sms_Admin::get_instance()->_get_hook_key($hook_status, false);
								update_option($hook_key, Octopush_Sms_API::get_instance()->replace_for_GSM7(Octopush_Sms_API::get_instance()->get_sms_default_text($hook_status)));
							}
						}
					}
				}
			}
		}

	}

	endif;

return new Octopush_Sms_Messages();

