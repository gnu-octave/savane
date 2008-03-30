#!/usr/bin/perl
# <one line to give a brief idea of what this does.>
# 
# Copyright 2004-2005 (c) Mathieu Roy <yeupou--gnu.org>
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


##
## Desc: any subs related to mail sending.
## This module is not loaded by Savannah.pm
##

use strict "vars";
require Mail::Send;
require Exporter;

# Exports
our @ISA = qw(Exporter);
our @EXPORT = qw(MailSend );
our $version = 1;

# Fixme: for now, there's only very limited testing here, it should in the
# end looks like sendmail.php

# from, to, subject, message
# Note: don't forget to escape @, otherwise address will be treated as 
# list.
# arg0: from
# arg1: to
# arg2: subject
# arg3: message
# arg4: Cc
# arg5: list of headers (can override previously set ones)
sub MailSend {
    my $msg = new Mail::Send;
        
    $msg->to($_[1]);
    $msg->subject($_[2]);
    if ($_[0] ne "") {
	$msg->add("From", $_[0]);	
    } else {
	$msg->add("From", GetConf("sys_mail_replyto"));
    }
	
    $msg->add("Cc", $_[4]) if $_[4];
    $msg->add("X-Savane-Server", GetConf("sys_default_domain"));
    $msg->add("User-Agent", "Savane::Mail.pm");

    
    if (@_[5]) {
	for (@_[5]) {
	    my ($header, $value) = split(": ", $_);

	    # Override previously set headers (apart the ones that identifies
	    # the sender (like X-Savane-Server)
	    $msg->delete($header) if
		$header eq "Cc" or
		$header eq "From" or
		$header eq "Subject" or
		$header eq "To";

	    # FIXME: unable to force the msg-id
	    $msg->add($header, $value);
	}
    }

    my $fh = $msg->open;
    print $fh $_[3]."\n\n_______________________________________________
  Message sent via/by ".GetConf("sys_name")."
  http://".GetConf("sys_default_domain").GetConf("sys_url_topdir")."\n";
    $fh->close;
}

return 1;
## EOF
