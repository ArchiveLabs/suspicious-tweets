#!/bin/bash
# Copyright 2018 Neal Krawetz
# License: WTFPL version 2
############################################
# Prep work: convert csv to sqlite3 database.
# NOTE: This is REALLY SLOW!  Assume 2-3 hours.
############################################
# Pre-requisites
# 1. Download all of the Twitter data dump zip files.
# 2. Put them all in 1 directory.  (symlinks are fine.)
#   - DO NOT UNZIP THEM!
#   - DO NOT RENAME THEM!
# 3. Run this script.
# This script will generate:
#   tweets.sqlite :: 4.9G database file of the tweets.
#   media :: 411M database file of referencing the media files.
# Why am I not providing the sqlite database?
#   - They are huge! (Seriously, 5 gigabytes of data.)
#   - Distribution might be a violation of Twitter's terms of service.
#     (I'm not an attorney and Twitter had a long agreement document
#     that can only be understood by a room full of attorneys.  I
#     didn't read it in detail because I'm not an attorney.  Are
#     click-through licenses even enforceable?)

echo "The tweet to SQLite conversion is slow."
echo "This may take 2 hours."
echo ""

# Merge every tweet into a csv file (removes headers)
if [ ! -f tweets.csv ] ; then
  echo "Creating tweets.csv"
  unzip -n iranian_tweets_csv_hashed.zip iranian_tweets_csv_hashed.csv
  unzip -n ira_tweets_csv_hashed.zip ira_tweets_csv_hashed.csv
  echo "Combining tweets into tweets.csv"
  (
   cat iranian_tweets_csv_hashed.csv | (read ; sed -e 's@$@,Iran@')
   cat ira_tweets_csv_hashed.csv | (read ; sed -e 's@$@,IRA@')
  ) | sort -t\" -k4 > tweets.csv
  echo "Combined tweets into tweets.csv"
fi

# Merge every tweet into a sqlite3 database.
# (Needed for speed)
if [ ! -f tweets.sqlite ] ; then
  echo "Importing tweets into DB"
  sqlite3 tweets.sqlite << EOF
CREATE TABLE tweets (tweetid UNSIGNED BIGINT,userid TEXT,user_display_name TEXT,user_screen_name TEXT,user_reported_location TEXT,user_profile_description TEXT,user_profile_url TEXT,follower_count INT,following_count INT,account_creation_date DATE,account_language TEXT,tweet_language TEXT,tweet_text TEXT,tweet_time DATE,tweet_client_name TEXT,in_reply_to_tweetid UNSIGNED BIGINT,in_reply_to_userid TEXT,quoted_tweet_tweetid UNSIGNED BIGINT,is_retweet INT,retweet_userid TEXT,retweet_tweetid UNSIGNED BIGINT,latitude REAL,longitude REAL,quote_count INT,reply_count INT,like_count INT,retweet_count INT,hashtags TEXT,urls TEXT,user_mentions TEXT,poll_choices TEXT,src TEXT);
BEGIN TRANSACTION;
.mode csv
.import tweets.csv tweets
COMMIT;
EOF
  echo "Imported tweets into DB"

  echo "Creating user table";
  sqlite3 tweets.sqlite << EOF
BEGIN TRANSACTION;
CREATE TABLE users(account_creation_date DATE,follower_count INT,following_count INT,first_tweet UNSIGNED BIGINT,userid,user_display_name,user_screen_name,account_language,user_profile_description,user_profile_url,src);
INSERT INTO users SELECT DISTINCT account_creation_date,follower_count,following_count,0,userid,user_display_name,user_screen_name,account_language,user_profile_description,user_profile_url,src FROM tweets; 
COMMIT;
EOF

  echo "Optimizing tweet tables (remove redundancy from user table)"
  sqlite3 tweets.sqlite << EOF
