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
 Where were the various user accounts created?
 *********************************************************
 *********************************************************/
function GetNewUsers()
  {
  global $DBtweets,$argv,$Templates,$OutputFmt;
  $HTML=$Templates['@Header'];
  $HTML=TemplateReplace($HTML,'%TITLE%',"Account Creation Dates");

  // Get new users from a specific date:
  // select distinct userid,user_screen_name,src from tweets where account_creation_date="2017-06-02" limit 8

  $Cal=array();
  $Cache="cache/cache-dates-all.gz"; // better exist!
  if (is_file($Cache)) { $Cal=json_decode(gzdecode(file_get_contents($Cache)),true); }

  // Dates new users were created
  $Users=array();
    {
    $sql="SELECT DISTINCT userid,src,account_creation_date FROM users";
    $res = $DBtweets->query($sql);
    while($row=$res->fetchArray())
      {
      $date=$row['account_creation_date'];
      if (!isset($Users[$date])) { $Users[$date]=array(); $Users[$date]['all']=0; }
      $Users[$date]['all']++;
      if (!isset($Users[$date][$row['src']])) { $Users[$date][$row['src']]=0; }
      $Users[$date][$row['src']]++;
      }
    ksort($Users);
    }

  $HTML.="<div class='tweet'>\n";
  $HTML.="<H1>Account Creation Dates</H1>\n";
  $HTML.="Dates when user accounts were created. A horizontal line indicates non-sequential dates.\n";
  $HTML.="<br><br>\n";
  $HTML.="<table>\n";
  $HTML.="<thead>\n";
  $HTML.="<tr><th style='width:8em'>Date</th><th style='width:6em'>All New Accounts</th><th style='width:6em'>New IRA Accounts</th><th style='width:6em'>New Iran Accounts</th></tr>\n";
  $HTML.="</thead>\n";
  $HTML.="<tbody>\n";
  foreach($Users as $date=>$row)
    {
    $v="<tr>";
    $v.="<td style='width:8em'>";
    if (($OutputFmt=='cli') || !isset($Cal[$date])) { $v.=$date; }
    else { $v.="<a href='?date=$date'>$date</a>"; }
    $v.="</td>";
    $v.="<td style='width:6em;text-align:center'>" . number_format($row['all'],0,".",",") . "</td>";
    $v.="<td style='width:6em;text-align:center'>";
    if (isset($row['IRA']) && $row['IRA']) { $v.=number_format($row['IRA'],0,".",","); }
    $v.="</td>";
    $v.="<td style='width:6em;text-align:center'>";
    if (isset($row['Iran']) && $row['Iran']) { $v.=number_format($row['Iran'],0,".",","); }
    $v.="</td>";
    $v.="</tr>";
    $HTML.=$v . "\n";
    }
  $HTML.="</tbody>\n";
  $HTML.="</table>\n";
  $HTML.="<br>NOTE: Most accounts did NOT tweet on the same day they were created.\n";
  $HTML.="Only dates with tweets have hyperlinks.\n";
  $HTML.="</div>\n";

  // Page Done
  $HTML.=$Templates['@Footer'];
  echo $HTML . "\n";
  return;
  } // GetNewUsers()
?>
