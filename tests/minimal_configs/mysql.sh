#!/bin/bash
# Start a user MySQL server
export PATH=$PATH:/usr/local/sbin:/usr/sbin:/sbin
opt="--no-defaults --pid-file=pid --socket=sock --skip-networking --datadir=`pwd`/db"
mysql_install_db $opt
mysqld $opt
