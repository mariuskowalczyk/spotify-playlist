<?php
/*
Plugin Name: lukes spotify playlist
Plugin URI: 
Description: Shows a Spotify playlist in main page 
Author: lukedimewalker
Author URI: 
*/

add_action('admin_init', 'luke_sampleoptions_init' );
add_action('admin_menu', 'luke_sampleoptions_add_page');

// Init plugin options to white list our options
function luke_sampleoptions_init(){
	register_setting( 'luke_sampleoptions_options', 'luke_sample', 'luke_sampleoptions_validate' );
}

// Add menu page
function luke_sampleoptions_add_page() {
	add_options_page('Spotify Credentials', 'Spotify Credentials', 'manage_options', 'luke_sampleoptions', 'luke_sampleoptions_do_page');
}

// Draw the menu page itself
function luke_sampleoptions_do_page() {
	?>
	<div class="wrap">
		<h2>Spotify Credentials</h2>
		<form method="post" action="options.php">
			<?php settings_fields('luke_sampleoptions_options'); ?>
			<?php $options = get_option('luke_sample');
			      if($options) {
			         $sometext = $options['sometext'];
			         $othertext = $options['othertext'];
                  } else {
			          $sometext = '';
			          $othertext = '';
                  }
			?>

			<table class="form-table">
				<tr valign="top"><th scope="row">Client Id</th>
					<td><input type="text" name="luke_sample[sometext]" value="<?php echo $sometext; ?>" /></td>
				</tr>
                <tr valign="top"><th scope="row">Client Secret</th>
                    <td><input type="text" name="luke_sample[othertext]" value="<?php echo $othertext; ?>" /></td>
                </tr>
			</table>
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
	</div>
	<?php	
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function luke_sampleoptions_validate($input) {
	//
	$input['sometext'] =  wp_filter_nohtml_kses($input['sometext']);
    $input['othertext'] =  wp_filter_nohtml_kses($input['othertext']);

	return $input;
}

add_shortcode('spotify', 'spotify_shortcode');
function spotify_shortcode( $atts = [], $content = null) {
    //
    $content = "";
    $options = get_option('luke_sample');

    $client_id ='';
    $client_secret = '';
    if ($options) {
        $client_id = $options['sometext'] ? $options['sometext'] : '';
        $client_secret = $options['othertext'] ? $options['othertext'] : '';
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,            'https://accounts.spotify.com/api/token' );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($ch, CURLOPT_POST,           1 );
    curl_setopt($ch, CURLOPT_POSTFIELDS,     'grant_type=client_credentials' );
    curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Authorization: Basic '.base64_encode($client_id.':'.$client_secret)));

    $result=curl_exec($ch);
    $erg = json_decode($result);

    if (strpos($result, 'error') == false) {
        $token = $erg->access_token;
    } else {
        $token = '';
    }

    if ($token) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.spotify.com/v1/browse/featured-playlists');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json", "Content-Type: application/json", "Authorization: Bearer " . $token));

        $result = curl_exec($ch);
        $erg = json_decode($result);
        if ($erg) {
            $playlists = $erg->playlists->items;
            foreach ($playlists as $pl) {
                $content .= "<h4>" . $pl->description . "</h4>";
                $content .= "<center><img src=" . $pl->images[0]->url . " width=100px height=100px></img></center>";
            }
        }
    }
    // always return
    return $content;
}
