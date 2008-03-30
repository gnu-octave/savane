# Setup a full local install, suitable for tests/.
# 
# Copyright (C) 2007  Sylvain Beucler
# 
# This file is part of Savane.
# 
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
# 
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
# 
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

PATH:=$(PATH):/usr/local/sbin:/usr/sbin:/sbin
/tmp/savane:
	rsync -av --exclude '.svn' . /tmp/savane
	ln -sf $(CURDIR)/../../frontend/php /tmp/savane/apache2/htdocs
	mkdir /tmp/savane/apache2/logs
	mysql_install_db --no-defaults --pid-file=pid --socket=sock --skip-networking --datadir=/tmp/savane/mysql
	-mkdir /tmp/savane/savane
	echo '<?php $$sys_dbhost=":/tmp/savane/mysql/sock";' > /tmp/savane/savane/.savane.conf.php
	echo '$$sys_incdir="'`pwd`'/../../etc/site-specific-content";' >> /tmp/savane/savane/.savane.conf.php
	echo '#$$sys_debug_on=true;' >> /tmp/savane/savane/.savane.conf.php
	echo '$$sys_debug_nobasehost=true;' >> /tmp/savane/savane/.savane.conf.php
	echo '#$$sys_debug_noformcheck=true;' >> /tmp/savane/savane/.savane.conf.php
	echo 'our $sys_dbparams="mysql_socket=/tmp/savane/mysql/sock";' > /tmp/savane/savane/savane.conf.pl
database:
	cd ../.. && ./configure --sysconfdir=/tmp/savane --with-db-options="-S /tmp/savane/mysql/sock"
	make -C ../../frontend/php/images
	for i in 1 2 3 4 5; do if mysqladmin -S /tmp/savane/mysql/sock ping; then break; fi; sleep 1; done
	cd ../.. && make database
install:
	make /tmp/savane
	make start
	make database

refresh:
	rsync apache2/apache2.conf /tmp/savane/apache2/

start: apache2-start mysql-start
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
