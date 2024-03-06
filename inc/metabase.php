<?php

/* Firebase JWT, necessary for the metabase url generation */
require get_stylesheet_directory() . '/vendor/php-jwt-6.10.0/src/JWT.php';

use \Firebase\JWT\JWT;

// Function to generate Metabase iframe URL
function museusbr_generate_metabase_iframe_url($dashboard_id = 2) {

    // Constants
    $METABASE_SITE_URL = 'https://metabase.museus.gov.br';
    $METABASE_SECRET_KEY = 'e53d976169ad61827e18f85aee623da9da22a623b9477da67069a9d401c46e6e';

    // Define the payload for the JWT token
    $payload = array(
        "resource" => array("dashboard" => $dashboard_id),
        "params" => (object)[], // Use instead of array(), as noted here: https://github.com/metabase/metabase/issues/11101#issuecomment-540147397
        "exp" => time() + (10 * 60) // 10 minute expiration
    );

    // Generate the JWT token
    $token = JWT::encode($payload, $METABASE_SECRET_KEY, 'HS256');

    // Construct the iframe URL
    $iframeUrl = $METABASE_SITE_URL . "/embed/dashboard/" . $token . "#theme=null&bordered=false&titled=false";

    // Output the iframe URL
    return $iframeUrl;
}

// Function to generate Metabase iframe shortcode
function museusbr_generate_metabase_iframe_shortcode($atts) {

    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'dashboard' => 2,
    ), $atts);

    // Obtain the iframe URL
    $iframeUrl = museusbr_generate_metabase_iframe_url();
    $iframeHTML = '<iframe id="metabase-iframe" src="' . $iframeUrl . '" frameborder="0" width="100%" height="2720px" allowtransparency></iframe>';

    // Home dashboard
    if ( $atts['dashboard'] == 3 ) {
        $iframeUrl = museusbr_generate_metabase_iframe_url(3);
        $iframeHTML = '<iframe id="metabase-iframe" src="' . $iframeUrl . '" frameborder="0" width="100%" height="860px" allowtransparency></iframe>';
    }

    // Output the iframe from the shortcode
    return $iframeHTML;
}
add_shortcode('metabase_iframe', 'museusbr_generate_metabase_iframe_shortcode');