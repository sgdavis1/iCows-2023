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
MODULES="masquerade-8.x-2.0-beta4.tar.gz flag-8.x-4.0-beta3.tar.gz twig_tweak-8.x-2.9.tar.gz auto_username-8.x-1.2.tar.gz token-8.x-1.10.tar.gz fullcalendar_view-5.1.4.tar.gz smtp-8.x-1.0.tar.gz gdoc_field-8.x-1.3.tar.gz menu_item_role_access-8.x-2.0.tar.gz field_permissions-8.x-1.1.tar.gz"
THEMES="bootstrap-8.x-3.23.tar.gz" # names seperated by |
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
