# Autotools -> ExtUtils::MakeMaker integration
all:
	make -f Makefile.perl

MANIFEST:
	make -f Makefile.perl manifest
install-data-local: MANIFEST
	make -f Makefile.perl install

uninstall-local:
	$(PERL) uninstall.pl Savane

CLEANFILES = Savane-*.tar.gz MANIFEST MANIFEST.bak
clean-local:
	-make -f Makefile.perl clean
	-rm -f Makefile.perl.old

all MANIFEST install-local clean-local: Makefile.perl
Makefile.perl:
	$(PERL) Makefile.PL PREFIX=$(prefix)

EXTRA_DIST = Makefile.PL Savane.pm Savane/
