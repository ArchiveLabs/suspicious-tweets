<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 This file: render user profiles. 
 *********************************************************
 *********************************************************/

$TweetsPerPage=25;

/*********************************************************
 *********************************************************
 Prepare Template!
 *********************************************************
 *********************************************************/
$Templates['Profile']="
<div class='banner'><img title='banner' alt='banner' src='%BANNER%'></div>
  <div class='tweet tweet-profile'>
  <b>Profile</b><br>
      <img class='profileimglg' title='@%USER_ID%' alt='@%USER_ID%' src='%PROFILEIMG%'><br>
      <b>%USER_NAME% <span class='gray'>@%USER_ID%</span></b>
      <br>%USER_DESC%
      <div class='follow'>
	<span class='spanblock'><small>CREATED<br>%USER_CREATED%</small></span>
	<span class='spanblock'><small>TWEETS<br>%TWEETCOUNT%</small></span>
	<span class='spanblock'><small>FOLLOWING<br>%FOLLOWING%</small></span>
	<span class='spanblock'><small>FOLLOWERS<br>%FOLLOWERS%</small></span>
	<span class='spanblock'><small>GROUP<br>%SRC%</small></span>
      </div>
  </div>
  <br><br><br>
";

/******************************
 PopulateProfile(): fill in the profile template
 ******************************/
function PopulateProfile (&$row,$username,$screenname)
  {
  global $DBtweets,$DBmedia,$Templates,$OutputFmt;

  $HTML = $Templates['Profile'];
  $HTML = TemplateReplace($HTML,'%USER_ID%',$screenname);
  $HTML = TemplateReplace($HTML,'%USER_NAME%',$username);
  $HTML = TemplateReplace($HTML,'%USER_DESC%',GetVal($row,'user_profile_description'));
  $HTML = TemplateReplace($HTML,'%USER_CREATED%',GetVal($row,'account_creation_date'));
  $HTML = TemplateReplace($HTML,'%FOLLOWING%',GetVal($row,'following_count',1));
  $HTML = TemplateReplace($HTML,'%FOLLOWERS%',GetVal($row,'follower_count',1));
  $HTML = TemplateReplace($HTML,'%SRC%',GetVal($row,'src'));

  // Grab tweet count
  $sql = "SELECT count(userid) FROM tweets WHERE userid='" . $row['userid'] . "'";
  $v = $DBtweets->querySingle($sql);
  $row['tweet_count']=$v;
  $v = number_format($v,0,".",",");
  if ($OutputFmt=='web')
    {
    $v="<a href='?userid=" . $row['userid'] . "'>$v</a>";
    }
  $HTML = TemplateReplace($HTML,'%TWEETCOUNT%',$v);

  // Grab profile pictures
  $sql = "SELECT * FROM profile WHERE uid='" . $row['userid'] . "'";
  $res = $DBmedia->query($sql);
  $Banner=$UserPic=false;
  while($row2=$res->fetchArray())
    {
    if ($row2['type']=='banner') { $Banner= Zipfile2url($row2['zip'],$row2['zippath']); }
    if ($row2['type']=='profile') { $UserPic= Zipfile2url($row2['zip'],$row2['zippath']); }
    }
  $HTML = TemplateReplace($HTML,'%BANNER%',$Banner);
  $HTML = TemplateReplace($HTML,'%PROFILEIMG%',$UserPic);

  return(implode("\n",$HTML)."\n");
  } // PopulateProfile()