BEGIN TRANSACTION;
ALTER TABLE tweets RENAME TO oldtweets;
CREATE INDEX olduserid_idx ON oldtweets(userid);
CREATE TABLE tweets (tweetid UNSIGNED BIGINT, tweet_time DATE, in_reply_to_tweetid UNSIGNED BIGINT, quoted_tweet_tweetid UNSIGNED BIGINT, retweet_tweetid UNSIGNED BIGINT, is_retweet INT, latitude REAL, longitude REAL, quote_count INT, reply_count INT, like_count INT, retweet_count INT, userid TEXT, tweet_text TEXT, user_reported_location TEXT, tweet_language TEXT, tweet_client_name TEXT, in_reply_to_userid TEXT, retweet_userid TEXT, hashtags TEXT, urls TEXT, user_mentions TEXT, poll_choices TEXT);
INSERT INTO tweets SELECT tweetid, tweet_time, in_reply_to_tweetid, quoted_tweet_tweetid, retweet_tweetid, is_retweet, latitude, longitude, quote_count, reply_count, like_count, retweet_count, userid, tweet_text, user_reported_location, tweet_language, tweet_client_name, in_reply_to_userid, retweet_userid, hashtags, urls, user_mentions, poll_choices FROM oldtweets order by userid,tweetid;
COMMIT;
EOF

  echo "Cleaning up (this is slow, but worth it)"
  sqlite3 tweets.sqlite << EOF
DROP TABLE oldtweets;
VACUUM;
EOF
  echo "Creating tweet indexes";
  sqlite3 tweets.sqlite << EOF
CREATE UNIQUE INDEX tweetid_idx ON tweets(tweetid);
CREATE INDEX userid_idx ON tweets(userid);
CREATE INDEX userid2_idx ON users(userid);
CREATE INDEX tweettime_idx ON tweets(tweet_time);
EOF

  echo "Creating first_tweet record (VERY SLOW)"
  sqlite3 tweets.sqlite << EOF
BEGIN TRANSACTION;
UPDATE users SET first_tweet = (SELECT tweetid FROM tweets WHERE tweets.userid=users.userid ORDER BY tweetid LIMIT 1);
COMMIT;
VACUUM;
EOF

fi

# Generating a listing of every zip file
if [ ! -f media.csv ] ; then
  echo "Extracting media listing"
  # z = zip file
  # zp = path in zip file
  for z in *media*.zip ; do
    unzip -l "$z" |
    grep '^  *[1-9]' |
    grep -v _MACOSX | grep -v DS_Store |
    grep -v ' files' | awk '{print $4}' | while read zp ; do
      ftype=${zp%/*}
      ftype=${ftype##*/}
      tweetid=${zp##*/}
      tweetid=${tweetid%%-*}
      echo "$tweetid,$ftype,$z,$zp"
    done
  done > media.csv
fi

# Generate listing of profile/banner content
if [ ! -f profile.csv ] ; then
  echo "Extracting profile/banner listing"
  # z = zip file
  # zp = path in zip file
  for z in *profile*.zip ; do
    unzip -l "$z" |
    grep '^  *[1-9]' |
    grep -v _MACOSX | grep -v DS_Store |
    grep -v ' files' | awk '{print $4}' | while read zp ; do
      ftype=${zp%.*}
      ftype=${ftype##*_}
      uid=${zp%_*}
      uid=${uid##*/}
      echo "$uid,$ftype,$z,$zp"
    done
  done > profile.csv
fi

# Convert media listing into database
# (separate database for speed)
if [ ! -f media.sqlite ] ; then
  echo "Importing media listing"
  sqlite3 media.sqlite << EOF
CREATE TABLE media (tweetid UNSIGNED BIGINT,type,zip,zippath);
BEGIN TRANSACTION;
.mode csv
.import media.csv media
COMMIT;
CREATE TABLE profile (uid,type,zip,zippath);
BEGIN TRANSACTION;
.mode csv
.import profile.csv profile
COMMIT;
CREATE INDEX media_idx ON media(tweetid);
CREATE INDEX profile_idx ON profile(uid);
EOF
  echo "Imported media listing"
fi

# There are two more zip files:
# iranian_periscope_hashed.zip  ira_periscope_hashed.zip
# Neither contains file data.

# To expand URLs:
# wget --max-redirect=1  -S -O - http://t.co/iFojzaCe4K 2>&1 | grep '^Location:' | awk '{print $2}' | tail -1

echo "Done!"
