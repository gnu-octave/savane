#!/usr/bin/perl
# This file is part of the Savane project
# <http://gna.org/projects/savane/>
#
# $Id$
#
#  Copyright 2002-2006 (c) Mathieu Roy <yeupou--gnu.org>
#                          Sylvain Beucler <beuc--beuc.net>
#
# The Savane project is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# The Savane project is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with the Savane project; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

##
## This script permit to create a configuration file. As a
## correct configuration is required in other to runs Savannah,
## this file is designed to be working completely alone.
##

use Getopt::Long;
use Term::ANSIColor qw(:constants);
use Text::Wrap qw(&wrap $columns);
use Term::ReadKey(GetTerminalSize);
use POSIX qw(strftime);
use File::Basename;
use File::Copy;
use File::Find::Rule;

my $getopt;
my $debug;
my $help;
my $confdir = "/etc/savane";
my $update;
my $recreate;
my $question_count = 0;
my $handle = STDOUT;
my $non_interactive;
my $conffile;
my $tries_limit = 10;
my ($columns) = GetTerminalSize();
my $compat_copy = 0;

my $mode = "0750"; # 0750/-rwxr-x---
my $http_user;
$http_user = "www-data" if getgrnam("www-data"); # Debian
$http_user = "apache" if getgrnam("apache");   # RedHat

my ($www_topdir, $url_topdir, $incdir);

# get options
eval {
    $getopt = GetOptions("conffile=s" => \$conffile,
			 "confdir=s" => \$confdir,
			 "http-user=s" => \$http_user,
			 "non-interactive" => \$non_interactive,
			 "update" => \$update,
			 "debug" => \$debug,
			 "recreate" => \$recreate,
			 "help" => \$help,
			 "default-domain=s" => \$default_domain,
			 "shell=s" => \$shell,
			 "www-topdir=s" => \$www_topdir,
			 "url-topdir=s" => \$url_topdir,
			 "incdir=s" => \$incdir,
			 "dbhost=s" => \$dbhost,
			 "dbname=s" => \$dbname,
			 "dbuser=s" => \$dbuser,
			 "dbpasswd=s" => \$dbpasswd);
};

if($help || !$getopt) {
    print STDERR <<EOF;
Usage: $0 [OPTIONS]

Generate or update a configuration file for your local
Savane installation. This file is called savane.conf.pl and
is usually located in /etc/savane

  General:
      --confdir=<path>       Path of your conf as /etc/savane
      --http-user=<user>     User of the webserver who should be owner
	                     of the configuration file.
			     (www-data is usual with Debian+Apache)
			     (apache is usual with RedHat+Apache)
			     Avoid to point to symlinks using this option
      --recreate             (re)Create the configuration file
      --non-interactive      Run in non-interactive mode
                             (apply only with --recreate)
      --update               Update PHP conf copy by reading savane.conf.pl

      --help                 Show this help and exit
      --debug                Print to STDOUT the savane.conf.pl that
                             would be created (debug option)

  Settings Modification:
      --default-domain=<dom> Default hostname
      --www-topdir=<dir>     Top directory of the PHP frontend
      --url-topdir=<dir>     Suffix appended to the default domain
      --incdir=<dir>         Local dir of the site-specific content
      --dbhost=<dom>         Database server hostname
      --dbname=<name>        Database name
      --dbuser=<user>        Database server user
      --dbpasswd=<passwd>    Database server password
      --shell=<bin>          Shell provided to users
                             (like /usr/bin/sv_membersh)

Author: yeupou\@gnu.org
EOF
exit(1);
}


# This must be set after confdir is set
$confdir = dirname($conffile)
    if $conffile;
$conffile = $confdir."/savane.conf.pl";
my $conffile_phpcopy = $confdir."/.savane.conf.php";


# Some settings the user cannot override by command line but that should 
# be set by default
$sys_default_domain = `hostname -f`; chomp($sys_default_domain);
$sys_dbhost = "localhost";
$sys_dbname = "savane";
$sys_url_topdir = "/";
$sys_unix_group_name = "siteadmin";
$sys_themedefault = "Emeraud";
$sys_mail_domain = $sys_default_domain;
$sys_mail_admin = "root";
$sys_replyto = "NO-REPLY.INVALID-ADDRESS";
$sys_cron_cleaner = "yes";
$sys_cron_reminder = "yes";

