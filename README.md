
PHP command line tools for reading large GBIF datasets into Elasticsearch.

Installation
------------

	composer install --no-dev


Setup
------

- Place datafiles to /data
- Select which fields to index on settings.php
- (set index name on process.php and createindex.php)
	$params['index'] = 'gbif5';
	$indexParams['index']  = 'gbif5';

Commands
--------

'''Create an index'''
```
app/console data:createindex
```

'''Index data'''
```
app/console data:process --file data/verbatim.txt --start 10 --end 100

```

Todo - checks
-------------

- Picking rows from datafile for analysis
- Identifiers
- Indexing
- Analyzed data fields
- Date formats

'''TODO'''
- Upgrade to ES 5.1
- PHP library with composer?
- Set index name in one place
- set which fields are analyzed in one place?
- Document code
- Summary of GBIF data quality
- index into Amazon service


Note
----
- Expects that 
	- First row of datafile contains Darwin Core terms
	- id or gbifID is an unique identifier for each row

- If datatypes don't match, bulk indexing can fail. (E.g. indexing string into long fails.)
- If dates are in incorrect format (with/without time), bulk inserting can fail or be extremely slow

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
- Doesn't index if eof before first(?) bulk size limit
- Dataset name (from where?)
- species name can be in species or scientificName field; combine into one field without author
- Misses some rows? Chek with Tiira's first 10k lines, or is this about Kibana's time limit?
- Code cleanup

- Index name as an argument / setting (both commands)
- Define analysis and mapping in settings file instead of createIndex

- Remove fields that are only analyzed (issue)
- Handle missing coordinates
- Speed up conversion scripts


CLIPS
-----

OLD: Starting Elasticsearc and Kibana with 8 Gb of RAM

	/PATH-TO/elasticsearch
	sudo ./bin/elasticsearch -Xmx4g -Xms4g

	/PATH-TO/kibana
	sudo ./bin/kibana




**Delete an index**

	curl -XDELETE "http://elastic:changeme@192.168.56.10/INDEXNAME"

Note: Kibana holds it's own data on indices and index patterns, so you have to delete the index from Kibana also, to see it fully gone.

Note: Kibana refuses to work properly, if date field contain invalid dates(?) such as 2016-012-006

**Extract first 100 lines of file**

	head occurrence.txt -n 100 > occurrence-part.txt

**Extract every 100th line of file, including first row**

	sed -n '1~100p' input.csv > output.csv


