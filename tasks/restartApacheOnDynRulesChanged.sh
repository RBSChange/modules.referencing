#!/bin/bash

# To be runned periodically by root, used to load new rules (redirection, etc.)
# You could install it like the following in /etc/crontab for instance:
# */10 *  * * *   root    '/bin/bash /root/bin/restartApacheOnDynRulesChanged.sh > /tmp/restartApacheOnDynRulesChanged.log 2>&1'

restartNeeded="false"

if [ -f /var/www/apachedynconf/restart-needed ]; then
  restartNeeded="true"
  rm /var/www/apachedynconf/restart-needed
fi

cd /home
for user in *
do
  if [ -f $user/webapp/apache/restart-needed ]; then
    restartNeeded="true"
    rm $user/webapp/apache/restart-needed
  fi
done

if [ "$restartNeeded" = "true" ]; then
  echo -n "$(date): restart needed... "
  /usr/sbin/apache2ctl graceful && echo "OK"
else
  echo "$(date): no need to restart."
fi
