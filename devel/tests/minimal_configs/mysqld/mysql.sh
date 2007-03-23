#!/bin/bash
opt="--no-defaults --pid-file=pid --socket=sock --skip-networking --datadir=`pwd`/db"
mysql_install_db $opt
mysqld $opt
