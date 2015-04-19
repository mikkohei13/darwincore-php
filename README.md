
PHP command line tools for reading large DwC datasets into Elasticsearch.

Installation
------------

	composer install --no-dev

Usage
------

Setup

- Place datafiles to /data
- Select which fields to index on settings.php
- (set index name on process.php and createindex.php)

Commands

Create an index
```
app/console data:createindex
```

Index data
```
app/console data:process --file data/verbatim.txt --start 10 --end 100

```

Expectations
------------
- First row of datafile contains Darwin Core terms
- eventDate format is yyyy-MM-dd HH:mm:ss
- catalogNumber is an unique identifier for each row


Benchmarks (2015-04-18)
-----------------------

Indexing single record at a time:
- 131 seconds / 100k rows
- ~96 % of row handling time goes to indexing.

Bulk indexing 1 k records at a time:
- 13,8 seconds / 100 k rows
- ~68 % of processing time goes to indexing.

Bulk indexing 10 k records at a time:
- 11.5 seconds / 100 k rows
- ~67 % of processing time goes to indexing.

Bulk indexing 50 k records at a time:
- 12.3 seconds / 100 k rows
- ~66 % of processing time goes to indexing.

Bulk indexing 100 k records at a time:
- 13,2 seconds / 100 k rows
- ~63 % of processing time goes to indexing.

Example indexing
- Birdlife Finland: Tiira information service -dataset http://www.gbif.org/dataset/be2af664-2990-4153-99b5-d92bbd8cdb0e on 2015-04-19 
- 10 k records at a time
- With index mapping as in https://github.com/mikkohei13/darwincore-php/blob/171876c71a788a59afe30613830cd2c48f028a1b/src/Data/Commands/Createindex.php
- Time to index
	- 11.964.093 rows indexed in 28,14 minutes (= 1688,6 seconds), which is 7085 records / second
	- 60,8 % of processing time goes to indexing
- Indexed data uses
	- 56,2 MB of Elasticsearch memory
	- 10.4 MB of Lucene memory
	- 1,8 GB of disk space

TODO
----

- index name as an argument / setting (both commands)
- Batch update
- Geoshape
- Handle missing dates; show only amount while indexing and at the end
- Code cleanup
- (Progress indicator)
- Speed
- Specify from which line to start indexing from; http://stackoverflow.com/questions/514673/how-do-i-open-a-file-from-line-x-to-line-y-in-php - make sure correct amount of rows are skipped
- Handle missing coordinates
- rows = end
- skip = start
- remove empty fields
- set index properties (shards, replicas)
- split date and time into parts
- define analysis and mapping in settings file



CLIPS
-----

Kibana
localhost:5601

Marvel
http://localhost:9200/_plugin/marvel/sense/index.html

	# Delete the `gbif` index
	DELETE /gbif

	# Check new mapping
	GET /gbif/_mapping/occurrence

	GET /gbif/_mapping?pretty=true

