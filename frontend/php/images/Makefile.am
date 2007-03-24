icons-stamp:
	for theme in *.theme mime_type; do \
		cd $$theme ; \
		for icon in *.orig.png; do \
			if [ -e $$icon ]; then \
				if [ -e `basename $$icon .orig.png`.png ]; then rm `basename $$icon .orig.png`.png; fi&& \
				convert -resize 24x24 -background white -antialias $$icon `basename $$icon .orig.png`.png && convert -background white  $$icon  $$icon ; \
			fi \
		done && \
		cd .. ;\
	done
	touch icons-stamp

all-local distdir-local: icons-stamp

maintainer-clean-local:
	-find -name "*.orig.png" | while read file; do \
	  if [ -e $${file%.orig.png}.png ]; then \
	    rm $${file%.orig.png}.png \
          fi \
        done
	-rm -f icons-stamp
