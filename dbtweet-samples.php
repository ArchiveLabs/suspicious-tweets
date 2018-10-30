<?php
/*********************************************************
 *********************************************************
 dbtweets: Given the Twitter data dump of tweets related to
 election influence campaigns.  This code reconstructs and
 datamines the tweets from the raw data dump.

 Copyright 2018 Neal Krawetz
 License: WTFPL version 2

 This file:
 Sample research findings.
 *********************************************************
 *********************************************************/
function GetSamples()
  {
  global $Templates;
  $HTML=$Templates['@Header'];
  echo TemplateReplace($Templates['@Header'],'%TITLE%',"Samples");
?>
<div class='tweet'>
<H1>Samples</H1>
It's easy enough to browse the data, but what sort of research findings are there?  Here's a few walk-through examples.

<H2>Example 1: Similar accounts</H2>
People use tools, and their methods often have consistencies.
The archive can be used to find very similar accounts.
<ol class='pad'>
<li>Use the <button class='intro' onclick='window.location="?userid=all"'>All accounts</button> button to view all of the foreign influence accounts from the Twitter archive. This will display a large table that shows the account names, creation date, and first tweet time.</li>
<li>If you click on the table heading 'Elapsed', it will sort all of the results by the elapsed time between when the account was created and when it first tweeted. Click it a second time to sort in descending order.</li>
<li>Notice how there are lots of accounts that were created on the same date, and first used at the same time, years later. Start comparing accounts that have similar creation and first-use dates.</li>
<li>For example, there are four Russian IRA accounts that were created on 2013-07-25 and first used on 2015-01-06 (elapsed time: 1 year, 5 months, 12 days). Open each account in a new browser tab:
<a target='sample1' href='?userid=d225180304e4fafe5af936306b254e2cf5f2a950d6947f94c6ab44f7d51e2606'>Account1</a>,
<a target='sample2' href='?userid=f1dc2d4ba0ac7fa438def6de29cad092542d54ae4364b9e193ae7c1a8653f382'>Account2</a>,
<a target='sample3' href='?userid=c567f55d5430039ab5b53980a337cd6e3399e842f186e3930a92d7f2e11d27d6'>Account3</a>, and
<a target='sample4' href='?userid=56078c7539382d72f23d6958f0c74928ae27bd6e6a798e3cfcfe661696d3fc62'>Account4</a>.
</li>
<li>Look over each of the four accounts. By themselves, each appears to be person interested in the Black Lives Matter movement.</li>
<li>Now look at the accounts as a group. Are there similarities between the accounts?
<br><br>
<ul class='pad'>
<li>The 1st tweet from each account was made on the same day (2015-01-06) and contains 4 pictures.</li>
<li>The 2nd tweet from each account was made on the same day (2015-01-06) and contains 2 pictures.</li>
<li>The 3rd tweet from each account was made the next day (2015-01-07) and contains a short saying.</li>
<li>The next few tweets from each account contain random quips. In fact, of the first 25 tweets from each account, tweets 3-25 all contain quips and were all tweeted in a burst -- seconds apart.</li>
<li>Eventually, there is a single political tweet (likely after the account gained a few followers).  Different accounts gained followers at different rates.</li>
<li>The more they tweeted, the more political they became.</li>
<li>By the time they stopped tweeting, 3 of the 4 bot accounts were almost entirely political tweets.  (The 4th was a combination of political, music, and sports.)</li>
</ul>
These are not real people tweeting. These are faux accounts that appear to look like real people but that all follow the same programatic pattern.
</ol>
This isn't the only example. If you look for accounts with the same elapsed time (duration between account creation and first tweet), you might see similar patterns between those accounts.

<H2>Example 2: Similar content</H2>
Many of the accounts have HTML-encoded text.
<br><br>
For example, many tweets by the Russian IRA "<a href='?userid=2547141851&offset=53760'>@ChicagoDailyNews</a>" account contain "&amp;amp;" instead of "&amp;". (That's how HTML encodes the ampersand character.)
<br><br>
This is NOT a bug in this recreation engine or in how Twitter stored tweet info.  Rather, this is a bug in the scripts used by both IRA and Iran to post automated tweets.
<br><br>
How do we know it's a bot bug and not Twitter?  There's some tweets that just contain "&amp;" to mean 'and'.  If it were Twitter's bug, they would all say "&amp;amp;".
<br><br>
There are also some instances of '&amp;amp;quot;', which is a double HTML-encoding bug. (It should be an ampersand followed by a double quote: &amp;".)

<H2>Example 3: Irregular content</H2>
There's a few user accounts that have irregular content. Such as <a target='account' href='?userid=7271c99b1d0d235f051113b76d4e6c765ebccb5c743f09f89376c1576690ee54&offset=300'>account</a>.
This person appears to be America and talks about American social issues.
He starts using "Twitter Web Client", then switched to "masss post5" as he became more
political.  His last post used "Tweetdeck" and posted Russian content.
<br><br>
This is an example of a screw-up.  The user managing the account posted content to the wrong account. (When you're managing a few hundred accounts, it may be difficult to keep them straight.) There's a couple of screw-up instances -- look for accounts where the Twitter client changes. Each change should be associated with a dramatic change in the faux personality assigned to the account.

<H2>Example 4: Scary</H2>
After looking over the data for a while, you might notice some really scary aspects. For example:
<ul class='pad'>
<li>The accounts are mainly divided into 3 categories: news/journalists (accounts that pretend to be media in order to give their biases more credibility), "people" (fake users), and anonymous groups (these are the minority).</li>
<li>For clusters of accounts, there might be one account that is extreme in one direction, one extreme in the opposite direction, and a variety of accounts inbetween. For example, you might see a strong anti-imigrant account and strong pro-imigrant account in the same cluster.  Or you might see a cluster with some pro-Trump accounts and some anti-Trump accounts. These fake accounts are not trying to support one side of any given debate. By playing both sides of a debate, these accounts are trying to create a division.</li>
<li>The elapsed time shows that some accounts were created 4 or more years before being used. This makes them "sleeper accounts". Iran and Russia are playing a very long game, with sleeper accounts that have been waiting years to be activated.</li>
</ul>

</div>
<?php
  echo $Templates['@Footer'];
  return;
  } // GetNewUsers()
?>
