EXTRA_DIST = .

install-data-local:
	if [ ! -e $(DESTDIR)$(SV_CONF)/content ]; then \
		mkdir -p -m 755 $(DESTDIR)$(SV_CONF)/content; \
		rsync -av --exclude '.svn' $(srcdir)/site-specific-content/ $(DESTDIR)$(SV_CONF)/content; \
	fi
