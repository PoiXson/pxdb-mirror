#!/bin/bash


echo
if [[ "$1" == "--restore" ]] || [[ "$1" == "--pre" ]]; then


	echo 'Restoring vendor..'

	if [ -L "vendor/pxn/phputils" ]; then
		pushd "vendor/pxn" || exit 1
			rm -fv phputils || exit 1
			[[ -d phputils.original ]] && \
				mv -v phputils.original phputils || exit 1
		popd
	fi


else


	echo 'Creating symlinks..'

#	ln -svf vendor/pxn/phputils/update.sh     update.sh
#	ln -svf vendor/pxn/phputils/pxnloader.php pxnloader.php

#	[ -d public/static ] || mkdir -pv public/static/
#	ln -svf ../../vendor/twbs/bootstrap/    public/static/
#	ln -svf ../../vendor/components/jquery/ public/static/

	if [ -d "../phpUtils/" ]; then
		echo
		echo ' *** Found local phpUtils..'
		pushd "vendor/pxn/" || exit 1
			[[ ! -L phputils ]] && \
				mv -v phputils phputils.original
			ln -svf ../../../phpUtils/ phputils
		popd
	fi


fi
echo
