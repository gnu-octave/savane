# Autotools -> ExtUtils::MakeMaker integration
all:
	make -f Makefile.perl PREFIX=$(prefix)

MANIFEST:
	make -f Makefile.perl manifest
install-data-local: MANIFEST
	make -f Makefile.perl PREFIX=$(prefix) install

clean-local:
	make -f Makefile.perl PREFIX=$(prefix) clean
	-rm -f Makefile.perl.old MANIFEST.bak
	-rm -f Savane-*.tar.gz

all MANIFEST install-local clean-local: Makefile.perl
Makefile.perl:
	perl Makefile.PL

# I use a non-portable $(shell) contruct here. Cf. ../Makefile.am
EXTRA_DIST = $(shell find -name "*.pm")
