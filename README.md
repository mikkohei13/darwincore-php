
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

Handle missing dates
Code cleanup
Progress indicator
Speed
Specify from which line to start indexing from