# Backward compat: if /etc/savannah/savannah.conf.pl or $confdir
# exists and the required
# conffile does not, we copy /etc/savannah/savannah.conf.pl just in case
for (($confdir, "/etc/savannah")) {
    if (-e "$_/savannah.conf.pl" and ! -e $conffile) {
	print ON_RED, WHITE, BOLD,Wrap("\n $conffile was missing but $_/savannah.conf.pl exists"), RESET, Wrap("\n\nAs $_/savannah.conf.pl was the old location of conffile, now renamed savane.conf.pl, we will copy its content and it will be used to regenerate the new conffile.\n\nType ", BOLD, "CTRL-C", RESET " to interrupt this process now or ", BOLD "Enter", RESET " to proceed")."\n\n";
	<STDIN>;
	`mkdir -p $confdir` unless -e $confdir; 
	copy("$_/savannah.conf.pl", $conffile);
	`chmod $mode $conffile`;
	$recreate = 1;
	$compat_copy = $_;
    }
}

# If conffile already exists, we can use it
# Otherwise, we need to create conffile from scratch.
# In fact, we will simply execute the conffile picked.
if (-e "$conffile") {
    # Make sure it is in the appropriate mode to be ran
    `chmod $mode $conffile`;

    # Actually try to run it - go in recreate mode if unable to read it
    do "$conffile" 
	or $failure = 1;

    if ($failure) {
	$recreate = 1;
	print ON_RED, WHITE, BOLD,"\n Unable to run $conffile", RESET, Wrap("\n\nIt is most probably, it's a privilege issue, or a perl typo inside it.\nThe current $conffile will be ignored, and saved as $conffile.bak.\n\nType ", BOLD, "CTRL-C", RESET " to interrupt this process now or ", BOLD "Enter", RESET " to proceed to a new $conffile creation.")."\n\n";
	# Wait for the user to type enter before proceeding to questions
	<STDIN>;
    }

    print "$conffile exists\n"
	if $debug;
} else {
    $recreate = 1;
    print "no $conffile - create it\n"
	if $debug;
}



# Some settings the user may want to override by command line
$sys_default_domain = $default_domain if $default_domain;
$sys_https_host = $https_host if $https_host;
$sys_https_port = $https_port if $https_port;

$sys_www_topdir= $www_topdir if $www_topdir;
$sys_url_topdir= $url_topdir if $url_topdir;
$sys_incdir= $incdir if $incdir;
$sys_shell= $shell if $shell;

$sys_dbhost= $dbhost if $dbhost;
$sys_dbname= $dbname if $dbname;
$sys_dbuser= $dbuser if $dbuser;
$sys_dbpasswd= $dbpasswd if $dbpasswd;

# A function cleanly wrap paragraphs
sub Wrap {
    return wrap("", "", @_);
}

# A function to add easily new questions
sub AskSetting {
    
    my $item = $_[0];
    my $item_name = $_[1];
    my $item_help = $_[2];
    my $item_example = $_[3];
    if ($_[4]) {
	$item_previous_answer = $_[4];
    } else {
	$item_previous_answer = "OUT";
    }   
  
    my $required_file_pattern = $_[5];
    my $file_to_check = $required_file_pattern;
    $file_to_check =~ s/\#ITSELF\#/$item_previous_answer/g;

    my $is_mandatory = $_[6];
    $is_mandatory = ($is_mandatory + $_[7]) if $_[7];
    $_[7]++ if $_[7];

    $item_quotes = "\"";

    # Increment question count unless if we are still reprinting the previous
    # unanswer mandatory question
    # (FIXME: this does not handle the file check problem
    $question_count = $question_count+1
	unless $is_mandatory > 1;


    # Get user input if in interactive mode
    if (!$non_interactive) {
	
	# Exit if the mandatory question was not answer after askin 10 times
	die WHITE, ON_RED, "\n Sorry, but I cannot create the conffile if you refuse to answer. Goodbye.", RESET, "\n"
	    if $is_mandatory > ($tries_limit-1);

	# Separator, warning if the setting is mandatory
	print WHITE, ON_BLUE, "\n $question_count. Optional", RESET, "\n"
	    unless $is_mandatory;
	print WHITE, ON_RED, "\n $question_count. Mandatory", RESET, "\n"
	    if $is_mandatory eq 1;
	print WHITE, ON_RED, "\n $question_count. This is really a ", BOLD, "Mandatory setting!", RESET, "\n"
	    if $is_mandatory > 1;
	print WHITE, ON_RED, "\n No kidding, you will not get away with it, ", BOLD, "you must answer this question.\n", RESET, WHITE, ON_RED, " You have ".($tries_limit-$is_mandatory)." tries left.", RESET, "\n"
	    if $is_mandatory > 3;

	# Warning if a file was required, if there is a previous answer
	# pointing to a non-existant file
	print WHITE, ON_RED, "\n $file_to_check does not exists! Your answer is not valid.", RESET, "\n"
	    if $file_to_check && ! -e $file_to_check;

	# Setting name and description
	print "\n", CYAN, BOLD "$item_name:", RESET "\n".Wrap("$item_help")."\n\n\tExample:", YELLOW "$item_example", RESET "\n\n";

	# Previous answer
	print "[$item_previous_answer]: ";

	# Obtain the value
	chomp($$item = <STDIN>);

	# Take the previous value if unset
	$$item = $item_previous_answer
	    if ($$item eq "" and $item_previous_answer ne "OUT");

	print "\n\n";

	$file_to_check = $required_file_pattern;
	$file_to_check =~ s/\#ITSELF\#/$$item/g;
	AskSetting(@_)
	    if $file_to_check && ! -e $file_to_check;

	# Reask the same question if unset still unset even after
	# taking previous value and mandatory
	AskSetting(@_, $is_mandatory)
	    if ($is_mandatory and $$item eq "" or $$item eq "OUT");

	# Make sure extra AskSettings calls for forgotten mandatory settings
	# will end here
	return
	    if $_[7] or ($file_to_check && ! -e $file_to_check);

	
    } else {
	$$item = $item_previous_answer;
    }

    # Now, we set $$item to how we want to show it in the configuration
    # file.
    
    SetSetting($item);
}

