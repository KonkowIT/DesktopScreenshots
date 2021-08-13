HOST="$1"
SN="$2"
cd ss/$SN && curl -O -u usr:psswd ftp://$HOST/SCREENNETWORK/player/screenshot.jpg
exit 0