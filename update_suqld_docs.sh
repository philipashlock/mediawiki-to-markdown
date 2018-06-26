#!/bin/bash
cd "$(dirname "$0")"

echo "Fetching Mediawiki export"
ssh -t suq-docker-web-2 'cd /srv/sites/docs.suqld.org.au; sudo docker-compose exec -T mediawiki php maintenance/dumpBackup.php --current --filter namespace:NS_MAIN --include-files --uploads > /tmp/docsexport.xml'
scp suq-docker-web-2:/tmp/docsexport.xml ./docsexport.xml
ssh suq-docker-web-2 rm /tmp/docsexport.xml

echo "Pulling latest docs repo"
pushd docs
git up
popd
echo "Removing all files from docs repo so we can process the export"
rm -rf docs/*

echo "Converting Mediawiki export to Markdown"
php convert.php --filename=docsexport.xml --indexes=true --output=docs/

echo "Committing changes"
pushd docs
git status
git add ./
git commit -am "Updated docs from $(date '+%d %b %Y')"
git push
popd

echo "Cleanup"
rm docsexport.xml
