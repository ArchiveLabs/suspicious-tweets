<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2
 *********************************************************
 *********************************************************/

include_once("dbtweets.php");

if (isset($argv))
  {
  $v=GetVal($argv,1);
  if ($v=='warc')
    {
    $OutputFmt='warc';
    GetTweet($v);
    GetProfile($v);
    return;
    }
  elseif ($v=='cache')
    {
    $OutputFmt='cache';
    GetDates("cache");
    return;
    }
  elseif ($v=='new') { $OutputFmt='cli'; GetNewUsers(); return; }
  elseif ($v=='pop') { $OutputFmt='cli'; GetPopUsers(); return; }
  }

?>
