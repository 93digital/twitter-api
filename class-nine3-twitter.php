<?php

/**
 *
 * Twitter
 *
 * Twitter API library. It connects with the Twitter DB trought the Twitter API.
 * This class allows you to retrieve a finite number of tweets and retweets from 
 * the user twitter account. The data retreived is stored in wp_options.
 * Data is updated every 5 minutes. This is a static class therefore does not have
 * to be instantiated.
 *
 * How to use :
 *
 * -> In extras.php call the init class method adding the following code :
 *    Twitter::init();
 *
 * -> In admin menu, go to Appaerance -> Theme settings
 * 
 * -> Go to Twitter API tab
 *
 * -> Fill the form with the client data. You should be able to get the client
 *    twitter data from Twitter APP.
 *
 * -> Check file 'usage_example.php' located in this repository for
 *    a full working example and the repository readme for further information.
 *
 *
 * @package           WordPress
 * @subpackage        inc
 * @author            93Digital <david@93digital.co.uk>
 *
 */
class Twitter {


      /**
      * Init method
      * 
      * Creates the settings panel options
      *
      * Settings panel options will be the following :
      * 
      * 1. - Oauth access token
      * 2. - Oauth access token secret
      * 3. - Consumer key
      * 4. - Consumer key secret
      * 5. - User screen name ( without @ )
      * 
      * @return void
      */
      public static function init() {
        add_filter( 'nine3_settings_tabs', array( 'Twitter', 'add_settings_tab' ), 99 );
        add_filter( 'nine3_settings', array( 'Twitter', 'add_settings_fields' ), 99 );
      }

      /**
      * Set settings field tab.
      *
      * @return array $tabs
      */
      public static function add_settings_tab( $tabs ) {
        $tabs['twitter_api'] = 'Twitter API';

        return $tabs;
      }

      /**
      * Set settings fields
      *
      * @return array $settings
      */
      public static function add_settings_fields( $fields ) {  
        $fields['twitter_api'] = array(
          'twitter_oauth_access_token' => array(
            'name' => esc_html__( 'Oauth Access Token' ),
            'desc' => '',
            'type' => 'text'
          ),
          'twitter_oauth_access_token_secret' => array(
            'name' => esc_html__( 'Oauth Access Token Secret' ),
            'desc' => '',
            'type' => 'text'
          ),
          'twitter_consumer_key' => array(
            'name' => esc_html__( 'Consumer Key' ),
            'desc' => '',
            'type' => 'text'
          ),
          'twitter_consumer_key_secret' => array(
            'name' => esc_html__( 'Consumer Key Secret' ),
            'desc' => '',
            'type' => 'text'
          ),
          'twitter_user_screen_name' => array(
            'name' => esc_html__( 'User Screen Name ( no @ )' ),
            'desc' => '',
            'type' => 'text'
          ),
          'twitter_number_of_tweets' => array(
            'name' => esc_html__( 'Number of tweets retrieved' ),
            'desc' => '',
            'type' => 'text',
          ),
          'twitter_refresh_time' => array(
            'name' => esc_html__( 'Minutes after each refresh' ),
            'desc' => 'Use a natural positive number. Twitter has a limit of requests per day, so an high refresh rate might overpass the limit. If that happnes, nothing will be displayed.',
            'type' => 'text',
          ),
        );
        
        return $fields;
      }

     /**
      * Returns all the user data to be used by internal class methods
      * Change the values with the client ones.
      * Do never declare this method public as it contains sensible user
      * data.
      *
      * @return object $user_data
      */
      private static function user_data() {
        $user_data                 = new stdClass();
        $default_number_of_posts  = 5;
        $defaul_refresh_time      = 5;

        $user_data->oauth_access_token        = nine3_get_option( 'twitter_oauth_access_token' );
        $user_data->oauth_access_token_secret = nine3_get_option( 'twitter_oauth_access_token_secret');
        $user_data->consumer_key              = nine3_get_option( 'twitter_consumer_key' );
        $user_data->consumer_secret           = nine3_get_option( 'twitter_consumer_key_secret' );
        $user_data->number_of_posts           = nine3_get_option( 'twitter_number_of_tweets' );
        $user_data->number_of_posts           = ( ! empty( $user_data->number_of_posts ) && Is_Numeric( $user_data->number_of_posts ) && $user_data->number_of_posts > 0 ) ? $user_data->number_of_posts : $default_number_of_posts;
        $user_data->refresh_time              = nine3_get_option( 'twitter_refresh_time' );
        $user_data->refresh_time              = ( ! empty( $user_data->refresh_time ) && Is_Numeric( $user_data->refresh_time ) && $user_data->refresh_time > 0 ) ? $user_data->refresh_time : $defaul_refresh_time;

        //Do not include the '@' in user name.
        $user_data->screen_name               = nine3_get_option( 'twitter_user_screen_name' );

        return $user_data;
      }

