COMPOSER=`which composer`;

sudo apt-get update

# MidCOM requires rcs
sudo apt-get install rcs

${COMPOSER} install
sudo chown -R travis var/