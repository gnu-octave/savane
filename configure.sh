#!/bin/bash -x
# Recommended configuration
if [ ! -e `dirname $0`/configure ]; then
    (cd `dirname $0` && sh bootstrap)
fi
bash `dirname $0`/configure --sysconfdir=/etc