      /**
       * Builds base URL to retrieve data
       * 
       * @param  string $baseURI Base url.
       * @param  string $method Method used to retrieve the data.
       * @param  array  $params Params.
       * @return string
       */
      private static function build_base_string( $baseURI, $method, $params ) {
        $r = array();
        ksort( $params );

        foreach ( $params as $key => $value ) {
          $r[] = "$key=" . rawurlencode( $value );
        }

        return $method . '&' . rawurlencode( $baseURI ) . '&' . rawurlencode( implode( '&', $r ) );
      }

      /**
      * Build authorization header request
      *
      * @param array $oauth Authorization.
      * @return string $r
      */
      private static function build_authorization_header( $oauth ) {
        $r = 'Authorization: OAuth ';

        $values = array();

        foreach ( $oauth as $key => $value ) {
          $values[] = "$key=\"" . rawurlencode( $value ) . '"';
        }

        $r .= implode( ', ', $values );

        return $r;
      }

      /**
       * This method gets the backup twitter data from the database.
       *
       * @return array $twitter_data Twitter data coming from the database
       */
       private static function get_back() {
        // gettting serialized twitter data
        $twitter_data     = get_option( 'twitter_data' );

        // unserialize
        $un_twitter_data  = unserialize( $twitter_data );

        return $un_twitter_data;
      }

      /**
       * This method refresh the backup database.
       *
       * @param  object $tweets Tweets data coming from the API.
       * @return void
       */
       private static function refresh_back( $tweets ) {
          $current_date = new DateTime();

          // update tweets option
          update_option( 'twitter_data', serialize( $tweets ) );

          //update last time call done
          update_option( 'twitter_last_call', $current_date->format( 'Y-m-d H:i:s' ) );
      }

      /**
       * This method gets a twit object ready to be stored on the database.
       *
       * @param  object $twit_element object.
       * @return object $parsed_tweet_object
       */
      private static function get_parsed_tweet( $twit_element ) {
        $parsed_tweet = new stdClass();
        $twit = self::get_object( $twit_element );

        $parsed_tweet->user_name      = $twit->user['name'];
        $parsed_tweet->user_url       = $twit->user['url'];
        $parsed_tweet->user_screename = $twit->user['screen_name'];

        $parsed_tweet->date           = date( 'd F, Y', strtotime( $twit->created_at ) );
        $parsed_tweet->url            = $twit->entities['urls'][0]['url'];

        // parsing twit text.
        $parsed_tweet->twit_text      = self::parse_tweet_text( $twit );

        return $parsed_tweet;
      }

      /**
      * Retrieves tweet data from Twitter API.
      *
      * @return object
      */
      private static function return_tweet() {
        $user_data                  = self::user_data();
        $oauth_access_token         = $user_data->oauth_access_token;
        $oauth_access_token_secret  = $user_data->oauth_access_token_secret;
        $consumer_key               = $user_data->consumer_key;
        $consumer_secret            = $user_data->consumer_secret;
        $twitter_timeline           = 'user_timeline';  // mentions_timeline / user_timeline / home_timeline / retweets_of_me

        $request = array(
          'screen_name'               => $user_data->screen_name,
          'count'                     => $user_data->number_of_posts,
        );

        $oauth = array(
          'oauth_consumer_key'        => $consumer_key,
          'oauth_nonce'               => time(),
          'oauth_signature_method'    => 'HMAC-SHA1',
          'oauth_token'               => $oauth_access_token,
          'oauth_timestamp'           => time(),
          'oauth_version'             => '1.0',
        );

        // merge request and oauth to one array.
        $oauth = array_merge( $oauth, $request );
        $base_info                    = self::build_base_string( "https://api.twitter.com/1.1/statuses/$twitter_timeline.json", 'GET', $oauth );
        $composite_key                = rawurlencode( $consumer_secret ) . '&' . rawurlencode( $oauth_access_token_secret );
        $oauth_signature              = base64_encode( hash_hmac( 'sha1', $base_info, $composite_key, true ) );
        $oauth['oauth_signature']     = $oauth_signature;

        // request data from 
        $header  = array( self::build_authorization_header( $oauth ), 'Expect:' );
        $options = array(
          CURLOPT_HTTPHEADER          => $header,
          CURLOPT_HEADER              => false,
          CURLOPT_URL                 => "https://api.twitter.com/1.1/statuses/$twitter_timeline.json?" . http_build_query( $request ),
          CURLOPT_RETURNTRANSFER      => true,
          CURLOPT_SSL_VERIFYPEER      => false,
        );

        $feed = curl_init();
        curl_setopt_array( $feed, $options );
        $json = curl_exec( $feed );
        curl_close( $feed );

        return json_decode( $json, true );
      }

