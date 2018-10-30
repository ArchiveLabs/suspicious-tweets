<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 This file:
 Data Mining
 List accounts, creation, and first tweet dates.
 *********************************************************
 *********************************************************/

function Plural($v,$t) { return("$v $t" . ($v==1 ? "" : "s")); } 

function GetUsers($UserType=false)
  {
  global $DBtweets,$argv,$Templates,$OutputFmt;
  $HTML=$Templates['@Header'];
  if ($UserType=='pop') { $HTML=TemplateReplace($HTML,'%TITLE%',"Popular Accounts"); }
  elseif ($UserType=='news') { $HTML=TemplateReplace($HTML,'%TITLE%',"News Accounts"); }
  elseif ($UserType=='journalist') { $HTML=TemplateReplace($HTML,'%TITLE%',"Journalist Accounts"); }
  else { $HTML=TemplateReplace($HTML,'%TITLE%',"Accounts"); }

  $HTML.=IncludeSortTable();

  // Get new users from a specific date:
  // select distinct userid,user_screen_name,src from tweets where account_creation_date="2017-06-02" limit 8

  $Cal=array();
  $Cache="cache/cache-dates-all.gz"; // better exist!
  if (is_file($Cache)) { $Cal=json_decode(gzdecode(file_get_contents($Cache)),true); }

  // Dates new users were created
  $Users=array();
  $Cache="cache/cache-users-all.gz";
  if (is_file($Cache)) { $Users=json_decode(gzdecode(file_get_contents($Cache)),true); }
  else 
    {
    //$sql="SELECT account_creation_date,tweet_time,src,users.userid,user_screen_name,user_display_name,user_profile_description,follower_count,tweetid FROM users";
    $sql="SELECT * FROM users";
    $sql.=" LEFT JOIN tweets ON users.first_tweet=tweets.tweetid";
    //if ($UserType=='pop') { $sql.=" WHERE length(users.userid) < 64"; }
    $sql.=" ORDER BY account_creation_date,tweet_time";
    $res = $DBtweets->query($sql);
    while($row=$res->fetchArray())
      {
      $Users[]=$row;
      }
    file_put_contents($Cache,gzencode(json_encode($Users),9),LOCK_EX);
    }

  $HTML.="<div class='tweet'>\n";
  $HTML.="<H1>";
  if ($UserType=='pop') { $HTML.="Popular "; }
  elseif ($UserType=='news') { $HTML.="Faux News "; }
  elseif ($UserType=='journalist') { $HTML.="Faux Journalist "; }
  $HTML.="Accounts</H1>\n";
  $HTML.="Each idenified Russian IRA and Iran account.\n";
  $HTML.="This includes when the account was created, when it first tweeted, and the elapsed time between creation and first tweet.\n";
  $HTML.="<br><br>\n";
  $HTML.="<table class='sortable'>\n";
  $HTML.="<thead>\n";
  $HTML.="<tr>";
  $HTML.="<th style='width:4em'>Group</th>";
  $HTML.="<th style='width:20em;text-align:left'>Account</th>";
  $HTML.="<th style='width:6em;text-align:left'>Created</th>";
  $HTML.="<th style='width:7em;text-align:left'>First Tweet</th>";
  $HTML.="<th style='width:6em' title='in years, months, days'>Elapsed</th>";
  $HTML.="<th style='width:6em;text-align:right'>Followers</th>";
  $HTML.="</tr>\n";
  $HTML.="</thead>\n";
  $HTML.="<tbody>\n";
  $Count=0;
  $NewsFalseNegative=array(
	  "b9c419eb9aaee0f5961eafed9757e077abe618aad966ce46c5b80ccbff6bdc98" // "US Journal"
	  );
  $JournalistFalsePositive=array(
	  "02310ce9a5d2f0740b57311efe8ceb013f84bbb61c1d7655034118a1febc9f9e",
	  "4a4f1c3cef9f4ee0a5d4d6c0c6d66cb5ed1e4d2d58a7cd79520f4b94eb7c5f5f",
	  "bcb05930008919ef08d611289eb92812ac09197f75da82140253539961935cad",
	  "b9c419eb9aaee0f5961eafed9757e077abe618aad966ce46c5b80ccbff6bdc98" // caught by news
	  );
  foreach($Users as $row)
    {
    if (($UserType=='pop') && (strlen($row['userid'])==64)) { continue; }
    elseif ($UserType=='news')
	{
	if (in_array($row['userid'],$NewsFalseNegative)) { ; }
	elseif (!preg_match('@news|daily|online|today@i',$row['user_screen_name'] . $row['user_display_name']))
	  { continue; }
	}
    elseif ($UserType=='journalist')
	{
	if (!preg_match('@journal@i',$row['user_profile_description'])) { continue; }
	if (in_array($row['userid'],$JournalistFalsePositive)) { continue; }
	}

    $Count++;
    $v="<tr>";
    $v.="<td style='width:4em'>" . $row['src'] . "</td>";
    $v.="<td style='width:20em'>";
    if (strlen($row['userid'])==64)
      {
      $row['user_screen_name']=false;
      $row['user_display_name']='[redacted]';
      }
    $v.="<a href='?userid=" . $row['userid'];
    $v.="' title='" . Taint($row['user_profile_description']);
    $v.="'>" . Taint($row['user_display_name']) . "</a>";
    if ($row['user_screen_name']) { $v.=" (@" . $row['user_screen_name'] . ")"; }
    $v.="</td>";
    $v.="<td style='width:6em'>" . $row['account_creation_date'] . "</td>";

    $row['tweet_time']=substr($row['tweet_time'],0,10);
    $v.="<td style='width:7em'";
    $v.=" title=\"" . Taint($row['tweet_text']) . "\"";
    $v.=">" . $row['tweet_time'] . "</td>";

    $d1=date_create($row['account_creation_date']);
    $d2=date_create($row['tweet_time']);
    $elapse = $d2->diff($d1);
    $e='';
    $et='';
    if ($elapse->y >= 1) { $e.=$elapse->y . "y "; $et.=Plural($elapse->y,"year")." "; }
    if ($elapse->m >= 1) { $e.=$elapse->m . "m "; $et.=Plural($elapse->m,"month")." "; }
    if ($elapse->d >= 0) { $e.=$elapse->d . "d"; $et.=Plural($elapse->d,"day"); }
    if ($elapse->days==0) { $e='0 days'; }
    $v.="<td style='width:6em;text-align:right'";
    $v.=" title='$et'";
    $v.=" sorttable_customkey='" . $elapse->days . "'";
    $v.=">$e</td>";

    $v.="<td style='width:6em;text-align:right'>" . number_format($row['follower_count'],0,".",",") . "</td>";
    $v.="</tr>";
    $HTML.=$v . "\n";
    }
  $HTML.="</tbody>\n";
  $HTML.="</table>\n";
  $HTML.="<br>";
  $HTML.="There are " . number_format($Count,0,".",",") . " accounts.\n";
  $HTML.="<br>NOTE: Most accounts did NOT tweet on the same day they were created.\n";
  $HTML.="</div>\n";

  // Page Done
  $HTML.=$Templates['@Footer'];
  echo $HTML . "\n";
  return;
  } // GetUsers()
?>
