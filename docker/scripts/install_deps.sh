#! /bin/bash

#
# NOTE: We are hardcoding version strings for this install script, it will be
#   necessary to occasionally review and update these strings

if [[ -e ../../src/all/modules ]] && [[ -e ../../src/all/themes ]]; then 
  echo "Installing dependencies into '`pwd`'..."
else
  echo "** Cannot find the 'sites/all/modules' or 'sites/all/themes' directories! **"
  echo "Make sure you run this script from the 'docker/scripts/' directory..."
  echo "  [CWD: '$(pwd)']"
  echo 
  echo "Cannot continue..."
  exit -1
fi

# Define all the required modules and themes
MODULES="bootstrap-8.x-3.23.tar.gz" # names seperated by |
THEMES=""
touch ../../src/all/modules; touch ../../src/all/themes

echo
echo "Installing modules..."
cd ../../src/all/modules/
for i in $MODULES; do
  echo $i...
  curl -s https://ftp.drupal.org/files/projects/$i > $i
  tar xzf $i
  rm $i
done
echo
echo "Installing themes..."
cd ../themes/
for i in $THEMES; do
  echo $i...
  curl -s https://ftp.drupal.org/files/projects/$i > $i
  tar xzf $i
  rm $i
done

echo
echo "Done with dependencies!"
