<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 Snarky Lessons:
 With php+sqlite: stay away from 'group by' and 'order by'.
 They are way too slow for production.
 It's much faster to get all data and use php to group/order/count.
 *********************************************************
 *********************************************************/

$OutputFmt='unset';

/******************************
 Zipfile2url(): Convert a zip file location to a URL.
 (For media.)
 IA already has all of the zip files!
 Rather than putting the actual media in the WARC, just
 reference the IA URL.
 ******************************/
function Zipfile2url	($zip,$file)
  {
  /*****
   Archived at the Internet Archive:
     https://archive.org/details/twittersdataarchive

   To view a specific file:
     https://archive.org/download/twitter-ira/ira/[ZIPFILE]/[PATHINZIP]
   E.g.:
     https://archive.org/download/twitter-ira/ira/ira_tweet_media_hashed_10.zip/ira_10%2F09f9f2673dc328eb7ee0204b7712f766d9b102ff9c657e614e4da7e0553eb790%2Fimages%2F673250708364247040-CVfPc8YUsAApZSL.jpg
     https://archive.org/download/twitter-ira/ira/ira_tweet_media_hashed_10.zip/ira_10/09f9f2673dc328eb7ee0204b7712f766d9b102ff9c657e614e4da7e0553eb790/images/673250708364247040-CVfPc8YUsAApZSL.jpg
   *****/
  $URL=false;
  if (!strncmp($file,"iran",4)) { $URL="https://archive.org/download/twitter-iran/iran/"; }
  elseif (!strncmp($file,"ira",3)) { $URL="https://archive.org/download/twitter-ira/ira/"; }
  else { return false; } // should never happen

  $URL .= $zip . "/" . $file;
  return($URL);
  }

/******************************
 Snowflake2Info(): Twitter encodes every tweet id into
 a 64-bit format called Snowflake.
   MSB 42 bits = timestamp with non-standard epoch.
   5 bits = data center id
   5 bits = worker id (box in the data center)
   12 bits LSB = just for uniqueness; rolls over occasionally
 This function parses the snowflake id and returns an array
 of [time] [datacenter] [worker]
 ******************************/
function Snowflake2Info	($sfid)
  {
  /*****
   Snowflake was introduced on:
     2010-11-04 21:04
   On/after that time, Snowflake was used.
   Before that time, it was a variety of odd epochs.
   *****/
  $decode=array();

  if (($sfid >> 48)==0) // old format
    {
    return(false);
    }
  else // new snowflake format
    {
    $decode['worker'] = ($sfid >> 12) & 0x1F;
    $decode['datacenter'] = ($sfid >> 17) & 0x1F;
    // Time is a little more complicated
    // For milliseconds: $t = ($sfid >> 22) & 0x3FFFFFFFFFF;
    // with epoch: 1288834974657
    $t = ($sfid >> 22);
    $t += 1288834974657;
    $t = intval($t/1000); // convert milliseconds to seconds
    }
  // Now $t is in unix epoch.
  $decode['time'] = $t;
  $decode['date'] = gmdate("Y-m-d h:i:s",$t) . " GMT";
  return($decode);
  }

/******************************
 Taint(): Make text HTML-safe.
 ******************************/
function Taint($v)
  {
  $v=str_replace("&","&amp;",$v);
  $v=str_replace('"',"&quot;",$v);
  $v=str_replace("<","&lt;",$v);
  $v=str_replace(">","&gt;",$v);
  return($v);
  }

/******************************
 GetVal(): Get a value from an array, or return false
 if it doesn't exist (or has no length).
 ******************************/
function GetVal	($arr,$col,$IsNum=false)
  {
  if (!is_array($arr) || !$col || !isset($arr[$col]))
    {
    if ($IsNum) { return(0); }
    return(false);
    }
  $v=$arr[$col];
  if (($IsNum > 1) && is_numeric($v)) { $v=number_format($v,4,'.',','); }
  elseif ($IsNum) { $v=number_format(intval($v),0,'.',','); }
  elseif (strlen($v) < 1) { return(false); }
  $v=Taint($v);
  return($v);
  }

include_once("dbtweet-templates.php"); // must include first
include_once("dbtweet-warc.php");
include_once("dbtweet-profile.php"); // includes template
include_once("dbtweet-tweet.php"); // includes template
include_once("dbtweet-details.php"); // includes template
include_once("dbtweet-users.php");
include_once("dbtweet-newusers.php");
include_once("dbtweet-dates.php");
include_once("dbtweet-intro.php");

/********************************************************
 ********************************************************
 MAIN
 ********************************************************
 ********************************************************/

// Prepare templates
foreach($Templates as $f=>$v)
  {
  if ($f[0]=='@') { continue; }
  $Templates[$f] = explode("\n",$v);
  }

// Load databases
$SQLDB="tweets.sqlite";
$DBtweets=new SQLite3($SQLDB,SQLITE3_OPEN_READONLY); // Assume Prep.sh has completed
if (!$DBtweets) { return; }
$SQLDB="media.sqlite";
$DBmedia=new SQLite3($SQLDB,SQLITE3_OPEN_READONLY); // Assume Prep.sh has completed
if (!$DBmedia) { return; }

/********************************
 ProcessWeb(): Handle web requests.
 ********************************/
function ProcessWeb()
  {
  global $OutputFmt;
  $OutputFmt='web';
  if (isset($_REQUEST["samples"]))
	{
	include_once("dbtweet-samples.php");
	GetSamples();
	return;
	}
  $t=GetVal($_REQUEST,'tweetid');
  $u=GetVal($_REQUEST,'userid');
  $d=GetVal($_REQUEST,'date');
  $offset=intval(GetVal($_REQUEST,'offset')); // for users with too many tweets
  if ($offset <= 0) { $offset=false; }
  // Validate inputs
  if ($t && !is_numeric($t)) { $t=false; }
  if ($u)
    {
    if ($u=='pop') { ; } // ?userid=pop = popular accounts
    elseif ($u=='new') { ; } // ?userid=new = new account creation dates
    elseif ($u=='news') { ; } // ?userid=news = fake "news accounts"
    elseif ($u=='journalist') { ; } // ?userid=journalist = fake journalists
    elseif ($u=='all') { ; } // ?userid=all = all account creation info
    elseif (!ctype_xdigit($u)) { $u=false; } // ?userid=... = specific user
    }
  if ($d)
    {
    if ($d=='all') { ; } // ?date=all = display all tweets by date
    elseif (!preg_match('@^[0-9]{4}-[0-9]{2}-[0-9]{2}$@',$d)) { $d=false; } // ?date=YYYY-MM-DD = specific date
    }
  if ($t && GetTweet($t)) { ; }
  elseif ($u=='all') { GetUsers(); }
  elseif ($u=='pop') { GetUsers($u); }
  elseif ($u=='news') { GetUsers($u); }
  elseif ($u=='journalist') { GetUsers($u); }
  elseif ($u=='new') { GetNewUsers(); }
  elseif ($u) { GetProfile($u,$offset); }
  elseif ($d) { GetDates($d); }
  else { GetIntro(); }
  }
?>
