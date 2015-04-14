
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

TODO
----

- Geoshape
- Handle missing dates; show only amount while indexing and at the end
- Code cleanup
- (Progress indicator)
- Speed
- Specify from which line to start indexing from; http://stackoverflow.com/questions/514673/how-do-i-open-a-file-from-line-x-to-line-y-in-php - make sure correct amount of rows are skipped
- Handle missing coordinates
- rows = end
- skip = start

CLIPS
-----

Kibana
localhost:5601

Marvel
http://localhost:9200/_plugin/marvel/sense/index.html

	# Delete the `gb` index
	DELETE /gbif

	# Check new mapping
	GET /gbif/_mapping/occurrence

	GET /gbif/_mapping?pretty=true

