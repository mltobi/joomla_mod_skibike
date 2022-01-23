#!/bin/sh

download_link=$1

rm -r tmp
mkdir tmp
cd tmp

wget $download_link

version=`echo $download_link | sed 's/.*virtuemart.//; s/_package.*//'`
zip_filename=`echo com_virtuemart.${version}_package_or_extract`
main_filename=`echo com_virtuemart.${version}`
aio_filename=`echo com_virtuemart.${version}_ext_aio`

unzip -d ./$zip_filename $zip_filename.zip

unzip -d ./patched ./${zip_filename}/${main_filename}.zip
unzip -d ./patched_ext_aio ./${zip_filename}/${aio_filename}.zip

cd patched
patch -p 1 < ../../com_virtuemart.patch
zip -r ../${zip_filename}/${main_filename}.zip *
cd ..

cd patched_ext_aio
patch -p 1 < ../../com_virtuemart_ext_aio.patch
zip -r ../${zip_filename}/${aio_filename}.zip *
cd ..

cd ${zip_filename}
zip -r ../../${zip_filename}_patched.zip *


