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
 What happened on what date?
 *********************************************************
 *********************************************************/

function GetDates($date)
  {
  global $DBtweets,$argv,$Templates;

  if (!$date) { return; }
  if (!is_dir("cache")) { mkdir("cache"); }

  // Retrieve list of dates + tweets per day
  // NOTE: This part is slow! Generate from the command-line first.
  $Cal=array();
  $Cache="cache/cache-dates-all.gz";
  if (is_file($Cache)) { $Cal=json_decode(gzdecode(file_get_contents($Cache)),true); }
  else
    {
    $sql='SELECT tweet_time,src FROM tweets LEFT JOIN users ON tweets.userid=users.userid';
    $res=$DBtweets->query($sql);
    while($row=$res->fetchArray())
	{
	$f=substr($row['tweet_time'],0,10);
	if (!isset($Cal[$f]['all'])) { $Cal[$f]['all']=0; }
	if (!isset($Cal[$f][$row['src']])) { $Cal[$f][$row['src']]=0; }
	$Cal[$f]['all']++;
	$Cal[$f][$row['src']]++;
	}
    ksort($Cal);
    file_put_contents($Cache,gzencode(json_encode($Cal),9),LOCK_EX);
    }

  //echo "<pre>" . print_r($date,true) . "</pre>\n"; exit;
  //echo "<pre>" . print_r($Cal,true) . "</pre>\n"; exit;
  if ($date=='cache')
    {
    foreach($Cal as $date=>$count)
      {
      if ($count['all'] >= 1000) { GetDates($date); }
      }
    exit;
    }

  // Need to display something

  $HTML=$Templates['@Header'];
  if ($date=="all") // Display all dates
    {
    $HTML=TemplateReplace($HTML,'%TITLE%','All Dates');
    $HTML.="<H1>All Tweet Dates</H1>\n";
    $HTML.="All tweets identified by Twitter as being part of Russia's IRA or Iranian influence campaigns.\n";
    $HTML.="A horizontal line identifies a break between consecutive days.\n";
    $HTML.="<br><br>\n";
    $HTML.="<div class='tweet'>\n";
    $HTML.="<table class='sortable'>\n";
    $HTML.="<thead>\n";
    $HTML.="<tr><th style='width:6em'>Date</th><th align='right' style='width:4em'>Total</th><th align='right' style='width:4em'>IRA</th><th align='right' style='width:4em'>Iran</th></tr>\n";
    $HTML.="</thead>\n";
    $HTML.="<tbody>\n";
    $LastDate='';
    $First=true;
    foreach($Cal as $date=>$count)
      {
      $v='';
      if ($First) { $First=false; $LastDate=new DateTime($date); }
      else
        {
        $d=new DateTime($date);
        if ($d->diff($LastDate)->format("%a") > 1)
	 {
	 $v.="<tr><td colspan='4' sorttable_customkey='" . $date . "'><hr></td></tr>\n";
	 }
        $LastDate=$d;
        }
      $v.="<tr><td style='width:6em'><a href='?date=" . $date . "'>" . $date . "</a></td>";
      $v.="<td align='right' style='width:4em'>" . number_format($count['all'],0,'.',',') . "</td>";
      $v.="<td align='right' style='width:4em'>";
      if (isset($count['IRA']) && $count['IRA']) { $v.=number_format($count['IRA'],0,'.',','); }
      $v.="</td>";
      $v.="<td align='right' style='width:4em'>";
      if (isset($count['Iran']) && $count['Iran']) { $v.=number_format($count['Iran'],0,'.',','); }
      $v.="</td></tr>";
      $HTML.=$v . "\n";
      }
    $HTML.="</tbody>\n";
    $HTML.="</table>\n";
    $HTML.="</div>\n";
    }

  // Got a date? Just return tweets from that date!
  elseif (array_key_exists($date,$Cal))
    {
    $HTML=TemplateReplace($HTML,'%TITLE%',$date);
    // Find start/end dates
    $HTML.="<H1>All Tweets From $date</H1>\n";
    $HTML.="Tweets identified by Twitter as being part of Russia's IRA or Iranian influence campaigns.\n";
    $HTML.="<br>NOTE: Twitter redacted account names that had less than 5,000 followers.\n";
    $HTML.="<br><br>\n";
    $HTML.="<div class='tweet'>\n";
    $HTML.="<table class='sortable' width='100%'>\n";
    $HTML.="<thead>\n";
    $HTML.="<tr><th style='width:12em'>Date/Time</th><th style='width:4em'>Group</th><th style='width:20em' nowrap>User</th><th align='right' style='width:10em'>Tweet</th></tr>\n";
    $HTML.="</thead>\n";
    $HTML.="<tbody>\n";

    $Time=array();
    $Cache="cache/cache-$date.gz";
    if (is_file($Cache))  { $Time=json_decode(gzdecode(file_get_contents($Cache)),true); }
    else
      {
      // Convert dates to Y-M-D!
      $date1=new DateTime($date . " 00:00:00 GMT");
      $date1=$date1->getTimestamp(); // 1st day
      $date1 = gmdate("Y-m-d",$date1);
      // Don't use "ORDER BY tweetid" because that's slow in sqlite; use ksort!
      // Find all tweets front a specific date range
      $sql="SELECT tweetid,tweet_time,tweets.userid,user_screen_name,user_display_name,src FROM tweets";
      $sql.=" LEFT JOIN users ON users.userid=tweets.userid";
      $sql.=" WHERE tweet_time BETWEEN '$date1 00:00' AND '$date1 23:59'";
      $sql.=" LIMIT " . $Cal[$date]['all']; // hint: I already know the count
      #error_log($sql); // debug
      $res = $DBtweets->query($sql);
      while($row=$res->fetchArray())
        {
	foreach($row as $f=>$v) { if (is_numeric($f)) { unset($row[$f]); } }
	if (strlen($row['userid'])==64) { $row['user_screen_name']=$row['user_display_name']='[redacted]'; }
	$Time[$row['tweetid']] = $row;
	}
      ksort($Time);
      if ($Cal[$date]['all'] >= 1000)
	{
	file_put_contents($Cache,gzencode(json_encode($Time),9),LOCK_EX);
	}
      }
    $Hour=array('IRA','Iran'); // Hourly Breakdown
    for($i=0; $i < 24; $i++) { $Hour['IRA'][$i]=$Hour['Iran'][$i]=0; }
    foreach($Time as $T=>$row)
      {
      $v = "<tr><td nowrap style='width:12em'>";
      $v.= $row['tweet_time'];
      $v.= " GMT";
      $v.= "</td><td style='width:4em'>";
      $v.= $row['src'];
      $v.= "</td><td nowrap style='width:20em'>";
      $u=$row['userid'];
      $v.= "<a href='?userid=$u'>";
      if (strlen($u)==64) { $v.= "[redacted]</a>"; }
      else
	{
	$v.= Taint($row['user_display_name']);
	$v.= "</a> <span class='gray'>@" . Taint($row['user_screen_name']) . "</span>";
	}
      $v.= "</td><td style='width:10em;text-align:right'>";
      $t=$row['tweetid'];
      $v.= "<a href='?tweetid=$t'>$t</a>";
      $v.= "</td></tr>";
      $HTML.=$v;

      // hourly stats
      $H=intval(substr($row['tweet_time'],11,2));
      $Hour[$row['src']][$H]++;
      }
    $HTML.="</tbody>\n";
    $HTML.="</table>\n";
    $HTML.="</div>\n";
    $HTML.="<br>Total tweets: " . number_format($Cal[$date]['all'],0,'.',',') . "\n";

    // Show Hourly Breakdown
    $HTML.="<div class='tweet'>\n";
    $HTML.="<H2>Hourly Distribution (GMT)</H2>\n";
    //$HTML.="<pre>" . print_r($Hour,true) . "</pre>"; // debug
    $Max=1;
    for($i=0; $i < 24; $i++)
      {
      $h=$Hour['IRA'][$i] + $Hour['Iran'][$i];
      if ($h > $Max) { $Max=$h; }
      }
    for($i=0; $i < 24; $i++)
      {
      $PercentIRA = intval(100*$Hour['IRA'][$i] / $Max);
      $PercentIran = intval(100*$Hour['Iran'][$i] / $Max);
      $PIRA = intval(100*$Hour['IRA'][$i] / $Cal[$date]['all']);
      $PIran = intval(100*$Hour['Iran'][$i] / $Cal[$date]['all']);
      $Pad = 100 - $PercentIRA - $PercentIran;
      $HTML.="<span class='hour'>\n";
      if ($Pad > 0) { $HTML.="<div class='hourpad' style='height:${Pad}px'></div>"; }
      if ($PercentIRA > 0) { $HTML.="<div class='hourbar1' title='${PIRA}%' style='height:${PercentIRA}px'></div>"; }
      if ($PercentIran > 0) { $HTML.="<div class='hourbar2' title='${PIran}%' style='height:${PercentIran}px'></div>"; }
      $HTML.="$i";
      $HTML.="</span>";
      }
    $HTML.="&nbsp;<span class='hour'>";
    $HTML.="<div class='hourbar1'>IRA</div>";
    $HTML.="<div class='hourbar2'>Iran</div>";
    $HTML.="</span>";
    $HTML.="</div>\n";
    }
  else
    {
    $HTML=TemplateReplace($HTML,'%TITLE%',$date);
    }

  // Page Done
  $HTML.=IncludeSortTable();
  $HTML.=$Templates['@Footer'];
  echo $HTML . "\n";
  return;
  } // GetDates()
?>
