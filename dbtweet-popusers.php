<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 This file:
 Data mining.
 Twitter redacted users with < 5000 followers.
 So 5000 makes someone popular.
 Retrieve all popular users!
 *********************************************************
 *********************************************************/
function GetPopUsers()
  {
  global $DBtweets,$argv,$Templates;
  $HTML=$Templates['@Header'];
  $HTML=TemplateReplace($HTML,'%TITLE%',"Popular Accounts");

  // And most common users
  $Users=array();
    {
    $sql="SELECT DISTINCT userid,src,account_creation_date,follower_count,user_screen_name,user_display_name FROM users";
    $sql.=" WHERE LENGTH(userid) < 64";
    $res = $DBtweets->query($sql);
    while($row=$res->fetchArray())
      {
      foreach($row as $f=>$v) { if (is_numeric($f)) { unset($row[$f]); } }
      $Users[] = $row;
      }
    function UserSort($a,$b)
      {
      if ($a['account_creation_date']<$b['account_creation_date']) return(-1);
      if ($a['account_creation_date']>$b['account_creation_date']) return(+1);
      if ($a['userid']<$b['userid']) return(-1);
      if ($a['userid']>$b['userid']) return(+1);
      return(0);
      }
    usort($Users,"UserSort");
    }
  $HTML.="<div class='tweet'>\n";
  $HTML.="<H2>Most Followed Users</H2>\n";
  $HTML.="Twitter redacted most usernames. Only users with at least 5,000 followers were not redacted.<br><br>\n";
  $HTML.="<table>\n";
  $HTML.="<thead>\n";
  $HTML.="<tr><th style='width:2em'>Group</th><th style='width:6em'>Account</th><th style='width:15em'>Name</th><th style='width:8em'>Followers</th><th style='width:7em'>Created</th></tr>\n";
  $HTML.="</thead>\n";
  $HTML.="<tbody>\n";
  foreach($Users as $row)
    {
    $v="<tr>";
    $v.="<td style='width:2em'>" . $row['src'] . "</td>";
    $v.="<td style='width:6em'><a href='?userid=" . $row['userid'] . "'>@" . $row['user_screen_name'] . "</a></td>";
    $v.="<td style='width:15em'>" . Taint($row['user_display_name']) . "</td>";
    $v.="<td style='width:8em;text-align:right'>" . number_format($row['follower_count'],0,".",",") . "</td>";
    $v.="<td style='width:7em;text-align:right'>" . $row['account_creation_date'] . "</td>";
    $v.="</tr>";
    $HTML.=$v . "\n";
    }
  $HTML.="</tbody>\n";
  $HTML.="</table>\n";
  $HTML.="</div>\n";

  // Page Done
  $HTML.=$Templates['@Footer'];
  echo $HTML . "\n";
  return;
  } // GetPopUsers()
?>
