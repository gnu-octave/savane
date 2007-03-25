#!/usr/bin/perl
# Records your session and generates a WWW::Mechanize script
# http://www.perl.com/pub/a/2004/06/04/recorder.html

use HTTP::Proxy;
use HTTP::Recorder;

my $proxy = HTTP::Proxy->new(port => 8080);

# create a new HTTP::Recorder object
my $agent = new HTTP::Recorder;

# set the log file (optional)
$agent->file("/tmp/myfile");

# set HTTP::Recorder as the agent for the proxy
$proxy->agent( $agent );

# start the proxy
$proxy->start();

1;
