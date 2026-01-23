#!/bin/bash
file=$1
if [[ -r "$file" ]]; then
  entityID=$(head -1 "$file")
  echo "Removing $entityID"
  php /var/www/scripts/removeEntity.php "$entityID" Prod && rm "$file"
else
  echo "Can't read $file"
fi
