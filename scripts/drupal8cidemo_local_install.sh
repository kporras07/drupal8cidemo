#!/usr/bin/env bash
SITE_UUID="af73781a-8d3b-4752-98b7-a2a3e898bf1a"
ahoy drush cc drush
echo "Installing..."
ahoy drush si drupal8cidemo --account-pass=admin -y
echo "Set site uuid..."
ahoy drush config-set "system.site" uuid "$SITE_UUID" -y
echo "Importing config..."
if [ -f ./config/sync/core.extension.yml ] ; then ahoy drush cim -y ; fi
echo "Cleaning cache..."
ahoy drush cr
