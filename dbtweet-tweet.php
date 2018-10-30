<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 This file: Handle tweet rendering.
 *********************************************************
 *********************************************************/

/*********************************************************
 *********************************************************
 Prepare Template!
 *********************************************************
 *********************************************************/
$Templates['Tweet']="
  <div class='tweet tweet-info'>
    <div class='tweet center'>In Reply To: %REPLYTO%</div>
    <div class='tweet-body'>
      <div>
      <img class='profileimgsm img-rounded' alt='@%USER_ID%' src='%PROFILEIMG%'>
      <b class='tweet-heading'>%USER_NAME% <span class='gray'>@%USER_ID%</span></b><br>
<small>%TWEETDATE%</small>
      </div>
<br>
%TWEETTEXT%
%TWEETMEDIA%
      <br><br>
<span class='spanblock'><small>Likes<br>%LIKES%</small></span>
<span class='spanblock'><small>Retweets<br>%RETWEETS%</small></span>
<span class='spanblock'><small>Replies<br>%REPLIES%</small></span>
<span class='spanblock'><small>Quotes<br>%QUOTES%</small></span>
<span class='spanblock'><small>Location<br>%TWEETLOCATION%</small></span>
<span class='spanblock'><small>Language<br>%TWEETLANG%</small></span>
<span class='spanblock'><small>Application<br>%TWEETAPP%</small></span>
<span class='spanblock'><small>Group<br>%SRC%</small></span>
      </div>
  <br><div class='tweet center'>Retweet Source: %RETWEETSRC%</div>
  </div>";

/******************************
 FindTweetLink(): Check for retweet/reply reference.
 ******************************/
function FindTweetLink($userid,$tweetid)
  {
  global $DBtweets;
  if (!$tweetid || !$userid) { return(false); }
  $row = $DBtweets->querySingle("SELECT user_screen_name,src FROM users LEFT JOIN tweets ON users.userid=tweets.userid WHERE tweetid='$tweetid' LIMIT 1",true);

  $refu = $userid;
  if (strlen($refu) == 64) { $refu='[redacted]'; }
  $urltxt="https://twitter.com/$refu/status/$tweetid";
  $urllnk="https://twitter.com/$userid/status/$tweetid";
  if (!$row)
    {
    if ($refu=='[redacted]') { return($urltxt); }
    return("<a href='$urllnk'>$urltxt</a>");
    }

  // Matched one of the Twitter dump links!
  return('(' . $row['src'] . ") <a href='?userid=$userid&tweetid=$tweetid'>$urltxt</a>");
  } // FindTweetLink()(

/******************************
 PopulateTweet(): fill in the tweet template
 ******************************/
