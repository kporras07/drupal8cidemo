#!/usr/bin/env bash
SITE_UUID="af73781a-8d3b-4752-98b7-a2a3e898bf1a"
ahoy drush cc drush
echo "Installing..."
ahoy robo setup:drupal admin admin site "$SITE_UUID"
