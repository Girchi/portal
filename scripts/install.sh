#!/bin/bash

#links
WEBSITE="http://girchi.docker.localhost"
MAILHOG="http://mailhog.girchi.docker.localhost"
PMA="http://pma.girchi.docker.localhost"

#colors 
NONE='\033[00m'
GREEN='\033[01;32m'
BLUE='\033[1;36m'

clear
echo -e "${BLUE}[1/8] Stopping containers...\n\v${NONE}"
make stop

echo -e "${BLUE}[2/8] Starting up containers...\n\v${NONE}"
make up

echo -e "\n${BLUE}[3/8] Building UI Project...\n\v${NONE}"
make update-ui

echo -e "\n${BLUE}[4/8] Running pre-install script...\n\v${NONE}"
./scripts/pre-install.sh

echo -e "\n${BLUE}[5/8] Running composer install...\n\v${NONE}"
docker-compose exec php composer install
docker-compose exec php ln -sf ../../githooks/pre-commit .git/hooks/pre-commit
docker-compose exec php ln -sf ../../githooks/commit-msg .git/hooks/pre-commit-msg

echo -e "\n${BLUE}[6/8] Installing drupal...\n\v${NONE}"
docker-compose exec php drush si --existing-config --account-pass=1234  -y -vvv 

echo -e "\n${BLUE}[7/8] Importing translations...\n\v${NONE}"
docker-compose exec php drush language-import -y

echo -e "\n${BLUE}[8/8] Importing config...\n\v${NONE}"
docker-compose exec php drush cim -y -vvv

echo -e "\n${BLUE}Girchi Website is ready${NONE} ${GREEN}\n\vWebsite${NONE}  - " ${GREEN}${WEBSITE}${NONE}"${GREEN}\n\vMailHog${NONE} - " ${GREEN}${MAILHOG}${NONE}"${GREEN}\n\vphpMyAdmin${NONE} - " ${GREEN}${PMA}${NONE}"\n"