my %forlater;
sub SetSetting {
    my $item = $_[0];
    
    $item_quotes = "\"";

    # Escape quotes
    $$item =~ s/$item_quotes/\\$item_quotes/g;

    # Save for later
    $forlater{$item} = $$item;
    
    if ($$item eq "" || $$item eq "OUT") {
	# Put in comment
	$$item = "# our \$$item=$item_quotes$item_quotes\;";
    } else {
	# Add the entry
	$$item = "our \$$item=$item_quotes$$item$item_quotes\;";
    }
}

############################################### QUESTIONS #####

if ($recreate) {

    unless ($debug) {
	# We create the conf directory if missing
	`mkdir -p $confdir` if ! -e $confdir;

        # We make a backup of the configuration file
	rename("$conffile", "$conffile.bak") && `chmod 0600 $conffile.bak` if -e "$conffile";

        # We check whether we can write in the configuration file
	die "Cannot writing in $confdir" unless -w $confdir;
    }

    print WHITE, ON_RED, "\n\n\t---------- $conffile (re)creation ---------\n", RESET, "\n\n";
    print Wrap("If you want a value to be commented out, type ",BOLD "OUT",RESET),"\n\n";
    print Wrap("If you only type enter, the content inside ",BOLD "[ ]",RESET "will be taken into account")."\n\n";
    print Wrap("If you do not understand what a question is about, it is probably not vital to you and you can comment it out by typing ",BOLD "OUT",RESET)."\n\n";
    print Wrap("If you want to use unusual characters like ",BOLD "\@",RESET ", escape them with a ", BOLD "\\",RESET"in front of them")."\n\n";
    print Wrap("Understood? Now, type ", BOLD "Enter", RESET "to proceed")."\n\n";

    # Wait for the user to type enter before proceeding to questions
    <STDIN>;

    # ask the user to set the correct values.

    ## SERVER(S) ##

      AskSetting("sys_default_domain", "Default hostname", "It must be the naked form of the domain", "savannah.gnu.org", $sys_default_domain, 0, 1);

      AskSetting("sys_https_host", "HTTPS hostname", "It must be the naked form of the domain. If you do not have https server, comment out", "\$sys_default_domain, savannah.gnu.org", $sys_https_host);
      AskSetting("sys_brother_domain", "Brother hostname", "You can run Savane with two different domain names. You will be able to write a different configuration for each one. The two brother/companion sites will share the same database. If you do not have brother/companion site, comment out. If you do not understand what is it about, you probably do not need that feature, comment out", "savannah.nongnu.org", $sys_brother_domain);


      AskSetting("sys_dbhost", "SQL database hostname", "Name of the server running the MySQL database", "localhost", $sys_dbhost, 0, 1);
      AskSetting("sys_dbname", "SQL database name", "Name of the database", "savane", $sys_dbname, 0, 1);
      AskSetting("sys_dbuser", "SQL database user", "Name of the MySQL user to be that can access and write to the database", "mysqluser, root", $sys_dbuser, 0, 1);
      AskSetting("sys_dbpasswd", "SQL database password", "Password associated to the MySQL user account that can access and write to the database", "mysqlpasswd", $sys_dbpasswd);
      AskSetting("sys_dbparams", "Additional database settings", "Extra parameters for MySQL: param=value pairs separated by colons", "mysql_socket=/non-standard-path-to/mysqld.sock:otherparam=value", $sys_dbparams);


    ## INSTALLATION PATHS ##

    # FIXME: Should check if the directories provided are ok
      AskSetting("sys_www_topdir", "(absolute) Path to the PHP frontend top directory", "In the source package, it is the directory frontend/php. IT MUST BE AN ABSOLUTE PATH NAME", "/usr/src/savane/frontend/php", $sys_www_topdir, "#ITSELF#/index.php", 1);
      AskSetting("sys_url_topdir", "Default web directory", "Suffix appended to the default domain", "/", $sys_url_topdir, 0, 1);
      AskSetting("sys_incdir", "Site-specific content directory", "In the source package, it is the directory etc/site-specific-content. This directory contains files you can modify to customize pages of your Savane installation", "/etc/savane/savane-content, /usr/src/savane/etc/site-specific-content", $sys_incdir, "#ITSELF#/homepage.txt", 1);

    ## GUI ##

      AskSetting("sys_name", "Platform name", "Name shown on public pages for the whole service", "New Savane Installation", $sys_name, 0, 1);
      AskSetting("sys_unix_group_name", "Server administration project unix name", "Unix group name of the meta-project used for administration. Take care to avoid conflicts with group name existing on your system, take care to select a valid unix group name: no checks will be done for this project unix group name", "siteadmin", $sys_unix_group_name, 0, 1);

      AskSetting("sys_default_locale", "Default locale", "You can select a valid locale name to be taken as default", "fr_FR", $sys_default_locale);
      AskSetting("sys_datefmt", "Date format", "Date formatting. Savane normally does a pretty good job at providing a correctly localized date. Unless you really know what you are doing, leave this commented out.", "%Y-%m-%d %H:%M", $sys_datefmt);

    # Get a list of themes. Normally, we should be able to expect to find
    # thmes in sys_www_topdir, as this directory was previously verified
    my @themes = File::Find::Rule->file()
	->name("*.css.in")
	->maxdepth(1) 
	->in(($forlater{'sys_www_topdir'}."/css"));
    my $themes;
    for (sort(@themes)) {
	$_ = basename($_, ".css.in");
	s/\s//g;

	# Ignore site specific themes
	next if $_ eq "base";
	next if $_ eq "printer";
	next if $_ eq "Gna";
	next if $_ eq "CERN";
	next if $_ eq "Savannah";
	next if $_ eq "Savanedu";
	next if $_ eq "UGent";
	$themes .= "$_ ; ";
    }
      AskSetting("sys_themedefault", "Default theme", "You can pick a default theme among Savane themes: ".$themes, "Emeraud", $sys_themedefault, $forlater{'sys_www_topdir'}."/css/#ITSELF#.css.in", 1);
      AskSetting("sys_logo_name", "Logo name", "The engine will search for a file like savane/frontend/php/images/\$theme.theme/\$sys_logo_name. If you do not want any logo, comment out", "floating.png", $sys_logo_name);


    ## MAIL, MAILING-LIST ##

      AskSetting("sys_mail_domain", "Mail domain", "--", "gnu.org, \$sys_default_domain", $sys_mail_domain, 0, 1);
      AskSetting("sys_mail_admin", "Admin mail address", "The mail domain we'll be added to this username", "root, help", $sys_mail_admin, 0, 1);
      AskSetting("sys_mail_replyto", "No reply address used in the trackers", "Trackers send mail notifications to users. Here you define the email address that will be used in the sender field", "NO-REPLY.INVALID-ADDRESS", $sys_replyto, 0, 1);
      AskSetting("sys_mail_list", "[BACKEND SPECIFIC] List of emails", "If you do not want such file to be updated by the backend, comment out", "/etc/email-addresses", $sys_mail_list);
      AskSetting("sys_mail_aliases", "[BACKEND SPECIFIC] List of emails aliases", "If you do not want such file to be updated by the backend, comment out", "/etc/aliases", $sys_mail_aliases);


    ## USERS ACCOUNTS ##

      AskSetting("sys_use_pamauth", "PAM support", "AFS, Kerberos (...) authentication can be made via PAM. If you need users to log in the web interface using PAM, this is what you want. If you do not know what it is about, comment out.", "no", $sys_use_pamauth);

    # Too rarely used to deserve to be prompted for each time 
    #AskSetting("sys_use_krb5", "Kerberos 5", "If you do not know what it is about, you surely don't have to deal with a kerberos server, say no here.", "no", $sys_use_krb5);

      AskSetting("sys_homedir", "[BACKEND SPECIFIC] User home directory", "Usually /home. You can uncomment if you do not plan to provide accounts", "/home", $sys_homedir);
      AskSetting("sys_homedir_subdirs", "[BACKEND SPECIFIC] User home directory subdirs", "Users home is by default /home/user. If you set this to 1, you'll get /home/u/user, and if you set it to 2, you'll get /home/u/us/user. It may be very convenient if you have plenty of users.", "2", $sys_homedir_subdirs);
      AskSetting("sys_shell", "[BACKEND SPECIFIC] User default shell", "sv_membersh is a limited shell, choose /bin/bash if you want to provide full access to your users", "/usr/bin/sv_membersh, /usr/local/bin/sv_membersh", $sys_shell, "#ITSELF#");

      SetSetting("sys_userx_prefix", "[BACKEND SPECIFIC] Prefix for user* binaries", "If you do not want to use useradd/usermod/userdel that are in the usual PATH but specific ones, you can type here their prefix. Otherwise, comment out", "/usr/local/savane/bin", $sys_userx_prefix);

    ## CRONJOBS ##
      AskSetting("sys_cron_cleaner", "[BACKEND SPECIFIC] Cron job: database cleaning", "A special backend script will clean regularly the database. If you do not want that cleaning to be done, comment out. It is recommended to use it, even if your installation use no other backend tool", "yes", $sys_cron_cleaner);
      AskSetting("sys_cron_reminder", "[BACKEND SPECIFIC] Cron job: trackers reminder", "A special backend script will check regularly the database and send email to users in defined cases. An user can decide to receive regularly task assigned to him in a batch ; a project administrator can decide to make people that got item with high priority not closed receiving a batch. Also, when an item is supposed to start of to finish, a reminder should be sent to anybody supposed to get notification for the item", "yes", $sys_cron_reminder);

 
      AskSetting("sys_cron_users", "[BACKEND SPECIFIC] Cron job: related to users", "If you do not want your system to be synchronized with database automatically regarding to users infos (/home/$user, /etc/passwd)), comment out", "yes", $sys_cron_users);
      AskSetting("sys_cron_groups", "[BACKEND SPECIFIC] Cron job: related to groups/projects", "If you do not want your system to be synchronized with database automatically regarding to groups infos (/etc/group), comment out", "yes", $sys_cron_groups);

    # Too rarely used to deserve to be prompted for each time 
      SetSetting("sys_cron_viewcvs_forbidden", "[BACKEND SPECIFIC] Cron job: related to viewcvs ignore list", "If you do not want your system to be synchronized with database automatically regarding to viewcvs forbidden list, comment out", "yes", $sys_cron_viewcvs_forbidden);

      AskSetting("sys_cron_mail", "[BACKEND SPECIFIC] Cron job: related to mails", "If you do not want your system to be synchronized with database automatically regarding to mail infos (/etc/aliases...), comment out", "yes", $sys_cron_mail);
      AskSetting("sys_cron_mailman", "[BACKEND SPECIFIC] Cron job: related to mailman", "If you do not want your system to be synchronized with database automatically regarding to mailman list (it assume you have mailman installed on this system), comment out", "yes", $sys_cron_mailman);

    ## EXTRA FEATURES / OTHER ##
      AskSetting("sys_spamcheck_spamassassin", "SpamAssassin checks on posted content", "You can filter content posted on trackers through SpamAssassin. This assume that you have spamassassin daemon (spamd) running that spamc can connect to. If you set this setting to 1, only anonymous posts will be filtered. If you post it to 2, logged-in posts (except by projects members) will be filtered too. We recommend that by default you set it to 1. If you do not have SpamAssassin, comment out", "1 or anonymous, 2 or all", $sys_spamcheck_spamassassin);
      AskSetting("sys_spamcheck_spamassassin_options", "SpamAssassin daemon options", "If spamc need to connect to spamd that is not on localhost, to use a special port, use this setting to the correct options. See spamc manual for available options. If you do not need any specific options, comment out", "OUT", $sys_spamcheck_spamassassin_options);
    
      AskSetting("sys_upload_max", "File upload maximum", "On trackers, people can attach item. The default value is 512 kb. If you want to allow bigger files, you can change this. Note that if you change this setting, you must make sure it does not contradict PHP setup, most notably upload_max_filesize, and your MySQL daemon setup, most notably max_allowed_packet. In doubt, comment out", "512, 1024, 2048", $sys_upload_max);
    
	# Ignore by default questions related to
        # webalizer and mrtg integration. These are nowadays useless,
        # since apache is no longer supposed to be able to this kind of
        # data, for security reasons, on most installations.
    
       # Too rarely used to deserve to be prompted for each time 
       SetSetting("sys_viewcvs_conffile", "[BACKEND SPECIFIC] Viewcvs configuration file", "Path to the viewcvs conffile. If you do not use viewcvs or if you do not want the fordibben setting of this configuration file to be edit by Savane, comment out", "/etc/viewcvs/viewcvs.conf", $sys_viewcvs_conffile);
       SetSetting("sys_use_google", "Google search", "Add a search via google option to the search module. If you do not want this search facility, comment out", "", $sys_use_google);
       SetSetting("sys_localdoc_file", "Local Administration Documentation File", "Will make available the content of a specific file to site admins. Give the path to the file or comment out", "", $sys_localdoc_file);

    ## SPECIFIC and (usually) DEPRECATED


############################################### OUTPUT CREATION #####

    unless ($debug) {
	open(CONFFILE, "> $conffile") or die "Cannot open $conffile for writing";
	$handle = CONFFILE;
    }


    # print the conffile
    print $handle <<EOF;
#!/usr/bin/perl
#
# Savane Site Configuration:
#
# \$Id$
#
# This file has been generated by sv_update_conf
# If you modify it, run sv_update_conf --update
# to update the PHP version of this file.
# You can recreate this file using sv_update_conf --recreate
# with ease.
# Ex: sv_update_conf --confdir=$confdir --update
#     sv_update_conf --confdir=$confdir --recreate
#
# IMPORTANT SECURITY NOTE:
#   Configuration files should be rwx-only for root and apache's
#   user (usually "www-data" on Debian GNU/Linux, "apache" on RedHat).
#   So the owner should be apache's user.
#      - there is a mysql password
#      - these files are executed
#  While it offers the incredible advantage to avoid reinventing a boring
#  config parser and to permit hackers to create very efficient config
#  by using perl/php, if anybody can edit it, anybody can push savane to
#  execute malicious code when looking for it's configuration.
#  (But it's like a /etc/profile ... /etc is for admins).
#
# By default:
#      Access: (0750/-rwxr-x---)
#      Uid: (    0/root)
#      Gid: (   33/www-data)
#
# HOWTO MODIFY THIS FILE:
#   Some settings are required, and should be marked as such. Other can be
#   commented out, at your convenience (usually, if something is referring
#   to something else you have no clue about, let it commented out)

use strict;
use warnings;

    ## SERVER(S) ##

      # Default HTTP domain
      # It must be the naked form of the domain
      # Ex: "savannah.gnu.org"
        # REQUIRED
        $sys_default_domain

      # Default HTTPS domain
      # It must be the naked form of the domain
      # Ex: "savannah.gnu.org"
        # OPTIONAL (comment out if not appliable)
        $sys_https_host

      # Brother HTTP(s) domain:
      # You can run Savane with two different domain names. You'll be able
      # to write a different configuration for each one.
      # The two brother/companion sites will share the same database.
      # Here you can let your Savane installation aware of the existence
      # of a brother/companion site, so while people login, it will be allowed
      # to them to login on both site in one click.
      # If you do not have brother/companion site, comment out.
        # OPTIONAL (comment out if not appliable)
        $sys_brother_domain

      # SQL server:
      # Ex: "localhost", "savane", "mysqluser", "mysqlpasswd",
      #     "mysql_socket=/non-standard-path-to/mysqld.sock"
        # REQUIRED
        $sys_dbhost
        # REQUIRED
        $sys_dbname
        # REQUIRED
        $sys_dbuser
        # REQUIRED
        $sys_dbpasswd
        # OPTIONAL (comment out if not appliable)
        $sys_dbparams


    ## INSTALLATION PATHS ##

      # Local dir of the installation of the PHP frontend
      # IT MUST BE AN ABSOLUTE PATH NAME
      # Ex: "/usr/src/savane/frontend/php"
        # REQUIRED
        $sys_www_topdir

      # Default web directory - suffix appended to the default domain
      # Ex: "/", "/savane"
        # REQUIRED
        $sys_url_topdir

      # Local dir of the site-specific content
      # Ex: "/etc/savane/savane-content"
        # REQUIRED
        $sys_incdir


    ## GUI ##

      # Platform name, server administration project:
      # Name of the platform running and unix_group_name of the project
      # dedicated of the server administration
      # Ex: "New Savane Installation", "siteadmin",
        # REQUIRED
        $sys_name
        # REQUIRED
        $sys_unix_group_name

      # Default locale and date formatting.
      # Ex: "fr_FR", "%Y-%m-%d %H:%M"
        # OPTIONAL (comment out if not appliable)
        $sys_default_locale
      # Savane normally does a pretty good job at providing a correctly 
      # localized date. This option allows you to force a specific date
      # format.  Unless you really know what you are doing, leave this
      # commented out
        # OPTIONAL (comment out if not appliable)
        $sys_datefmt

      # Frontend look:
      # Default theme, logo filename
      # The engine will search for a file like
      #    frontend/php/images/\$theme.theme/$sys_logo_name
      # Ex: "emeraud", "floating.png"
      #
      # Please, do not use "Gna, Savannah, CERN or Ugent" as default theme,
      # they were designed for specific sites and are part of these sites
      # color policy.
        # REQUIRED
        $sys_themedefault
        # OPTIONAL (comment out if not appliable)
        $sys_logo_name

    ## MAIL, MAILING-LIST ##

      # Mail domain, admin mail address, default replyto when
      # no reply is possible, email addresses list, email aliases
      # Ex: "gnu.org", "help", "NO-REPLY.INVALID-ADDRESS",
      # "/etc/email-addresses," "/etc/aliases"
        # REQUIRED
        $sys_mail_domain
        # REQUIRED
        $sys_mail_admin
        # REQUIRED
        $sys_mail_replyto
        # OPTIONAL (comment out if not appliable)
        $sys_mail_list
        # OPTIONAL (comment out if not appliable)
        $sys_mail_aliases


    ## USERS ACCOUNTS ##

      # Provide web interface login through PAM
      # (it can be AFS, Kerberos...)
      # Ex: "yes", "no"
        # OPTIONAL (comment out if not appliable)
        $sys_use_pamauth

      # Kerberos:
      # If you do not know what it is about, you surely don't have to
      # deal with a kerberos server, say no here.
      # IMPORTANT: this part will be removed, replaced by PAM
      # Ex: "no"
        # OPTIONAL (comment out if not appliable)
        $sys_use_krb5

      # [BACKEND ONLY]
      # user* binaries path
      # If you do not want to use useradd/usermod/userdel that are in the
      # usual PATH but specific ones, you can type here their prefix.
      # Otherwise, comment out.
      # If you do not know that this setting is about, comment out.
        # OPTIONAL (comment out if not appliable)
        $sys_userx_prefix

      # [BACKEND ONLY]
      # User home directory
      # You can safely uncomment all these settings if you do not use
      # the backend.
      # Ex: "/home"
        # OPTIONAL (comment out if not appliable)
        $sys_homedir

      # [BACKEND ONLY]
      # User home directory subdirs
      # Ex: "2" means users will have $sys_homedir/u/us/user as home.
        # OPTIONAL (comment out if not appliable)
        $sys_homedir_subdirs

      # [BACKEND ONLY]
      # User default shell.
      # Ex: "/usr/local/bin/sv_membersh" or "/bin/bash"
        # OPTIONAL (comment out if not appliable)
        $sys_shell


    ## CRONJOBS ##

      # [BACKEND ONLY]
      # Cron jobs
      # If you do not want system to be synchronized with database
      # automatically
      # on the following topics, by the backend, comment out.
        # OPTIONAL but highly recommended, almost required.
        $sys_cron_cleaner
        # OPTIONAL but recommended
        $sys_cron_reminder
        # OPTIONAL but recommended
        $sys_cron_mail
        # OPTIONAL (comment out if not appliable)
        $sys_cron_users
        # OPTIONAL (comment out if not appliable)
        $sys_cron_groups
        # OPTIONAL (comment out if not appliable)
        $sys_cron_mailman
        # OPTIONAL (comment out if not appliable)
        #our \$sys_cron_viewcvs_forbidden=\"$sys_cron_viewcvs_forbidden\"\;


    ## EXTRA FEATURES / OTHER ##

      # Spam checks with SpamAssassin
      # This assume that you have spamassassin daemon (spamd) running 
      # that spamc can connect to.
      # If you set sys_spamcheck_spamassassin to "1" or "anonymous",
      # all posted content by anonymous will be filtered.
      # If you set sys_spamcheck_spamassassin to "2" or "all", all posted 
      # content by anonymous and logged-in users (except projects members) 
      # will be filtered.
      # We recommend by default to set it to "1"
	# OPTIONAL (comment out if not appliable)
	$sys_spamcheck_spamassassin
	# OPTIONAL (comment out if not appliable)
	$sys_spamcheck_spamassassin_options
	

      # File upload maximum
      # On trackers, people can attach item. The default value is 512kB.
      # If it is too much or not enough for your installation, you can change
      # this. Note that if you change this setting, you must make sure
      # it does not contradict PHP setup, most notably upload_max_filesize
      # and your MySQL daemon setup, most notably max_allowed_packet.
	# OPTIONAL (comment out if not appliable)
	$sys_upload_max

      # [BACKEND ONLY]
      # viewcvs ignore list
      # If you do not use viewcvs, if you do not want the backend to
      # edit that list comment out
        # OPTIONAL (comment out if not appliable)
        $sys_viewcvs_conffile

      # search via google added to the search module
        # OPTIONAL (comment out if not appliable)
        $sys_use_google

      # local admin doc: path of this path will be made available to site
      # admins
        # OPTIONAL (comment out if not appliable)
        $sys_localdoc_file

    ## DEVELOPMENT ##

      # Developers of Savane should probably set this setting on their
      # test machine
      #our \$sys_debug_on=\"1\"\;


    ## WORKAROUNDS ##

      # DEPRECATED:
      # settings from the original code that does not seems usable nor
      # usefull. They are needed in order for the PHP frontend to run for now.
      #our \$sys_urlroot=\"\$sys_www_topdir\"\;
      our \$sys_replyto=\"\$sys_mail_replyto\"\;
      our \$sys_admin_list=\"\$sys_mail_admin\"\;
      our \$sys_lists_domain=\"\$sys_default_domain\"\;
      our \$sys_email_adress=\"\$sys_mail_admin\\\@\$sys_mail_domain\"\;
      our \$sys_email_address=\"\$sys_email_adress\"\;

# END
EOF

    unless ($debug) {

	# close the conffile, set it properly
	close(CONFFILE);
	`chmod $mode $conffile`;
	
	print WHITE, ON_RED, "\n\n\t---------- $conffile (re)created ---------\n", RESET, "\n\n";
	
    }
}


