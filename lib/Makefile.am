# Autotools -> ExtUtils::MakeMaker integration
all:
	$(MAKE) -f Makefile.perl VPATH=.:$(srcdir)

MANIFEST:
	$(MAKE) -f Makefile.perl manifest VPATH=.:$(srcdir)
install-data-local: MANIFEST
	$(MAKE) -f Makefile.perl install VPATH=.:$(srcdir)

uninstall-local:
	PERLLIB=`find $(DESTDIR)/$(prefix)/lib -name perl -type d` \
          $(PERL) $(srcdir)/uninstall.pl Savane
	$(RM) `find $(DESTDIR)/$(prefix)/lib -name perllocal.pod`

CLEANFILES = Savane-*.tar.gz MANIFEST MANIFEST.bak
clean-local:
	$(MAKE) -f Makefile.perl clean VPATH=.:$(srcdir)
	$(RM) Makefile.perl.old

all MANIFEST install-local clean-local: Makefile.perl
Makefile.perl:
	$(PERL) $(srcdir)/Makefile.PL PREFIX=$(prefix)

EXTRA_DIST = Makefile.PL Savane.pm Savane/ uninstall.pl
