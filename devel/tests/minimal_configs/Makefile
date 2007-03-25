PATH:=$(PATH):/usr/local/sbin:/usr/sbin:/sbin
/tmp/savane:
	rsync -av --exclude '.svn' . /tmp/savane
	ln -sf $(CURDIR)/../../../frontend/php /tmp/savane/apache2/htdocs
	mysql_install_db --no-defaults --pid-file=pid --socket=sock --skip-networking --datadir=/tmp/savane/mysql
	-mkdir /tmp/savane/savane
	echo '<?php $$sys_dbhost=":/tmp/savane/mysql/sock";' > /tmp/savane/savane/.savane.conf.php
install: /tmp/savane

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
