#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
# Copyright 2006 (c) Mathieu Roy <yeupou--gnu.org>
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
# $Id: perl-template.pl,v 1.4 2005/04/23 09:52:15 moa Exp $

use strict;
require Exporter;

# Exports
our @ISA = qw(Exporter);
our @EXPORT = qw(CheckSession );
our $version = 1;


# Check if a session is valid
# arg0: hash
# arg1: user id
sub CheckSession {
    return GetDBSettings("session", "session_hash='".$_[0]."' AND user_id='".$_[1]."'");
}
