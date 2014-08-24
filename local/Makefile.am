# Local Savannah Installation - for testing and development
#

EXTRA_DIST = \
	     apache2/conf/apache2.conf.template \
	     savane/savane.php.conf.template

all:
	@echo ""
	@echo "This make file provides targets to create the MySQL database and"
	@echo "apache configuration for a locally running Savannah server."
	@echo ""
	@echo "Please see HACKING.md for details"
	@echo ""

# Create a directory for the local apache setup
.PHONY: local-apache2-make-dir
local-apache2-make-dir:
	mkdir -p $(abs_top_srcdir)/local/apache2/logs/
	mkdir -p $(abs_top_srcdir)/local/apache2/conf/

# Create an apache configuration file,
# With the absolute path of the current directory
# (inside the apache conf, it will point to the PHP files and
#  the Savane php config file)
$(abs_top_srcdir)/local/apache2/conf/apache2.conf: apache2/conf/apache2.conf.template
	sed -e 's;@ABSSRCDIR@;$(abs_top_srcdir);g' $< > $@

# Dummy target to create the directories and the configuration file.
.PHONY: local-apache2-make-conf
local-apache2-make-conf: local-apache2-make-dir $(abs_top_srcdir)/local/apache2/conf/apache2.conf


# Ensures the apache2 binary was found.
# After "configure", the final Makefile will have the path (or an empty string).
.PHONY: local-apache2-bin
local-apache2-bin:
	@(test -n "@APACHE2_SERVER_BIN@" \
	  || { echo "error: apache2 binary not found during ./configure" >&2 ; \
	       exit 1 ; })

# Start the local apache server (as the current user),
# Using the custom configuration.
.PHONY: local-apache2-start
local-apache2-start: local-apache2-make-dir local-apache2-make-conf local-apache2-bin
	"@APACHE2_SERVER_BIN@" -f $(abs_top_srcdir)/local/apache2/conf/apache2.conf -e debug

# Stop the local apache server
.PHONY: local-apache2-stop
local-apache2-stop: local-apache2-bin
	@(test -e $(abs_top_srcdir)/local/apache2/pid \
	   || { echo "error: apache2 pid file not found" >&2 ; exit 1 ; })
	kill $$(cat $(abs_top_srcdir)/local/apache2/pid)


# Creates a PHP configuration file for Savane.
# The configuration file has two settings critical for this local setup:
#  1. The path of the Unix socket for the MySQL database.
#     (set with ABSSRCDIR)
#  2. The name of the configuration ('site admin') project in the database,
#     Which must be set for Savannah to work properly.
#     (set with ADMINPROJECTNAME)
.PHONY: internal-local-savane-make-conf
internal-local-savane-make-conf:
	test -n "$(ADMINPROJECT)" \
	    || { echo "Error: 'ADMINPROJECT' parameter is empty" >&2 ; exit 1 ; }
	mkdir -p $(abs_top_srcdir)/local/savane
	sed -e 's;@ABSSRCDIR@;$(abs_top_srcdir);g' \
	    -e 's;@ADMINPROJECTNAME@;$(ADMINPROJECT);g' \
	    $(abs_top_srcdir)/local/savane/savane.conf.php.template \
	    > $(abs_top_srcdir)/local/savane/.savane.conf.php

# Creates a Savane PHP config file, pointing to 'siteadmin' as the name of
# the administration project.
# 'siteadmin' is created when using the './db/mysql/bootstrap.sql' as a database demo.
# This target is used with 'local-db-setup-tiny'.
.PHONY: local-savane-make-conf-siteadmin
local-savane-make-conf-siteadmin:
	$(MAKE) internal-local-savane-make-conf ADMINPROJECT=siteadmin

# Creates a Savane PHP config file, pointing to 'administration' as the name of
# the administration project.
# This is used by the real GNU Savannah database.
.PHONY: local-savane-make-conf-administration
local-savane-make-conf-administration:
	$(MAKE) internal-local-savane-make-conf ADMINPROJECT=administration


