all: fresh build install

fresh:
	echo fresh

install: 
	echo install
	
build:
	echo build

clean:
	rm -rf debian/clientzone 
	rm -rf debian/*.substvars debian/*.log debian/*.debhelper debian/files debian/debhelper-build-stamp *.deb

db:
	phinx migrate

deb:
	dpkg-buildpackage -A -us -uc

dimage: deb
	mv ../clientzone_*_all.deb .
	docker build -t vitexsoftware/clientzone .

dtest:
	docker run -d -p 9002:80 --name clientzone  vitexsoftware/clientzone:latest
        
drun: dimage
	docker run  -dit --name ClientZone -p 9002:80 vitexsoftware/clientzone
	nightly http://localhost:9002/clientzone

dclean:
	docker rmi $(docker images |grep 'clientzone')

vagrant: clean
	vagrant destroy
	vagrant up

# Gettext: regenerate POT, merge PO, compile MO
i18n:
	find src -name '*.php' -print0 | xargs -0 xgettext \
		--language=PHP --from-code=UTF-8 --keyword=_ --keyword=gettext \
		--keyword=ngettext:1,2 --package-name=abraflexi-clientzone \
		--msgid-bugs-address=info@vitexsoftware.cz \
		-o i18n/clientzone.pot
	msgmerge --update --backup=simple i18n/cs_CZ/LC_MESSAGES/clientzone.po i18n/clientzone.pot
	msgmerge --update --backup=simple i18n/en_US/LC_MESSAGES/clientzone.po i18n/clientzone.pot
	msgfmt -c -o i18n/cs_CZ/LC_MESSAGES/clientzone.mo i18n/cs_CZ/LC_MESSAGES/clientzone.po
	msgfmt -c -o i18n/en_US/LC_MESSAGES/clientzone.mo i18n/en_US/LC_MESSAGES/clientzone.po

.PHONY: install i18n
