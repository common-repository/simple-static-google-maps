<?php
/*
Plugin Name: Simple Static Google Maps
Plugin URI: https://wordpress.org/plugins/simple-static-google-maps/
Description: Simple Static Google Map automatically adds a map snapshot if an address is entered in the Static Map post meta box.
Version: 2.1
Author: Garrett Grimm
Author URI: http://grimmdude.com
*/

if ( ! class_exists('SimpleStaticGoogleMaps')) {
	class SimpleStaticGoogleMaps
	{

		public function __construct()
		{
			add_action('save_post', array($this, 'metaBoxSave'));
			add_action('add_meta_boxes', array($this, 'metaBox'));
			add_action('the_content', array($this, 'insertMap'));
		}


		// Function to handle meta box inputs upon save
		public function metaBoxSave()
		{
			// verify if this is an auto save routine.
			// If it is our form has not been submitted, so we dont want to do anything
			if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
				return;
			}

			// verify this came from the our screen and with proper authorization,
			// because save_post can be triggered at other times

			if ( ! wp_verify_nonce( $_POST['static_map_meta_nonce'], plugin_basename(__FILE__))) {
				return;
			}

			// Check permissions
		 	if ('page' === $_POST['post_type']) {
			    if ( ! current_user_can( 'edit_page', $post_id)) {
					return;
				}

			} else {
				if ( ! current_user_can( 'edit_post', $post_id)) {
					return;
				}
			}
			// OK, we're authenticated: we need to find and save the data

			// Add or update custom field
			update_post_meta($_POST['post_ID'], 'static_map_address', $_POST['static_map_address']);
		}


		//Main Function to Add Map
		public function insertMap($content)
		{
			global $post;

			if($address = get_post_meta($post->ID, 'static_map_address', true))
			{
				if (empty($address)) {
					return $content;

				} elseif (is_array($address)) {
					// Handle the new way of storing meta data
					if (empty($address['address'])) {
						return $content;

					} else {
						$address = urlencode($address['address'].','.$address['city'].','.$address['state'].','.$address['zip']);
					}
				}

				return $content.'<div class="listing-static-map">
				<a href="https://maps.google.com/maps?q='.$address.'" target="_blank""><img src="http://maps.google.com/maps/api/staticmap?center='.$address.'
				&zoom=14&size=350x250&maptype=roadmap&markers=color:blue|label:S|40.702147,-74.015794&markers=color:green|label:A|'.$address.'
				&markers=color:red|color:red|label:C|40.718217,-73.998284&sensor=false" /></a>
				</div>';

			} else {
				return $content;
			}
		}


		public function metaBox()
		{
			add_meta_box(
				'static_map_meta_box',
				'Static Google Map',
				array($this, 'metaBoxContent'),
				NULL,
				'side'
			);
		}


		// Meta box contents
		public function metaBoxContent($post)
		{
			$address = get_post_meta($post->ID, 'static_map_address', TRUE);
			?>
			<table>
				<tbody>
					<tr>
						<td>
							<label for="static_map_address">Address</label>
						</td>
						<td>
							<input type="text" name="static_map_address[address]" value="<?php echo is_array($address) ? $address['address'] : $address; ?>" id="static_map_address" />
						</td>
					</tr>
					<tr>
						<td>
							<label for="static_map_city">City</label>
						</td>
						<td>
							<input type="text" name="static_map_address[city]" value="<?php echo is_array($address) ? $address['city'] : ''; ?>" id="static_map_city" />
						</td>
					</tr>
					<tr>
						<td>
							<label for="static_map_state">State</label>
						</td>
						<td>
							<input type="text" name="static_map_address[state]" value="<?php echo is_array($address) ? $address['state'] : ''; ?>" id="static_map_state" />
						</td>
					</tr>
					<tr>
						<td>
							<label for="static_map_zip">Zip</label>
						</td>
						<td>
							<input type="text" name="static_map_address[zip]" value="<?php echo is_array($address) ? $address['zip'] : ''; ?>" id="static_map_zip" />
						</td>
					</tr>
				</tbody>
			</table>
			<p>
				Map will appear only if something is entered in the address field.
			</p>
			<p>
				<a href="https://wordpress.org/support/plugin/simple-static-google-maps" target="_blank">Feedback/Support</a>
			</p>
			<?php
			wp_nonce_field( plugin_basename(__FILE__), 'static_map_meta_nonce' );
		}

	}

	new SimpleStaticGoogleMaps;
}