# Ensures the MySQL related binaries were found.
# After "configure", the final Makefile will have the path (or an empty string).
.PHONY: local-db-check-bin
local-db-bin:
	@(test -n "@MYSQL_INSTALLDB_BIN@" \
	  || { echo "error: mysql_installdb_bin binary not found during ./configure" >&2 ; \
	       exit 1 ; })
	@(test -n "@MYSQLD_SAFE_BIN@" \
	  || { echo "error: mysqld_safe binary not found during ./configure" >&2 ; \
	       exit 1 ; })
	@(test -n "@MYSQLADMIN_BIN@" \
	  || { echo "error: mysqladmin binary not found during ./configure" >&2 ; \
	       exit 1 ; })
	@(test -n "@MYSQL_BIN@" \
	  || { echo "error: mysql binary not found during ./configure" >&2 ; \
	       exit 1 ; })

# Creates a directory (+required files) for a new,
# empty instance of a MySQL Database.
# This target DOES NOT require the server to be running.
.PHONY: local-db-create-dir
local-db-create-dir:
	mkdir -p $(abs_top_srcdir)/local/db
	if ! test -d $(abs_top_srcdir)/local/db/mysql ; then \
	  "@MYSQL_INSTALLDB_BIN@" --no-defaults \
	           --pid-file=pid --socket=sock \
	           --skip-networking --datadir=$(abs_top_srcdir)/local/db ; \
	fi

# Creates an empty MySQL directory (if it doesn't exist),
# And starts the MySQL server (as the current user),
# Allowing connections only through a unix socket.
# The target verifies the server is correctly started, or fails.
# NOTE:
#  This target DOES NOT populate the database (or even creates
#  the 'savane' database).
.PHONY: local-db-start
local-db-start: local-db-check-bin \
		local-db-check-not-running \
		local-db-create-dir \
		internal-local-db-start \
		local-db-wait-for-server

# Internal target, to start the database server process
.PHONY: internal-local-db-start
internal-local-db-start:
	nohup "@MYSQLD_SAFE_BIN@" --no-defaults --pid-file=pid --socket=sock \
	       --datadir=$(abs_top_srcdir)/local/db \
	       --skip-networking \
	         > $(abs_top_srcdir)/local/db/stdouterr.log 2>&1 &

# Target to verify the MySQL server is NOT running
.PHONY: local-db-check-not-running
local-db-check-not-running:
	@("@MYSQLADMIN_BIN@" -S $(abs_top_srcdir)/local/db/sock \
	                   -u root --silent ping \
	   && { echo "** error: MySQL server is already running. ">&2 ; \
	        echo "   use 'make local-db-stop' or 'make local-db-delete' ">&2 ; \
	                exit 1 ; } \
	           || true )

# Target to wait for the MySQL server to start.
# Waits for 10 seconds for it to start, or fails.
.PHONY: local-db-wait-for-server
local-db-wait-for-server:
	@(for i in $$(seq 10) ; do \
	    "@MYSQLADMIN_BIN@" -S $(abs_top_srcdir)/local/db/sock \
	                   -u root --silent ping && exit 0 ; \
	    echo "Waiting for MySQL-Server to start .... " ; \
	    sleep 1; \
	  done ; \
	  echo "Error: MySQL-Server failed to start. " >&2 ; \
	  echo "  Check $(abs_top_srcdir)/local/db/*.log" >&2 ; \
	  exit 1 ; )

# Target to ensure the MySQL server is running.
.PHONY: local-db-check-running
local-db-check-running:
	@("@MYSQLADMIN_BIN@" -S $(abs_top_srcdir)/local/db/sock \
	                   -u root --silent ping \
	   || { echo "Error: MySQL-Server is not running" >&2 ; \
	        exit 1; } )

# Target to quickly connect with the command-line interface
# (mysql) to the locally running server.
.PHONY: local-db-cli
local-db-cli: local-db-check-running
	"@MYSQL_BIN@" -S $(abs_top_srcdir)/local/db/sock -u root

