# Autoconf configuration
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

# Based on gettext-0.16.1/gettext-tools/examples/hello-php
# (public domain)

AC_INIT([GNU Savannah],
        [3.1-cleanup1],
	[savannah-hackers-public@gnu.org], [],
	[https://savannah.gnu.org/projects/administration])

# We expect to use Bash for our build scripts - it's widespread
# enough, not need to write portable shell in this case, I'd say.
if test -z "$BASH"; then
  AC_MSG_ERROR([*** Essential program Bash not found])
fi
SHELL=$BASH

AC_CONFIG_AUX_DIR(autotools)
# For future version of aclocal:
AC_CONFIG_MACRO_DIR(autotools/m4)
AM_INIT_AUTOMAKE([1.11.1])

dnl The installed program must know where to find its message catalogs.
dnl Unfortunately, prefix gets only finally determined at the end of configure.
dnl i.e. otherwise you'll get @localedir@ => ${datarootdir}/locale
dnl (litterally)
if test "X$prefix" = "XNONE"; then
  final_prefix="$ac_default_prefix"
else
  final_prefix="$prefix"
fi
save_prefix="$prefix"
prefix="$final_prefix"
eval "datarootdir=\"${datarootdir}\""
eval "localedir=\"${datadir}/locale\""
prefix="$save_prefix"
dnl AC_SUBST(localedir)

dnl Support for the po directory.
AM_PO_SUBDIRS


AC_PATH_PROG(PERL, perl)
if test -z "$PERL"; then
  AC_MSG_ERROR([perl not found])
fi

AC_PATH_PROG(SV_DB_CMD, mysql)
if test -z "$SV_DB_CMD"; then
  AC_MSG_ERROR([mysql client not found])
fi

AC_ARG_WITH([db-name], AS_HELP_STRING([--with-db-name],
  [name of the database (default: 'savane')]),
  SV_DB_NAME="$withval",
  SV_DB_NAME="savane")
AC_ARG_WITH([db-options], AS_HELP_STRING([--with-db-options],
  [parameters to give MySQL when initializing the database (default: empty)]),
  SV_DB_OPTIONS="$withval")
# Add these vars in the Makefile's:
AC_SUBST(SV_DB_NAME)
AC_SUBST(SV_DB_OPTIONS)

# Apache
# Perl: TODO, see how to autoconfiscate this proPERLy
echo -n "checking for webserver user... "
if test -e  "/etc/debian_version"; then 
    # Debian-based
    GUESS_WWW_SERVER_USER="www-data"
    GUESS_PERL_INC="/usr/lib/perl5"
elif test -e "/etc/redhat-release"; then 
    # Fedora- or RedHat-based
    GUESS_WWW_SERVER_USER="apache"
    GUESS_PERL_INC="/usr/lib/perl5/site_perl"
elif test -e "/etc/gentoo-release"; then
    # Gentoo-based
    GUESS_WWW_SERVER_USER="apache"
elif test -e "/etc/slackware-version"; then
    # Slackware-based"
    GUESS_WWW_SERVER_USER="nobody"
fi
echo $GUESS_WWW_SERVER_USER
# TODO: --with-php-uid=... (AC_ARG_ENABLE)
# TODO: ps aux | grep -E 'http|apache' | parse_uid_field...
# TODO: else { echo "Couldn't guess Apache user"; exit 1}  


SV_CONF=$sysconfdir/savane
AC_SUBST(SV_CONF)


AC_CONFIG_FILES([Makefile])
AC_CONFIG_FILES([ \
autotools/m4/Makefile \
backend/Makefile \
backend/system-specific/Makefile \
backend/system-specific/darwin/Makefile \
etc/Makefile \
frontend/Makefile \
frontend/php/images/Makefile \
lib/Makefile])
AC_CONFIG_FILES([po/Makefile], [AM_POSTPROCESS_PO_MAKEFILE])

AC_CONFIG_FILES([frontend/php/include/ac_config.php])
AC_CONFIG_FILES([lib/Savane/Version.pm])

dnl associate the setting of the execute bit with the individual scripts
dnl [idea from cvs-1.1.13/contrib]
dnl AC_CONFIG_FILES(backend/accounts/sv_groups, [chmod +x backend/accounts/sv_groups])
AC_CONFIG_FILES(backend/accounts/sv_groups, [chmod +x backend/accounts/sv_groups])
AC_CONFIG_FILES(backend/accounts/sv_users, [chmod +x backend/accounts/sv_users])
AC_CONFIG_FILES(backend/accounts/sv_membersh, [chmod +x backend/accounts/sv_membersh])
AC_CONFIG_FILES(backend/export/sv_export, [chmod +x backend/export/sv_export])
AC_CONFIG_FILES(backend/export/sv_export_cleaner, [chmod +x backend/export/sv_export_cleaner])
AC_CONFIG_FILES(backend/mail/sv_mailman, [chmod +x backend/mail/sv_mailman])
AC_CONFIG_FILES(backend/mail/sv_aliases, [chmod +x backend/mail/sv_aliases])
AC_CONFIG_FILES(backend/mail/sv_mailman_and_mailarchivedotcom,
	[chmod +x backend/mail/sv_mailman_and_mailarchivedotcom])
AC_CONFIG_FILES(backend/extra/subversion/sv_extra_svn_postcommit_brigde,
	[chmod +x backend/extra/subversion/sv_extra_svn_postcommit_brigde])
AC_CONFIG_FILES(backend/extra/subversion/sv_extra_daily_svn_tarball,
	[chmod +x backend/extra/subversion/sv_extra_daily_svn_tarball])
AC_CONFIG_FILES(backend/extra/cvs/sv_extra_daily_cvs_tarball,
	[chmod +x backend/extra/cvs/sv_extra_daily_cvs_tarball])
AC_CONFIG_FILES(backend/extra/cvs/sv_extra_viewcvs_forbidden,
	[chmod +x backend/extra/cvs/sv_extra_viewcvs_forbidden])
AC_CONFIG_FILES(backend/extra/cvs/sv_extra_daily_cvs_tarball_forbid,
	[chmod +x backend/extra/cvs/sv_extra_daily_cvs_tarball_forbid])
AC_CONFIG_FILES(backend/extra/cvs/sv_extra_import_cvs,
	[chmod +x backend/extra/cvs/sv_extra_import_cvs])
AC_CONFIG_FILES(backend/extra/cvs/sv_extra_pserver_cvsroots,
	[chmod +x backend/extra/cvs/sv_extra_pserver_cvsroots])
AC_CONFIG_FILES(backend/extra/siteadmin/sv_extra_dead_projects,
	[chmod +x backend/extra/siteadmin/sv_extra_dead_projects])
AC_CONFIG_FILES(backend/extra/siteadmin/sv_extra_merge_projects,
	[chmod +x backend/extra/siteadmin/sv_extra_merge_projects])
AC_CONFIG_FILES(backend/install/sv_update_conf, [chmod +x backend/install/sv_update_conf])
AC_CONFIG_FILES(backend/download/sv_gpgcheckfiles, [chmod +x backend/download/sv_gpgcheckfiles])
AC_CONFIG_FILES(backend/misc/sv_spamcheck_peon, [chmod +x backend/misc/sv_spamcheck_peon])
AC_CONFIG_FILES(backend/misc/sv_reminder, [chmod +x backend/misc/sv_reminder])
AC_CONFIG_FILES(backend/misc/sv_spamcheck_monitor, [chmod +x backend/misc/sv_spamcheck_monitor])
AC_CONFIG_FILES(backend/misc/sv_cleaner, [chmod +x backend/misc/sv_cleaner])
AC_CONFIG_FILES(backend/misc/sv_spamcheck_scholar, [chmod +x backend/misc/sv_spamcheck_scholar])
AC_CONFIG_FILES(backend/misc/sv_spammers_purge, [chmod +x backend/misc/sv_spammers_purge])
AC_CONFIG_FILES(backend/accounts/sv_assign_uid_gid, [chmod +x backend/accounts/sv_assign_uid_gid])

AC_OUTPUT

echo

echo "Configuration directory: " `eval echo $SV_CONF`
echo "Guessed HTTPd/PHP user (configuration file permissions): $GUESS_WWW_SERVER_USER"
echo "Database name: $SV_DB_NAME"
if test ! -z "$SV_DB_OPTIONS"; then
   echo "Database options: $SV_DB_OPTIONS"
fi

echo

echo "Now you can run:"
echo "  make"
echo "  make install"
echo "to install the backend scripts, their Savane Perl library,"
echo "and language files for the web interface"
