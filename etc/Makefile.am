EXTRA_DIST = $(shell find -type d -name ".svn" -prune -o -type f ! -name "*~" -print)

install-data-local:
	if [ ! -e $(DESTDIR)$(SV_CONF)/content ]; then
		cp -r $(srcdir)/site-specific-content $(DESTDIR)$(SV_CONF)/content
	fi
