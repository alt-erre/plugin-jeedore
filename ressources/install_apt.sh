PROGRESS_FILE=/tmp/jeedore_dependencies

touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "***************************************************************"
echo "*                 Dependencies installation                   *"
echo "***************************************************************"
echo "downloading dotnet sdk for x86 arm arm64..."
sudo rm -rf jeedom-master.zip

wget https://github.com/rezolv-fr/jeedoredaemon-dotnet/archive/master.zip -O jeedom-master.zip
echo 30 > ${PROGRESS_FILE}

echo "installing unzip"
echo 50 > ${PROGRESS_FILE}
sudo apt-get install unzip

echo "unpacking archive"
sudo unzip jeedom-master.zip -d /tmp/
echo 90 > ${PROGRESS_FILE}

echo "copying files"
sudo rsync -i -a -v /tmp/jeedoredaemon-dotnet-master/ /var/www/html/plugins/jeedore/
echo 99 > ${PROGRESS_FILE}

echo "deleting downloaded files"
sudo rm -rf /tmp/jeedoredaemon-dotnet-master/*
sudo rm -rf jeedom-master.zip
echo 100 > ${PROGRESS_FILE}

echo "***************************************************************"
echo "*                    Installation done!                       *"
echo "***************************************************************"
rm ${PROGRESS_FILE}