stamp-icons:
	for theme in *.theme mime_type common/arrows?; do \
	  ( \
	    cd $$theme ; \
	    for icon in *.orig.png; do \
	      if [ -e $$icon ]; then \
	        convert -resize 24x24 -background white -antialias $$icon `basename $$icon .orig.png`.png; \
	      fi; \
	    done \
	  ) \
	done
	touch stamp-icons

# Keep those separate for AM1.9 to recognize them
# (ie not the "all-local distdir-local: stamp-icons" one-liner)
all-local: stamp-icons
distdir-local: stamp-icons

maintainer-clean-local:
	-find -name "*.orig.png" | while read file; do \
	  if [ -e $${file%.orig.png}.png ]; then \
	    rm $${file%.orig.png}.png; \
          fi; \
        done
	-rm -f stamp-icons
