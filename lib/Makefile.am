# Public domain.  Originally written by Ineiev from the scratch.

dist_libexec_SCRIPTS = sv_pm_true

modules = \
	Savane.pm \
	Savane/Bzr.pm \
	Savane/Conf.pm \
	Savane/Cvs.pm \
	Savane/DB.pm \
	Savane/Download.pm \
	Savane/Frontend.pm \
	Savane/Frontend/Exit.pm \
	Savane/Git.pm \
	Savane/Groups.pm \
	Savane/Hg.pm \
	Savane/Locks.pm \
	Savane/Mail.pm \
	Savane/Session.pm \
	Savane/Svn.pm \
	Savane/Trackers.pm \
	Savane/User.pm \
	Savane/Util.pm \
	Savane/Version.pm

nobase_libexec_SCRIPTS = $(modules)
sysconf_DATA = savane.conf.pl

substituted = $(modules) savane.conf.pl

subst_pm = sed \
	-e 's,[@]libexecdir[@],$(libexecdir),' \
	-e 's,[@]sysconfdir[@],$(sysconfdir),' \
	-e 's,[@]PERL[@],$(PERL),' \
	-e 's,[@]VERSION[@],$(VERSION),'
	chmod +x $@

$(substituted): %: %.in
	$(MKDIR_P) $(dir $@)
	$(subst_pm) < $^ > $@

CLEANFILES = $(substituted)

EXTRA_DIST = README $(modules:%=%.in) savane.conf.pl.in
