# Based on gettext-0.16.1/gettext-tools/examples/hello-php
# (public domain)

# General automake options.
AUTOMAKE_OPTIONS = gnits
ACLOCAL_AMFLAGS = -I autotools/m4

# The list of subdirectories containing Makefiles.
SUBDIRS = backend frontend lib autotools/m4 po

# Additional files to be distributed.  $(shell) is not supported by
# automake but works in this case, as long as one doesn't use '/' in
# the expression (otherwise it's interpreted as a directory
# name). Beware that including 'frontend/', a large directory,
# produces a "Argument list too long" shell error). I tried using the
# list of directories directly but then automake includes everything
# recursively - including the .svn files that we carefully excluded,
# as documented in (automake-1.7.info)Dist. Alternatively we could do
# as they say and reference all files explicitely in EXTRA_DIST.
EXTRA_DIST = bootstrap AUTHORS.Savannah AUTHORS.SourceForge BUILD-DEB \
	REQUIREMENTS INSTALL.verbose \
	$(shell find debian etc devel doc update -type d -name ".svn" -prune \
	          -o -type f ! -name "*~" -print)
