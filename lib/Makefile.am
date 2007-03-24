# Autotools -> ExtUtils::MakeMaker integration
all:
	make -f Makefile.perl PREFIX=$(prefix)

MANIFEST:
	make -f Makefile.perl manifest
install-data-local: MANIFEST
	make -f Makefile.perl PREFIX=$(prefix) install

CLEANFILES=Makefile.perl.old MANIFEST.bak Savane-*.tar.gz
clean-local:
	make -f Makefile.perl PREFIX=$(prefix) clean

all MANIFEST install-local clean-local: Makefile.perl
Makefile.perl:
	perl Makefile.PL

# I use a non-portable $(shell) contruct here. Cf. ../Makefile.am
EXTRA_DIST = $(shell find -name "*.pm")