  /**
   * This method removes the string url at the end of the twit and adds a link tag.
   *
   * @param  string $tweet Tweet to be modified on the sidebar.
   * @param  int    $have_link Starting link position on the tweet string.
   * @return string $parsed_tweet Tweet with the link appended
   */
  private static function add_last_link( $tweet, $have_link ) {
    $parsed_tweet = $tweet;
    $url          = substr( $tweet, $have_link );

    // building link tag.
    $tag          = "<a href='" . $url . "' target='_blank'>" . $url . '</a>';
    $parsed_tweet = str_replace( $url, $tag, $tweet );

    return $parsed_tweet;
   }

   /**
    * This method parses the twit text to display all links, hanstags, etc
    * http://stackoverflow.com/questions/11533214/php-how-to-use-the-twitter-apis-data-to-convert-urls-mentions-and-hastags-in
    *
    * @param  object  $tweet Tweet object.
    * @param  boolean $links Wheter to parse links. Default : true.
    * @param  boolean $users Wheter to parse users. Default : true.
    * @param  boolean $hashtags Wheter to parse hashtags. Default : true.
    * @return string $return
    */
  public static function parse_tweet_text( $tweet, $links = true, $users = true, $hashtags = true ) {
    $return   = $tweet->text;
    $entities = array();

    if ( $links && is_array( $tweet->entities['urls'] ) ) {\
      foreach ( $tweet->entities['urls'] as $e ) {
        $temp['start']         = $e['indices'][0];
        $temp['end']           = $e['indices'][1];
        $temp['replacement']   = "<a href='" . $e['expanded_url'] . "' target='_blank'>" . $e['display_url'] . '</a>';
        $entities[]            = $temp;
      }
    }

    if ( $users && is_array( $tweet->entities['user_mentions'] ) ) {
      foreach ( $tweet->entities['user_mentions'] as $e ) {
        $temp['start']          = $e['indices'][0];
        $temp['end']            = $e['indices'][1];
        $temp['replacement']    = "<a href='https://twitter.com/" . $e['screen_name'] . "' target='_blank'>@" . $e['screen_name'] . '</a>';
        $entities[]             = $temp;
      }
    }

    if ( $hashtags && is_array( $tweet->entities['hashtags'] ) ) {
      foreach ( $tweet->entities['hashtags'] as $e ) {
        $temp['start']          = $e['indices'][0];
        $temp['end']            = $e['indices'][1];
        $temp['replacement']    = "<a href='https://twitter.com/hashtag/" . $e['text'] . "?src=hash' target='_blank'>#" . $e['text'] . '</a>';
        $entities[]             = $temp;
      }
    }

    usort($entities, function( $a, $b ) {
      return($b['start'] -$a['start']);
    });

    /*
     * TODO Fix me! Check if is there any other better solution for this workaround.
     * The original twitter ends with:
     * https...
     *
     * and the foreach code doesn't replace the string "https", but only the 3 dots.
     * So, the workaround is removing the 'https' text as well.
     *
     */
     $https          = iconv( 'utf-8', 'ascii//TRANSLIT', substr( $return, -8 ) );
     $end_with_https = $https === 'https...';

     if ( $end_with_https ) 
      $return = substr( $return, 0, strlen( $return ) - 8 );
      

      foreach ( $entities as $item ) {
        // substr_replace is not multibyte (utf-8) compatible.
        $return = self::utf8_substr_replace( $return, $item['replacement'], $item['start'], $item['end'] - $item['start'] );
        // $return = substr_replace($return, $item["replacement"], $item["start"], $item["end"] - $item["start"]);
      }

      // adding the link at the end of twit if required.
      $have_link = strrpos( $return, 'https://t.co/' );

      if ( $have_link !== false ) {
        $return = self::add_last_link( $return, $have_link );
      }

      return $return;
    }

