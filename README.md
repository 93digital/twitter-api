# Twitter Class
<br/>
Twitter API library which retreives a limited amount of tweets,
answers and retweets from a given user twitter account.

The data retreived is stored in **wp_options** as a serializez
string data.

Data is updated every **5 minutes** but the time the data is updated
can be changed. It is not recommended to set update data to 0 because
Twitter has restrictions about API abusive call rates.

This is a static class therefore does not have to be instantiated.

User Twitter APP data can be set in Admin menu -> Appaerance -> Theme settings.


## How to use
<br/>

- In extras.php add the following line of code :
  ```php
  <?php
    Twitter::init();
  ?>
  ```

- Log in into your project's **admin panel**. Then open **Appareance -> Theme settings** in the lef menu.

- Open the **Twitter API** tab. Fill the form with the client Twitter APP data. In
  order to **get all the tokens** and consumer key you need to make the class work
  you will need to create a Twitter APP using the client Twitter account.

- You can also set the **Number of Tweets** you want to retrieve and how ofter the
  **cache is cleared**. Twitter API has a limit in the number of request you can perform
  per day.

- Default number of Tweets retrieved per request is **5**.

- Save the form.

- Call static method **return_html** to get an ul HTML element which
  contains all the Twitter data. This method accpets parameters. Check
  usage_example.php for further information.
  ```php
  <?php
    //exmaple - $twitter_data contains <ul><li>Tweet 1</li<li>Tweet 2</li></ul>
    $twitter_data = Twitter::return_html();
    echo $twitter_data;
  ?>
  ```

- Call static method **check_twitter_data()** to retrieve an object
  which contains the twitter data.
  ```php
  <?php
    //example - $twitter_data contains an php objectstdClass
    $twitter_data = Twitter::check_twitter_data();
  ?>
  ```
- Modify the attribute **$minutes_after_call** in method **check_retrieve_form_api()**
  to change the time passed since last API call.

- Modify the attribute **$request** key *count* in method **return_tweet** to change
  the number of tweets retreived

<br/>
Check file **usage_example.php** located in this repository for a full working example.
