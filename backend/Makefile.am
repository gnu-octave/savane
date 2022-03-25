# Copyright (C) 2007 Sylvain Beucler
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

bin_SCRIPTS = \
	accounts/sv_groups \
	accounts/sv_get_authorized_keys \
	accounts/sv_users \
	accounts/sv_membersh \
	mail/sv_mailman \
	mail/sv_aliases \
	mail/sv_mailman_and_mailarchivedotcom \
	extra/subversion/sv_extra_svn_postcommit_brigde \
	extra/subversion/sv_extra_daily_svn_tarball \
	extra/cvs/sv_extra_daily_cvs_tarball \
	extra/cvs/sv_extra_viewcvs_forbidden \
	extra/cvs/sv_extra_daily_cvs_tarball_forbid \
	extra/cvs/sv_extra_import_cvs \
	extra/cvs/sv_extra_pserver_cvsroots \
	extra/siteadmin/sv_extra_dead_projects \
	extra/siteadmin/sv_extra_merge_projects \
	install/sv_update_conf \
	misc/sv_spamcheck_peon \
	misc/sv_reminder \
	misc/sv_spamcheck_monitor \
	misc/sv_cleaner \
	misc/sv_spamcheck_scholar \
	misc/sv_spammers_purge \
	accounts/sv_assign_uid_gid \
	accounts/sv_homedirs \
	accounts/sv_authorized_keys

CLEANFILES = $(bin_SCRIPTS)

SUBDIRS = system-specific
