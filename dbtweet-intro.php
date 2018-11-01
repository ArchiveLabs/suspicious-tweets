<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 This file:
 Intro and initial links.
 *********************************************************
 *********************************************************/
function GetIntro()
  {
  global $Templates;
  $HTML=$Templates['@Header'];
  echo TemplateReplace($Templates['@Header'],'%TITLE%',"About");
?>
<div class='tweet'>
<H1>About SuspiciousTweets</H1>
Following the foreign influence campaigns that impacted the US 2016 Elections, social media sites began to crack down on fraudulent accounts. Many of these influence accounts impersonate people and organizations.
<br><br>
On 17-October-2018, Twitter <a href='https://blog.twitter.com/official/en_us/topics/company/2018/enabling-further-research-of-information-operations-on-twitter.html'>released</a> raw data related to two of these foreign influence campaigns: Russia's "Internet Research Agency" (IRA) and a separate campaign from Iran.  These campaigns are denoted in the data as "IRA" and "Iran".
<br><br>
The <a href='https://about.twitter.com/en_us/values/elections-integrity.html#data'>raw data</a> released by Twitter is publicly available and intended for researchers. However, the raw data does not "look" like tweets. Instead, it is a collection of comma separated values (CSV) files and raw media (pictures, videos, etc.).  This web site reconstructs the tweets based on the raw data, allowing researchers and curious observers to dig through the data.

<H2>Start!</H2>
Here are some sample searches that you can use as a starting point:
<br><br>
<button class='intro' onclick='window.location="?userid=all"'>All accounts</button><span class='intro'>Most account names are redacted. This shows the account name, date the account was created, date of the account's first tweet, and the elapsed time between creation and first tweet.</span>
<br><br>
<button class='intro' onclick='window.location="?userid=pop"'>Popular</button><span class='intro'>Accounts with at least 5,000 followers are popular enough to not be redacted. This shows the most popular accounts.</span>
<br><br>
<button class='intro' onclick='window.location="?userid=news"'>News</button><span class='intro'>Some IRA and Iran accounts claimed to be news outlets to give their biases more credibility.</span>
<br><br>
<button class='intro' onclick='window.location="?userid=journalist"'>Journalists</button><span class='intro'>Some IRA and Iran accounts claimed to be journalists to give their biases more credibility.</span>
<br><br>
<button class='intro' onclick='window.location="?userid=new"'>Creations</button><span class='intro'>When were the accounts created?</span>
<br><br>
<button class='intro' onclick='window.location="?date=all"'>Tweet dates</button><span class='intro'>When were the tweets posted? This shows the number of tweets per day in the data, allowing you to drill down into specific dates, accounts, and tweets.</span>
<br><br>
If you still don't know where to start, take a look at some of the <a href='?samples'>research examples</a>.

<H2>Caveats</H2>
<ul class='pad'>
<li>Because this is a recreation of tweets from raw data, the formatting does not look exactly like Twitter.</li>
<li>Twitter anonymized account names that have less than 5,000 followers. These are denoted by the word '[redacted]'. However, Twitter assigned each anonymized account a unique random hash value. Different '[redacted]' accounts can still be tracked and linked to a unique set of tweets. The only unknown is the unredacted account name.</li>
<li>Tweets can reference URLs. Twitter did not provide reference URLs for suspended accounts. For this reason, some URLs are not recreated with hyperlinks.</li>
<li>The data does not include account changes over time. The account name, banner image, description, follower count, etc. represent the content when the account was disabled by Twitter.</li>
<li>For the last tweet, the data does not distinguish whether the account stopped on its own or was shutdown by Twitter. (Most likely, this denotes the last tweeet before the account was shutdown.)</li>
</ul>
<H2>Credits</H2>
The raw Twitter data came from Twitter.
This tweet recreation and analysis web site was created by <a href='http://www.hackerfactor.com/'>Hacker Factor</a>, with assistance and hosting provided by the <a href='https://archive.org/'>Internet Archive</a> and <a href='https://archivelab.org/'>Archive Labs</a>. 
The analysis of this data is open source under a WTFPL license. Get the source code at <a href='https://github.com/ArchiveLabs/twitter-foreign-influence'>https://github.com/ArchiveLabs/suspicious-tweets</a>.
The raw data is available from  <a href='https://about.twitter.com/en_us/values/elections-integrity.html#data'>Twitter</a> or from the Internet Archive's mirror: <a href='https://archive.org/details/twittersdataarchive'>https://archive.org/details/twittersdataarchive</a>.
</div>
<?php
  echo $Templates['@Footer'];
  return;
  } // GetNewUsers()
?>
