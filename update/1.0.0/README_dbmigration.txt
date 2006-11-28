===========================================================
SAVANE DATA BASE MIGRATION to TDEV_2003-09-05_CERN branch
===========================================================

Authors: Derek Feichtinger <derek.feichtinger@cern.ch>
	 Yves Perrin <yves.perrin@cern.ch>
Date:   Dec. 5. 2003


1. INTRODUCTION
------------

This document describes how to migrate an old Savannah data base to
the new format which was introduced in the TDEV_2003-09-05_CERN
development branch.

A number of scripts for doing the migration and repairing some deficiencies
of existing data bases are provided.



2. REQUIREMENTS:
-------------
- an old Savannah data base
- a new (post TDEV_2003-09-05_CERN) Savannah data base, initialized 
  by executing Savannah's 'make database' command
- a MySQL user able to access both data bases. 
- perl modules: Term::ReadKey, DBI


Recommended: 

- Do the migration on a test machine. Install the old Savannah
  software and copy the data base to the test server and use the
  savannah_change_servername.pl script to change the server name in
  the existing data base to the name of the test system.
- Install the new Savannah software (TDEV_2003-09-05_CERN branch) on
  the same machine, create and initialize the data base as described
  in the Savannah installation documents. Use a different data base
  name!
  This setup will allow you to compare the data bases by using
  the old and new front ends to view entries.


3. MIGRATION STEP BY STEP MINI-INSTRUCTIONS:
-----------------------------------------

This describes the ideal case (which should be ok for you, if you
used the old Savannah out of the box, without making any changes
to the data base).

- go to the directory where you downloaded the migration scripts

- Modify the dbmapper.conf file, which is used by most of the scripts
  (contains definition of data base names, etc)

- use the renumber_items script which acts on the source data
  base. This will correct some problems with IDs that many
  installations have.  You may opt to skip this (refer to the section
  describing the helper scripts in this document)

  $> ./renumber_items.pl

- run the migrate.sh shell script which calls the actual migration
  perl scripts. You may need to provide the MySQL user's password
  several times.

  $> ./migrate.sh



4. DESCRIPTION OF THE SCRIPTS:
---------------------------


Helper scripts to be used on the old data base prior to the
migration:
-----------------------------------------------------------

savannah_change_servername.pl: allows you to relocate the Savannah
             data base to a different server (just exchanges the old
             servername against the new one). Useful to test the
             installation on a different host.

renumber_items.pl: Even in the old support, bug, and task trackers the
             item with ID 100 should not contain a regular entry, but
             the reserved 'none' entry. The old initialization scripts
             did not take care of creating the correct initialiation
             values for some trackers. Some of you may have noticed
             that once you have more than 100 tasks suddenly all bugs
             have a dependency on the task with ID 100.  This script
             renumbers the items by moving those below 100 to the top
             of the stack (i.e. these items end up with a new, high
             ID. Users may not like that).
	     You can migrate without renumbering (just moving off the
	     items with ID 100 by hand, leaving the others below 100
	     untouched), but there is no guarantee that later updates
	     of Savannah will not require other reserved IDs below 100!


Migration scripts
-----------------

migrate.sh:  shell script calling the following 4 perl scripts one
	     after the other.

----
All of these scripts can be run with an '-h' flag to provide information
about themselves.

dbmapper.pl: script to map entire data base columns from a source
             onto a target data base. The program uses a configuration
             file where all special mappings are listed (called
	     map.in in these examples). It will write out a SQL command
	     file which can be piped into mysql to migrate the columns
	     (this way it's easy to spot failing SQL statements).
	     It writes out a new input file, in which unmapped or lost
	     columns are printed in comment lines. From these commented
	     lines you can build a better input file. This should lead
	     you iteratively to a correct transfer of all columns.

db_tracker_configurator.pl: script to migrate the project configurations
             of the old trackers to the new data base. The old trackers
	     offered limited configuration possibilities (e.g. the old
	     support tracker offered the configuration of categories),
	     and these need to be mapped onto the new structure. The
	     scripts outputs a SQL command file which can be piped into
	     mysql to be executed.
	     

db_move_entries.pl: script to actually move the tracker entries to the
             new tracker structure. The script is written in a way
             that a whole item with all its associated data (e.g. all
             follow-up comments and dependency pointers) is read and
             then transferred. Contrary to the previous two scripts,
             this one does not produce an SQL command file to be
             executed afterwards. The SQL commands are directly
             executed from within the script but the last series of
             SQL commands can always be found in a logfile for
	     debugging purposes.

loose_ends.pl: this script takes care of some problems which do not
             fit into the previous scripts (like rewriting some URLs
             for certain fields, where the naming convention has
             changed).
----


For questions, bugs or help, contact 
   derek.feichtinger@cern.ch or
   yves.perrin@cern.ch 
or submit your problem as a bug report on our Savannah installation
using this URL:
         http://savannah.cern.ch/bugs/?func=addbug&group=savcern
