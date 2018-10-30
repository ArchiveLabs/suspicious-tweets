<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 This file: Template management.
 You can replace a template variable.
 If the replacement is false, then delete the entire line
 that contains the variable.
 *********************************************************
 *********************************************************/

/*********************************************************
 *********************************************************
 Prepare Template!
 *********************************************************
 *********************************************************/
$Templates=array();
// Templates that begin with "@" are not split apart.

$Templates['@Header']="<!DOCTYPE html>
<html>
<head>
<meta http-equiv='Content-Type' content='text/html;charset=utf-8' />
<style>
.debug { border:1px solid red}
body {
  background-color: #ddf;
  font-family: Arial,Helvetica,sans-serif;
  font-size: 12pt;
  }
.center { text-align:center; }
table { display:block; border:1px solid #444; }
thead { display:block; width:100%; }
tbody { display:block; width:100%; height:20em; overflow-y:scroll; overflow-x:hidden; }
table.sortable th { cursor:pointer; }
.banner { width:100%; text-align:center; background-color: #ccf; padding:0; margin:0; }
.banner img { max-height:300px; padding:0; margin:0; }
.pointer { cursor:pointer; }
.container {
  padding-right: 0.5em;
  padding-left: 0.5em;
  margin: 0 auto;
}
.tweet {
  padding: 0.5em;
  margin-bottom: 0.5em;
  background-color: #fff;
  border: 1px solid #888;
  border-radius: 4px;
  -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
	  box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
}
.tweet-profile { display:inline-block; width: 85%; }
.tweet-info { display:inline-block; margin-left:25%; width: 50%; }
.tweet-heading { margin-top: 0; margin-bottom: 5px; }
.details { display:inline-block; width:15em; }
.follow { margin-top: 1em; }
.profileimglg{max-width: 300px; height: auto; }
.profileimgsm{max-width: 60px; height: auto; }
.img-rounded { border-radius: 6px; }
.twimg { max-width:100%; }
.spanblock { display:inline-block; text-align:center; padding:0 0.5em; }
.col-sm { padding-right: 0.5em; padding-left: 0.5em; }
.gray { color:#888; }
.red { border:1px solid red; }
.hour { display:inline-block; width:2em; text-align:center; }
.hourbar1 { display:block; background:#c44; }
.hourbar2 { display:block; background:#44c; }
.hourpad { display:block; }
.pad>li { margin-bottom: 0.25em; padding-bottom: 0.25em; }
span.intro { display:inline-block; width:50%; vertical-align:middle; margin-left:1em; }
button.intro { width:10em; }
</style>
<title>SuspiciousTweets: %TITLE%</title>
</head>
<body>
<b class='pointer' onclick='window.location=\"?\"'>&#x1f3e0; Reconstructed based on unformatted data provided by Twitter.</b><br><br>\n";

$Templates['@Footer']="</body>
</html>";

/******************************
 TemplateReplace(): Replace a value in the template.
 If the value doesn't exist, remove all evidence from the template.
 Returns: modified template.
   T=template
   F=field to replace
   V=value to replace
 ******************************/
function TemplateReplace	($T,$F,$V)
  {
  if ($V===false) // if remove all values
    {
    foreach($T as $f=>$v)
      {
      if (strpos($v,$F) !== false) { unset($T[$f]); }
      }
    }
  elseif (is_array($T)) // replace all values (array)
    {
    foreach($T as $f=>$v)
      {
      if (strpos($v,$F) !== false) { $T[$f] = str_replace($F,$V,$v); }
      }
    }
  else // replace all values (string)
    {
    $T = str_replace($F,$V,$T);
    }
  return($T);
  }

/******************************
 IncludeSortTable(): make tables sortable.
 Returns: HTML to include.
 Class is "sortable" (with ONE T).
 ******************************/
function IncludeSortTable	()
  {
  $HTML="<script language='javascript'>\n";
  $v=file_get_contents("sorttable.js");
  $v=str_replace('Add <script src="sorttable.js"></script> to your HTML',"",$v);
  $HTML.=$v;
  $HTML.="</script>\n";
  return($HTML);
  }
?>