function PopulateTweet ($row,$username,$screenname,$snowflake)
  {
  global $DBtweets,$DBmedia,$Templates,$OutputFmt;

  $HTML = $Templates['Tweet'];
  $HTML = TemplateReplace($HTML,'%USER_ID%',$screenname);
  $HTML = TemplateReplace($HTML,'%USER_NAME%',$username);
  $HTML = TemplateReplace($HTML,'%LIKES%',GetVal($row,'like_count',1));
  $HTML = TemplateReplace($HTML,'%RETWEETS%',GetVal($row,'retweet_count',1));
  $HTML = TemplateReplace($HTML,'%REPLIES%',GetVal($row,'reply_count',1));
  $HTML = TemplateReplace($HTML,'%QUOTES%',GetVal($row,'quote_count',1));
  $HTML = TemplateReplace($HTML,'%TWEETLANG%',GetVal($row,'tweet_language'));
  $HTML = TemplateReplace($HTML,'%SRC%',GetVal($row,'src'));
  if ($snowflake)
    {
    $HTML = TemplateReplace($HTML,'%TWEETDATE%',$snowflake['date']);
    }
  else
    {
    $HTML = TemplateReplace($HTML,'%TWEETDATE%',$row['tweet_time'] . " GMT");
    }
  $HTML = TemplateReplace($HTML,'%TWEETAPP%',GetVal($row,'tweet_client_name'));

  // Store tweet text
  $v=GetVal($row,'tweet_text');
  // Text: Replace t.co URLs
  $urls=GetVal($row,'urls');
  $urls=trim($urls,"[]");
  $urls=explode(", ",$urls);
  if (isset($urls[0]))
    {
    $Match=array();
    preg_match_all('@https://t.co/[[:alnum:]]+@',$v,$Match);
    for($i=0; isset($urls[$i]); $i++)
      {
      if (!$urls[$i] || !isset($Match[0][$i])) { continue; }
      $newurl="<a href='".$urls[$i]."'>".$Match[0][$i]."</a>";
      $v=str_replace($Match[0][$i],$newurl,$v);
      }
    }
  // Text: Remove redacted twitter names
  $v=preg_replace('/@[0-9a-f]{64}/','@[redacted]',$v);
  $HTML = TemplateReplace($HTML,'%TWEETTEXT%',$v);

  // Lat/Lon is special
  $lat=GetVal($row,'latitude',2);
  $lon=GetVal($row,'longitude',2);
  if ($lat || $lon) { $v=$lat . "," . $lon; } else { $v=false; }
  $HTML = TemplateReplace($HTML,'%TWEETLOCATION%',$v);

  // Resolve reference: reply to...
  $u=GetVal($row,'in_reply_to_userid');
  $v=GetVal($row,'in_reply_to_tweetid');
  $HTML = TemplateReplace($HTML,'%REPLYTO%',FindTweetLink($u,$v));

  // Resolve reference: retweet...
  $u=GetVal($row,'retweet_userid');
  $v=GetVal($row,'retweet_tweetid');
  $HTML = TemplateReplace($HTML,'%RETWEETSRC%',FindTweetLink($u,$v));

  // Grab profile pictures
  $sql = "SELECT * FROM profile WHERE uid='" . $row['userid'] . "'";
  $res = $DBmedia->query($sql);
  $Banner=$UserPic=false;
  while($row2=$res->fetchArray())
    {
    if ($row2['type']=='banner') { $Banner= Zipfile2url($row2['zip'],$row2['zippath']); }
    if ($row2['type']=='profile') { $UserPic= Zipfile2url($row2['zip'],$row2['zippath']); }
    }
  $HTML = TemplateReplace($HTML,'%PROFILEIMG%',$UserPic);

  // Grab media
  $sql = "SELECT * FROM media WHERE tweetid='" . $row['tweetid'] . "'";
  $res = $DBmedia->query($sql);
  $Media='';
  $First=true;
  while($row2=$res->fetchArray())
    {
    $url=Zipfile2url($row2['zip'],$row2['zippath']);
    if ($First) { $Media.="<br>"; $First=false; }
    $Media.="<br>";
    if ($row2['type']=='videos')
      {
      $Media.="<video class='twimg' controls='controls'>";
      $Media.="<source src='$url' type='video/mp4'>";
      $Media.="</video>\n";
      }
    else
      {
      $Media.="<a href='$url'><img class='twimg' src='$url'></a>\n";
      }
    }
  $HTML = TemplateReplace($HTML,'%TWEETMEDIA%',$Media);

  $HTML = implode("\n",$HTML)."\n";

  // if it's output is web:
  if ($OutputFmt=='web')
    {
    $p=strpos($HTML,"<div>");
    $v="<div class='pointer' onclick='window.location=\"?tweetid=".$row['tweetid']."\"'>";
    $HTML=substr_replace($HTML,$v,$p,5);
    }

  return($HTML);
  } // PopulateTweet()

//******************************************************
// Process each tweet!
function GetTweet($tweetid)
  {
  global $DBtweets,$Templates,$OutputFmt;
  $rc=0;
  $sql="SELECT * FROM tweets";
  $sql.=" LEFT JOIN users ON tweets.userid=users.userid";
  if ($tweetid=='warc') { $sql.=" LIMIT 10"; }
  elseif ($tweetid) { $sql.=" WHERE tweetid='$tweetid'"; }
  else { return; }
  $res=$DBtweets->query($sql);
  while($row=$res->fetchArray())
    {
    // These go with "https://twitter.com/userid/status/tweetid"

    // Twitter anonymized/redacted most user ids
    $userid = GetVal($row,'userid');
    $screenname = GetVal($row,'user_screen_name');
    $username = GetVal($row,'user_display_name');
    $snowflake = Snowflake2Info($row['tweetid']);
    if (strlen($userid) == 64) { $userid=$username=$screenname='[redacted]'; }

    $HTML  = $Templates['@Header'];
    $HTML  = TemplateReplace($HTML,'%TITLE%','Tweet');
    $HTML .= PopulateProfile($row,$username,$screenname);
    $HTML .= PopulateTweet($row,$username,$screenname,$snowflake);
    $HTML .= PopulateDetails($row,$username,$screenname,$snowflake);
    $HTML .= $Templates['@Footer'] . "\n";
    if ($OutputFmt=='web') { echo $HTML; }
    elseif ($OutputFmt=='warc')
      {
      $date=$row['tweet_time'] . " GMT";
      if ($snowflake) { $date=$snowflake['date']; }
      echo HTTP2WARC(HTML2HTTP($HTML,$date),$date,$row['userid'],$row['tweetid']);
      }
    $rc=1;
    } // foreach Tweet to encode
  return($rc);
  } // GetTweet()
?>