function GetProfile($userid,$offset=false,$tweetid=false)
  {
  global $DBtweets,$DBmedia,$Templates,$OutputFmt,$TweetsPerPage;
  $rc=0;
  $sql="SELECT DISTINCT userid FROM tweets";
  if ($userid) { $sql.=" WHERE userid='$userid'"; }
  elseif ($tweetid=='warc') { ; } // debug
  elseif ($tweetid) { $sql.=" WHERE tweetid='$tweetid'"; } // debug
  else { return; }
  if (!$offset) { $offset=0; }
  $res=$DBtweets->query($sql);
  $TweetCount=0;
  while($row=$res->fetchArray())
    {
    // These go with "https://twitter.com/username"
    $sql="SELECT * FROM tweets LEFT JOIN users ON tweets.userid=users.userid WHERE tweets.userid='" . $row['userid'] . "'";
    $sql.=" ORDER BY tweetid";
    $sql.=" LIMIT $TweetsPerPage";
    if ($offset) { $sql.=" OFFSET $offset"; }
    $tres = $DBtweets->query($sql);
    $HTML=$Templates['@Header'];
    if (strlen($row['userid'])==64) { $HTML=TemplateReplace($HTML,'%TITLE%','[redacted]'); }
    else { $HTML=TemplateReplace($HTML,'%TITLE%',$row['userid']); }
    $First=true;
    $TweetCount=0;
    $TweetUserid=$row['userid'];
    while($row=$tres->fetchArray())
      {
      if ($First)
	{
	// Twitter anonymized/redacted most user ids
	$TweetUserid = $userid = GetVal($row,'userid');
	$screenname = GetVal($row,'user_screen_name');
	$username = GetVal($row,'user_display_name');
	$snowflake = Snowflake2Info($row['tweetid']);
	if (strlen($userid) == 64) { $userid=$username=$screenname='[redacted]'; }
	$HTML .= PopulateProfile($row,$username,$screenname);
	$TweetCount=$row['tweet_count'];
	if ($OutputFmt=='web')
	  {
	  $HTML.="Showing tweets " . number_format($offset+1,0,'.',',') . " through " . number_format(min($offset+$TweetsPerPage,$TweetCount),0,'.',',') . ".<br>\n";
	  }
	$First=false;
	}
      $snowflake = Snowflake2Info($row['tweetid']);
      $HTML .= PopulateTweet($row,$username,$screenname,$snowflake);
      }

    if (($OutputFmt=='web') && ($TweetCount > $TweetsPerPage))
      {
      $HTML.="<br><br><center>More Tweets: ";
      if ($offset > 0) { $HTML.="[<a href='?userid=$TweetUserid&offset=0'>First</a>]\n"; }
      if ($offset >= 1000) { $HTML.="[<a href='?userid=$TweetUserid&offset=" . ($offset-1000) . "'>-1000</a>]\n"; }
      if ($offset >= 100) { $HTML.="[<a href='?userid=$TweetUserid&offset=" . ($offset-100) . "'>-100</a>]\n"; }
      if ($offset >= $TweetsPerPage) { $HTML.="[<a href='?userid=$TweetUserid&offset=" . ($offset-$TweetsPerPage) . "'>-$TweetsPerPage</a>]\n"; }
      if ($offset+$TweetsPerPage < $TweetCount) { $HTML.="[<a href='?userid=$TweetUserid&offset=" . ($offset+$TweetsPerPage) . "'>+$TweetsPerPage</a>]\n"; }
      if ($offset+100 < $TweetCount) { $HTML.="[<a href='?userid=$TweetUserid&offset=" . ($offset+100) . "'>+100</a>]\n"; }
      if ($offset+1000 < $TweetCount) { $HTML.="[<a href='?userid=$TweetUserid&offset=" . ($offset+1000) . "'>+1000</a>]\n"; }
      if ($TweetCount > $TweetsPerPage) { $HTML.="[<a href='?userid=$TweetUserid&offset=" . ($TweetCount-$TweetsPerPage) . "'>Last</a>]\n"; }
      $HTML.="</center>";
      }

    $HTML .= $Templates['@Footer'] . "\n";
    if ($OutputFmt=='web') { echo $HTML; }
    elseif ($OutputFmt=='warc')
      {
      $date=$row['tweet_time'] . " GMT";
      if ($snowflake) { $date=$snowflake['date']; }
      echo HTTP2WARC(HTML2HTTP($HTML,$date),$date,$row['userid'],false);
      }
    $rc=1;
    } // foreach profile
  return($rc);
  } // GetProfile()
?>
