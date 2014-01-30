<?php
/*
 * Plugin Name:  Search Terms
 * Version:      1.0.0
 * Description:  Complement to WAC search to store keywords on the DB
 * Author:       Alejandro Muñoz Odero
 * Author URI:   http://www.fubra.com
 * License:      GPL-2.0+
 * License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI:  fubralimited/wp-plugin-comment-subject
 * GitHub Branch:      master
*/


// Dependencies
require 'vendor/autoload.php';
require_once 'class-adminlog.php';


// Actions
add_action('admin_menu', 'report_menu');

// Register Hooks
register_activation_hook( __FILE__, 'search_terms_plugin_activate' );
register_deactivation_hook( __FILE__, 'search_terms_plugin_deactivate' );



function search_terms_plugin_activate() {

  global $wpdb;

  $table_name = $wpdb->prefix . 'search_terms';

  if( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table_name .  '"' ) !== $table_name ) {

    $sql = '
      CREATE TABLE `' . $table_name .  '` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `keyword` VARCHAR(255) NOT NULL DEFAULT "",
        `browser` VARCHAR(255) DEFAULT NULL,
        `results` INT(11) DEFAULT NULL,
        `location` VARCHAR(255) DEFAULT NULL,
        `date` VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (`id`)
      )
    ';
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $wpdb->query( $sql );

  }

}


function search_terms_plugin_deactivate() {

  global $wpdb;

  $table_name = $wpdb->prefix . 'search_terms';

  if( $wpdb->get_var( 'SHOW TABLES LIKE "' . $table_name . '"' ) === $table_name ) {

    $sql = 'DROP TABLE `' . $table_name .  '`';
    $wpdb->query( $sql );

  }

}


/**
* Get the keyword from the search and store it
*
* @param string $keyword It's the search string introduced by the user
* @param int $num_result The number of results returned by the search
**/
function get_search_term($keyword, $num_result) {

  // table to work with 'wac_wp_search_terms'
  global $wpdb;

  try {
    $location = get_location($_SERVER['REMOTE_ADDR']);
  }
  catch (Exception $e) {}

  $cont = array();
  $cont['keyword']  = $keyword;
  $cont['results']  = $num_result;
  $cont['browser']  = get_user_agent($_SERVER['HTTP_USER_AGENT']);
  $cont['location'] = $location;
  $cont['date']     = date('d F Y \- H:i:s');

  $sql = $wpdb->prepare( '
    INSERT INTO '.$wpdb->prefix.'search_terms'.'(keyword, browser, results, location, date)
    VALUES ("%s", "%s",%s, "%s", "%s")',
    $cont['keyword'], $cont['browser'], $cont['results'], $cont['location'], $cont['date']
  );

  $wpdb->query($sql);

}

/**
* Call to uaparser module to get the browser and it's version
*
* @param array $agent Server user agent
* @return string Browser name + version
**/
function get_user_agent($agent){

  $parser = new \UA();
  $result = $parser->parse($agent);
  $browser = $result->ua->toString;

  return $browser;

}

/**
* Call to geocoder module to get the geolocation of the user
*
* @param int $ip Used to transform stone into gold... wait, no, it's just an IP
* @return string Location being "<City> - <Country>" or 403 if hitted max API connections
**/
function get_location($ip){

  $adapter = new \Geocoder\HttpAdapter\CurlHttpAdapter();
  $geocoder = new \Geocoder\Geocoder();
  $provider = '\Geocoder\Provider\FreeGeoIpProvider';
  $geocoder->registerProviders(
    array(
      new $provider($adapter)
    )
  );
  $geo_object = $geocoder->geocode($ip);
  $location = $geo_object->getCity()." - ".$geo_object->getCountry();

  return $location;

}

/**
* Adds a link on the admin menu and calls the function to show the results
**/
function report_menu() {

  // Get and execute CSV function here to send headers properly
  if(isset($_GET["csv"])) {
    export_csv();
  }

  add_menu_page(
    'Searched keywords',
    'Searched keywords',
    'edit_pages',
    'report_menu',
    function() {

      echo '<div class="wrap">';

        echo '<h2>Searched Keywords</h2>';
        new Keyword_WP_List_Table();

      echo '</div>';

    },
    plugins_url( 'traffic_16.png', __FILE__ )
  );

}

/**
* Exports the whole table into a CSV file
**/
function export_csv() {

  //$now = getdate();
  $filexport = 'keywords_'.date('dmY\_His').'.csv';

  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header('Content-Description: File Transfer');
  header("Content-type: text/csv");
  header("Content-Disposition: attachment; filename={$filexport}");
  header("Expires: 0");
  header("Pragma: public");

  $fh = @fopen( 'php://output', 'w' );

  global $wpdb;

  $query = 'SELECT * FROM '.$wpdb->prefix.'search_terms ORDER BY id DESC LIMIT 30000';

  $results = $wpdb->get_results( $query, ARRAY_A );
//var_dump($results);die();
  $headers = false;

  foreach ($results as $data) {

    // Add a header row if it hasn't been added yet
    if ( !$headers ) {

      // Use the keys from $data as the titles
      fputcsv($fh, array_keys($data));
      $headers = true;

    }

    // Add data into the stream
    fputcsv($fh, $data);

  }

  fclose($fh);

  exit();

}

