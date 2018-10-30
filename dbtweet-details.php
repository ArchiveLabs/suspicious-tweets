<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 This file: Populate additional details.
 *********************************************************
 *********************************************************/

$Templates['Details']="
  <br><br><br>
  <div class='tweet tweet-details'>
  <b>Details</b>
  <br><span class='details'>Twitter Server:</span><span>%TWITTERINFO%</span>
  <br><span class='details'>Twitter Redacted Hash:</span><span>%TWITTERREDACTED%</span>
  </div>";

/******************************
 PopulateDetails(): fill in the details template
 ******************************/
function PopulateDetails ($row,$username,$screenname,$snowflake)
  {
  global $DBtweets,$Templates;

  $HTML = $Templates['Details'];

  $v=false;
  if ($snowflake)
    {
    $v="Data center " . $snowflake['datacenter'] . ", worker " . $snowflake['worker'];
    }
  $HTML = TemplateReplace($HTML,'%TWITTERINFO%',$v);
  $HTML = TemplateReplace($HTML,'%TWEETLANG%',GetVal($row,'tweet_language'));

  $u=GetVal($row,'userid');
  if (strlen($u) == 64) { $HTML = TemplateReplace($HTML,'%TWITTERREDACTED%',$u); }
  else { $HTML = TemplateReplace($HTML,'%TWITTERREDACTED%',false); }
  return(implode("\n",$HTML)."\n");
  } // PopulateDetails()

?>
