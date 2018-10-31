dbtweets: Given the Twitter data dump of tweets related to
election influence campaigns.  This code reconstructs and
datamines the tweets from the raw data dump.

Copyright 2018 Neal Krawetz
License: WTFPL version 2

How to install:

1. Everything is relative to the "www/" directory.
   You need 2 directories:  ./www/ and ./data/
   Your web server will only access "www/", but data is used
   for additional dependency files.

   (Why ".."?  Simple security: remote users cannot directly access
   the dependency files through the web browser.)

2. Put all php and js and sh files in "../data/".

3. Download all of the Twitter dump zip files.

   https://about.twitter.com/en_us/values/elections-integrity.html#data

   NOTE: It's about 365G and includes both IRA and Iran content.

4. Temporarily:  Put all of the zip files in ../data/
   Symbolic links are fine.
   DO NOT UNZIP THE FILES!
   DO NOT RENAME THE FILES!

5. Convert the CSV and zip file contents into an SQLite3 database.

     cd data

     ./PrepSQLDB.sh

   NOTE: This is SLOW.  It might take 2-3 hours.
   When it finishes, you will have:

	tweets.sqlite  (4.9G)

	media.sqlite   (411M)

   There are also a bunch of temp files that you no longer need:
	*.zip
	*.csv

6. SQLite3 is not fast enough for some searches, even with indexes turned on.
   Cache the slowest searches for speed:

     cd data

     php cli.php cache

   This will take a few hours to complete.
   After the first minute, you should have data/cache/cache-dates-all.gz

   When it exists, you're ready to use the site (even while other cache
   files are being generated).

7. Copy data/index.php to www/index.php

8. That's it!  Your web browser should hit your web server and it will
   access index.php.

   NOTE: If you don't know how to configure the web server to use the
   index.php file, well, that's outside the scope of this little script.
   I'm sure your web server software has a tutorial somewhere.

