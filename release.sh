#!/bin/sh

echo "Release Version $@ Start..."

if [[ "$@" = "" ]]
then
    echo "Version can not be empty"
    exit 1
fi

VERSION_FILE="src/Version.php"

echo "Update src/Version.php ..."

echo """<?php


namespace TcbManager;


class Version
{
    const VERSION = \"TCB-MANAGER-PHP/$@\";
}
""" > ${VERSION_FILE}

git add ${VERSION_FILE}
git commit ${VERSION_FILE} -m "update src/Version.php"

echo "git release $@"

git release $@

echo "Release Version $@ Finish."

