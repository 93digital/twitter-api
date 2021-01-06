<?php


/**
 *
 * Template Name: Twitter API
 *
 * Twitter API basic working example file
 *
 * 
 * There are basically two ways to use Twitter class
 * to retrieve Twitter data
 *
 * 1. - Get all the twitter data in a PHP array
 * 2. - Get all the twitter data wrapped in a
 *      ul HTML element. Optionally both a class
 *      for the ul element and a wrapper tag with custom
 *      class can be added. See working example below.
 *
 * Please notice that method 2 could return error strings if
 * twitter data is empty or the parameters passed are invalid.
 * Method 2 will return null if there is not data
 *
 * This file provides a working example of the Twitter API
 * class. Copy and paste this file in your /templates
 * folder and create a page using this template to display
 * a live testing page.
 *
 * Do not forget that Twitter class has to be included in 
 * your functions.php file, otherwise a PHP fatal error will
 * be tiggered.
 *
 * This file is supposed to be used in a WordPress Theme as 
 * a custom page template.
 *
 * @package       WordPress
 * @subpackage    templates
 * @author        93Digital <david@93digital.co.uk>
 *
 */

get_header();

// Method 1 - Retrieve array data
$twitter_data = Twitter::check_twitter_data();

?>

<div>
  <ul>
		
    <?php

      //Display tweets

      foreach( $twitter_data as $tweet ) {

        //Convert tweet array into object
        $tweet = Twitter::get_object( $tweet );

        /** 
        * Parse tweet text 
        * By default Tweeter API returns
        * raw text which does not displays URLs
        */
        $parsed_tweet_text = Twitter::parse_tweet_text( $tweet );

        echo '<li>' . $parsed_tweet_text .  '</li>';

      }

    ?>

  </ul>
</div>

<?php

//Method 2.1 - Retrieve HTML data.

$twitter_data = Twitter::return_html();
echo $twitter_data;

/*
This will print the following ouput :
    <ul class=''>
        <li>Tweet Content</li>
        <li>Tweet Content</li>
    </ul>
*/

//Method 2.2 - Retrieve HTML data with ul custom class

$twitter_data = Twitter::return_html( 'ul_class' );
echo $twitter_data;

/*
This will print the following ouput :
    <ul class="ul_class">
       <li>Tweet Content</li>
       <li>Tweet Content</li>
    </ul>
*/

//Method 2.3 - Retrieve HTML data with ul custom class and custom wrapper

$wrapper = array(
  'tag'   => 'div',     //Do not add brakets to tag name.
  'class' => 'wrapper_class'
);
$twitter_data = Twitter::return_html( 'ul_class', $wrapper );
echo $twitter_data;

/*
This will print the following output : 
    <div class="wrapper_class">
        <ul class="ul_class">
            <li>Tweet Content</li>
            <li>Tweet Content</li>
        </ul>
    </div>
*/

?>