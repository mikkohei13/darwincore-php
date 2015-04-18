
PHP command line tools for reading large DwC datasets

Installation
------------

	composer install --no-dev

Usage
-----
Place datafiles to /data
Select which fields to index on settings.php

Commands:
	app/console data:createindex
	app/console data:process --file data/datafile.txt


Benchmarks
----------

Indexing single record at a time:
131 seconds / 100k rows
~96 % of row handling time goes to indexing.

Bulk indexing 1 k records at a time:
13,8 seconds / 100 k rows
~68 % of processing time goes to indexing.

Bulk indexing 10 k records at a time:
11.5 seconds / 100 k rows
~67 % of processing time goes to indexing.

Bulk indexing 50 k records at a time:
12.3 seconds / 100 k rows
~66 % of processing time goes to indexing.

Bulk indexing 100 k records at a time:
13,2 seconds / 100 k rows
~63 % of processing time goes to indexing.


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

