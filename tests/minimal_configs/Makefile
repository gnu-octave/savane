# Setup a full local install, suitable for tests/.
# Copyright (C) 2007  Sylvain Beucler
# 
# This file is part of Savane.
# 
# Savane is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

PATH:=$(PATH):/usr/local/sbin:/usr/sbin:/sbin
/tmp/savane:
	rsync -av --exclude '.svn' . /tmp/savane
	ln -sf $(CURDIR)/../../frontend/php /tmp/savane/apache2/htdocs
	mysql_install_db --no-defaults --pid-file=pid --socket=sock --skip-networking --datadir=/tmp/savane/mysql
	-mkdir /tmp/savane/savane
	echo '<?php $$sys_dbhost=":/tmp/savane/mysql/sock";' > /tmp/savane/savane/.savane.conf.php
        echo 'our $sys_dbparams="mysql_socket=/tmp/savane/mysql/sock";' > /tmp/savane/savane/savane.conf.pl
database:
	cd ../.. && ./configure --sysconfdir=/tmp/savane --with-db-options="-S /tmp/savane/mysql/sock"
	for i in 1 2 3 4 5; do if mysqladmin -S /tmp/savane/mysql/sock ping; then break; fi; sleep 1; done
	cd ../.. && make database
install: /tmp/savane start database

refresh:
	rsync apache2/apache2.conf /tmp/savane/apache2/

start: install apache2-start mysql-start
apache2-start:
	apache2 -f /tmp/savane/apache2/apache2.conf -e debug
mysql-start:
	mysqld_safe --no-defaults --pid-file=pid --socket=sock \
		--skip-networking --datadir=/tmp/savane/mysql > /dev/null &

restart: mysql-stop mysql-start apache2-restart
apache2-restart:
	kill -HUP `cat /tmp/savane/apache2/pid`

stop: apache2-stop mysql-stop
mysql-stop:
	-mysqladmin -S /tmp/savane/mysql/sock shutdown
apache2-stop:
	-kill `cat /tmp/savane/apache2/pid`

uninstall: stop
#-rm -rf mysql/db
#-rm -f apache2/logs/*
	rm -rf /tmp/savane/