    /**
     * A UTF-8 Aware substr_replace
     *
     * This code is used by self::parse_tweet_text because substr_replace
     * is not multibyte (utf-8) compatible, and this cause a mess in the output string,
     * as the indices doesn't match, when using spanish accented letter.
     *
     * The following function instead is utf-8 awer, and so the replacement is made
     * correctly.
     * 
     * https://shkspr.mobi/blog/2012/09/a-utf-8-aware-substr_replace-for-use-in-app-net/
     *
     * @param  string $original Original.
     * @param  string $replacement Replacement.
     * @param  int    $position Position.
     * @param  int $length Length.
     * @return string $out
     */
     private static function utf8_substr_replace( $original, $replacement, $position, $length ) {

      $startString = mb_substr( $original, 0, $position, 'UTF-8' );
      $endString   = mb_substr( $original, $position + $length, mb_strlen( $original ), 'UTF-8' );

      $out = $startString . $replacement . $endString;

      return $out;
    }

  /**
   * This method creates and sets the twitter refresh time.
   *
   * @return $tweets
   */
  private static function handle_twitter() {
    $tweets       = '';
    $call_api     = self::check_retrieve_from_api();

    if ( $call_api ) {
      $tweets = self::get_twitter_data();

      // rewritting database.
      self::refresh_back( $tweets );
    } else {
      $tweets = self::get_back();
    }
    
    return $tweets;
  }

  /**
   * This method returns all the twitter data
   *
   * @return object $tweets JSON object with all the twitter data coming from Twitter API
   */
  private static function get_twitter_data() {
    $tweets  = self::return_tweet();
    return ( object ) $tweets;
  }

  /**
   * This method wrappers the get twttiter data
   *
   * @return object $tweets
   */
  public static function check_twitter_data() {
     $handler = self::handle_twitter();
     return $handler;
  }

   /**
    * This function gets an object based on a array.
    *
    * @param array $array Array to be converted
    * @return object
    */
   public static function get_object( $array ) {
     return (object) $array;
   }

   /**
   * This method checks how long the Twitter
   * data was retreived from the API. If it was
   * more than 5 minutes then an API call has to
   * be done again
   * 
   * If we have to retrieve data from API this method
   * returns true, otherwise it returs false
   *
   * @return boolean
   */
  private static function check_retrieve_from_api() {
    $current_date       = new DateTime();
    $last_call_date     = get_option( 'twitter_last_call');
    $user_data          = self::user_data();
    $minutes_after_call = $user_data->refresh_time;

    if ( ! $last_call_date ) {
      return true;
    }

    $since = $current_date->diff( new DateTime( $last_call_date ) );

    if ( $since->i > $minutes_after_call ) {
      return true;
    }

    return false;
  }

   /**
   * Retrieves twitter data wrapped by HTML. This method is intended for
   * basic usage
   *
   * @param string $ul_class Class for ul element.
   * @param array $wrapper Ul optional wrapper. Array should be the following format:
   *        key1 = tag ( wrapper_tag ) , key2 = class ( wrapper class )
   *        Tag has to be the name , not the HTML tag.
   *        Example : $wrapper = array( 'tag' => 'div', 'class' => 'main')
   * @return string $html
   */
  public function return_html( $ul_class = '', $wrapper = array( 'tag' => '', 'class' => '' ) ) {
     $html         = '';
     $twitter_data = self::check_twitter_data();

     if( !array_key_exists( 'tag', $wrapper ) || !array_key_exists( 'class', $wrapper ) ) {
      return 'Invalid wrapper array keys';
     }

     $wrapper = self::get_object( $wrapper );

     //add wrapper tag if required.
     if ( $wrapper->tag !== '' ) {
       $html = "<" . $wrapper->tag . " class='" . $wrapper->class . "'>";
     }

     // add twitter data to string if there is twitter data available.
     if ( is_object( $twitter_data ) && count( $twitter_data ) > 0 ) {

      $html .= "<ul class='" . $ul_class . "'>";

      foreach ( $twitter_data as $tweet ) {

        $tweet             = self::get_object( $tweet );
        $parsed_tweet_text = self::parse_tweet_text( $tweet );

        $html .= "<li>" . $parsed_tweet_text . "</li>";

      }

      $html .= "</ul>";

      //close wrapper tag if requried
      if( $wrapper->tag !== '' )
        $html .= "</" . $wrapper->tag . ">";
     } else {
      return 'No twitter data has been retreived';
     }

     return $html;
   }

}