# Target to stop the MySQL server process
.PHONY: local-db-stop
local-db-stop: local-db-check-running
	"@MYSQLADMIN_BIN@" -S $(abs_top_srcdir)/local/db/sock \
	                   -u root shutdown

# Target to stop the MySQL server process, and
# DELETE the local database directory
# (effectively allows re-creating/reloading the database from scratch)
.PHONY: local-db-delete
local-db-delete:
	( if "@MYSQLADMIN_BIN@" -S $(abs_top_srcdir)/local/db/sock \
	                   -u root --silent ping >/dev/null; then \
	      "@MYSQLADMIN_BIN@" -S $(abs_top_srcdir)/local/db/sock \
	                       -u root shutdown ; \
	  fi )
	rm -rf $(abs_top_srcdir)/local/db

# Target to create the Savane database
.PHONY: local-db-create-savane-db
local-db-create-savane-db:
	"@MYSQL_BIN@" -S $(abs_top_srcdir)/local/db/sock -u root \
	              -e "CREATE DATABASE savane;"

# Internal target to load an SQL dump file into the MySQL database
.PHONY: internal-load-database-dump
internal-load-database-dump:
	@test -n "$(DUMPFILE)" \
	   || { echo "Error: DUMPFILE not defined" >&2 ; exit 1 ; }
	@test -e "$(DUMPFILE)" \
		|| { echo "** Error: Sql Dump file $(DUMPFILE) not found. " >&2 ; \
		echo "You'll need to get it from fencepost.gnu.org." >&2 ; \
	        echo "run (assuming you have access): " >&2 ; \
	        echo "  rsync -avh 'fencepost.gnu.org:/home/a/agn/sv-db/$(DUMPFILE)' ." >&2 ; \
		exit 1 ; }
	bzip2 -dc "$(DUMPFILE)" \
	  | "@MYSQL_BIN@" -S $(abs_top_srcdir)/local/db/sock -u root savane

# Initializes a new MySQL database, and loads the absolute minimal configuration
# for a working savane - using the template in './db/mysql/*.sql'.
# NOTE: when this target completes, the MySQL server is running.
.PHONY: local-db-setup-tiny
local-db-setup-tiny: local-savane-make-conf-siteadmin \
                   local-db-start \
		   local-db-create-savane-db
	cat $(abs_top_srcdir)/db/mysql/table_*.structure \
	      $(abs_top_srcdir)/db/mysql/table_*.initvalues \
	      $(abs_top_srcdir)/db/mysql/bootstrap.sql \
	  | "@MYSQL_BIN@" -S $(abs_top_srcdir)/local/db/sock -u root savane

# Creates a new MySQL database, loading a small portion from the real GNU Savannah database.
# It will have just three projects and few users.
# NOTE: when this target completes, the MySQL server is running.
.PHONY: local-db-setup-small
local-db-setup-small: local-savane-make-conf-administration \
		    local-db-start \
		    local-db-create-savane-db
	$(MAKE) internal-load-database-dump DUMPFILE=savane-demo-db.small.sql.bz2

# Creates a new MySQL database, loading a medium  portion from the real GNU Savannah database.
# It will have just few dozen GNU projects, and few thousands of users.
# NOTE: when this target completes, the MySQL server is running.
.PHONY: local-db-setup-medium
local-db-setup-medium: local-savane-make-conf-administration \
		    local-db-start \
		    local-db-create-savane-db
	$(MAKE) internal-load-database-dump DUMPFILE=savane-demo-db.medium.sql.bz2

# Creates a new MySQL database, loading a the entire real GNU Savannah database.
# NOTE: when this target completes, the MySQL server is running.
.PHONY: local-db-setup-big
local-db-setup-big: local-savane-make-conf-administration \
		    local-db-start \
		    local-db-create-savane-db
	$(MAKE) internal-load-database-dump DUMPFILE=savane-demo-db.big.sql.bz2
