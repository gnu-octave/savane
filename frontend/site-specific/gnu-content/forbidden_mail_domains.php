<?php
# Copyright (C) 2005 Sylvain Beucler
# Copyright (C) 2017 Ineiev <ineiev@gnu.org>
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
#
#    You can fed $forbid_mail_domains_regexp by a perl regexp
#    with theme domain names you want to forbid on your system.
#
#    This forbid_mail_domains_regexp site specific variable be useful if you
#    do not want to allow registration of users accounts on the basis of
#    a given domain.
#
#    For instance, you may not want to allow people to create account
#    with your Savane installation domain, because it would allow people
#    to endlessly create account and mail aliases.
#    Or you may want to allow only people having an address @yourcompany
#    to create account.
#
#    As it is regexp for the full address, you can basically block from
#    here any address your want.

# The perl regexp:
#    The two slashes (/ /) are mandatory, see the preg_match manual.

# $GLOBALS['forbid_mail_domains_regexp'] = "/^(.*\@invalid\.dom)$/";
?>
