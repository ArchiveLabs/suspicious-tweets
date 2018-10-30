<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 This file: Convert sqlite database and pages into a WARC file.
 *********************************************************
 *********************************************************/

/******************************
 HTML2HTTP(): Convert HTML data to an HTTP server reply.
 ******************************/
function HTML2HTTP($html,$date)
  {
  // Convert date to epoch
  $date = strtotime($date);
  // Generate HTTP header
  $http="HTTP/1.1 200 OK\r\n";
  $http.="Content-Length: " . strlen($html) . "\r\n";
  $http.="Content-Type: text/html;charset=utf-8\r\n";
  $http.="Date: " . gmdate('r',$date) . "\r\n";
  return($http . "\r\n" .  $html . "\r\n");
  }

/******************************
 UUID(): Generate a UUID (because PHP doesn't have one!)
 ******************************/
function UUID($seed)
  {
  /*****
   The issue: I want a UUID, but I also want everyone who runs
   this script to generate the exact same UUID.
   Solution? Cheat! Use SHA1 instead.
   *****/
  $sha1=sha1($seed);
  $sha1[12]='4'; // "random" uuid that isn't random
  $sha1[16]='8'; // "random" uuid that isn't random
  $uuid=substr($sha1,0,8);
  $uuid.="-" . substr($sha1,8,4);
  $uuid.="-" . substr($sha1,12,4);
  $uuid.="-" . substr($sha1,16,4);
  $uuid.="-" . substr($sha1,20,12);
  return($uuid);
  } // UUID()

/******************************
 HTTP2WARC(): Convert HTTP data to WARC.
 ******************************/
function HTTP2WARC($http,$date,$userid,$tweetid)
  {
  // Convert date to epoch
  $date = strtotime($date);
  // Prepare url
  $url = "https://twitter.com/";
  $seed = $url;
  if (strlen($userid)==64) { $url.='redacted/'; } else { $url.="$userid/"; }
  $seed .= "$userid/";
  if ($tweetid) { $url.="status/$tweetid"; $seed.="status/$tweetid"; }
  // Generate WARC header
  $warc='';
  $warc.="WARC/1.0\r\n";
  $warc.="WARC-Type: response\r\n";
  $warc.="WARC-Record-ID: <urn:uuid:" . UUID("record-".$url) . ">\r\n";
  $warc.="WARC-Warcinfo-ID: <urn:uuid:" . UUID("info-".$url) . ">\r\n";
  //$warc.="WARC-Concurrent-To: <urn:uuid:1912f5eb-3770-4e5e-a17e-34f756accf17>\r\n";
  $warc.="WARC-Target-URI: $url\r\n";
  $warc.="WARC-Date: " . gmdate("c",$date) . "\r\n";
  //$warc.="WARC-Block-Digest: sha1:KWJHXSHAUTNCQLVVX7ALQ4X4G4AZHTS4\r\n";
  //$warc.="WARC-Payload-Digest: sha1:UMZ72PVGV3WUJD7FJYWK37WF4LYWDIYM\r\n";
  $warc.="Content-Type: application/http;msgtype=response\r\n";
  $warc.="Content-Length: " . (strlen($http)-2) . "\r\n";
  return($warc . "\r\n" . $http . "\r\n");
  }

?>
