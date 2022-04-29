# Based on gettext-0.16.1/gettext-tools/examples/hello-php
# (public domain)

# General automake options.
AUTOMAKE_OPTIONS = gnits
ACLOCAL_AMFLAGS = -I autotools/m4

# The list of subdirectories containing Makefiles.
SUBDIRS = etc frontend lib autotools/m4 po local

backend_scripts = \
	backend/accounts/sv_groups \
	backend/accounts/sv_get_authorized_keys \
	backend/accounts/sv_users \
	backend/accounts/sv_membersh \
	backend/mail/sv_mailman \
	backend/mail/sv_aliases \
	backend/mail/sv_mailman_and_mailarchivedotcom \
	backend/extra/subversion/sv_extra_daily_svn_tarball \
	backend/extra/cvs/sv_extra_daily_cvs_tarball \
	backend/extra/cvs/sv_extra_viewcvs_forbidden \
	backend/extra/cvs/sv_extra_daily_cvs_tarball_forbid \
	backend/extra/cvs/sv_extra_import_cvs \
	backend/extra/cvs/sv_extra_pserver_cvsroots \
	backend/misc/sv_reminder \
	backend/misc/sv_cleaner \
	backend/accounts/sv_assign_uid_gid

bin_SCRIPTS = $(backend_scripts)

subst_in_scripts = \
	sed \
	-e 's,[@]PERL[@],$(PERL),' \
	-e 's,[@]libexecdir[@],$(libexecdir),' \
	-e 's,[@]sysconfdir[@],$(sysconfdir),'

$(backend_scripts): %: %.in
	$(MKDIR_P) $(dir $@)
	$(subst_in_scripts) < $^ > $@
	chmod +x $@

EXTRA_DIST = bootstrap AUTHORS.Savannah AUTHORS.SourceForge \
	REQUIREMENTS db debian devel update tests \
	$(backend_scripts:%=%.in) \
	backend/README

CLEANFILES=$(backend_scripts)

database:
	( \
	  echo "CREATE DATABASE $(SV_DB_NAME) DEFAULT CHARACTER SET utf8;"; \
	  echo "USE $(SV_DB_NAME);"; \
	  cat db/mysql/table_*.structure; \
	  cat db/mysql/table_*.initvalues; \
	  cat db/mysql/bootstrap.sql; \
	) | $(SV_DB_CMD) $(SV_DB_OPTIONS)
# TODO: create the siteadmin group here

dist-hook:
	find $(distdir) -type d -name '.svn' -o -name '*~' | xargs -r rm -rf

distcheck-hook:
	if grep -q '^This is a stub' $(distdir)/ChangeLog; then \
	  echo; echo; \
	  echo "ChangeLog hasn't been generated.  " \
            "Configure with --enable-changelog."; \
	  echo; echo; false; \
	fi