# now update/create the local/inc.pl file
# we ask user to use --update but for now
# update is done in any cases

open(CONFFILEPL, "< $conffile") or die "Cannot open $conffile for reading";
open(CONFFILEPHP, "> $conffile_phpcopy") or die "Cannot open $conffile_phpcopy for writing";

print CONFFILEPHP "<?php\n";
print CONFFILEPHP "// THIS FILE WAS GENERATED (", strftime "%c)\n", localtime;
print CONFFILEPHP "// DO NOT MODIFY THIS FILE
// Modify only $conffile and run sv_update_conf
// Ex: sv_update_conf --confdir=$confdir --update
//
// IMPORTANT SECURITY NOTE.
// Configuration files should be rwx-only for root and apache's
// user (usually \"www-data\" with Debian GNU/Linux).
// So the owner should be apache's user.
//      - there is a mysql password
//      - these files are executed
// While it offers the incredible advantage to avoid reinventing a boring
// config parser and to permit hackers to create very efficient config
// by using perl/php, if anybody can edit it, anybody can push Savane to
// execute malicious code when looking for it's configuration.
// (But it's like a /etc/profile ... /etc is for admins).
//
// By default:
//     Access: (0750/-rwxr-x---)
//     Uid: (    0/root)
//     Gid: (   33/www-data)
";

while (<CONFFILEPL>) {
    s/our\ //;
    s/use\ .*$//;
    s/\\\@/\@/;
    next if /^#/;
    next if /^\s*(#|$)/;
    print CONFFILEPHP "$_";
}

print CONFFILEPHP "// NOTHING SHOULD REMAIN BEYOND THE PHP CLOSING TAG\n";
print CONFFILEPHP "?>";

close(CONFFILEPHP);
close(CONFFILEPL);
    
system("chmod", $mode, $conffile_phpcopy);
system("chown", "root:$http_user", $conffile_phpcopy, $conffile) if $http_user;

print "\nYou asked this script to run in non-interactive mode. You should \nprobably edit the file $conffile now\n" if $non_interactive;
    

if ($compat_copy) {
    # ask to remove outdated content
    print WHITE, ON_RED, BOLD Wrap("\nYou should delete outdated content of /etc/savannah if existing"), RESET, "\n\n";
    # by brute force, remove old conffile in the same directory as the new one
    system("rm", "-f", "$confdir/savannah.conf.pl") if "$confdir/savannah.conf.pl";
    system("rm", "-f", "$confdir/savannah.conf.php") if "$confdir/savannah.conf.php";
}

# EOF
